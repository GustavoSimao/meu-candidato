"""Câmara dos Deputados API client for data ingestion."""

import asyncio
import time
from datetime import date
from typing import Any

import httpx
import structlog
from tenacity import (
    retry,
    retry_if_exception_type,
    stop_after_attempt,
    wait_exponential_jitter,
)

from app.ingestion.domain.value_objects import RateLimitConfig, RetryConfig, TimeoutConfig

logger = structlog.get_logger(__name__)

HTTP_TOO_MANY_REQUESTS = 429


class RateLimitError(Exception):
    """Raised when rate limit is exceeded."""
    def __init__(self, retry_after: float):
        self.retry_after = retry_after
        super().__init__(f"Rate limit exceeded, retry after {retry_after}s")


class CamaraClient:
    """Async client for Câmara dos Deputados open data API."""

    BASE_URL = "https://dadosabertos.camara.leg.br/api/v2"

    def __init__(
        self,
        rate_limit: RateLimitConfig | None = None,
        retry_config: RetryConfig | None = None,
        timeout: TimeoutConfig | None = None,
        semaphore: asyncio.Semaphore | None = None,
    ):
        self.rate_limit = rate_limit or RateLimitConfig()
        self.retry_config = retry_config or RetryConfig()
        self.timeout = timeout or TimeoutConfig()
        self._last_request_time = 0.0
        self._rate_lock = asyncio.Lock()
        self._semaphore = semaphore or asyncio.Semaphore(self.rate_limit.burst_limit)
        self._client: httpx.AsyncClient | None = None

    async def __aenter__(self) -> "CamaraClient":
        self._client = httpx.AsyncClient(
            base_url=self.BASE_URL,
            timeout=httpx.Timeout(
                timeout=self.timeout.total_seconds,
                connect=self.timeout.connect_seconds,
            ),
            headers={"Accept": "application/json"},
        )
        return self

    async def __aexit__(self, exc_type, exc_val, exc_tb) -> None:
        if self._client:
            await self._client.aclose()

    async def _rate_limit(self) -> None:
        """Enforce rate limiting between requests (async-safe)."""
        async with self._rate_lock:
            elapsed = time.monotonic() - self._last_request_time
            min_interval = 1.0 / self.rate_limit.requests_per_second
            if elapsed < min_interval:
                await asyncio.sleep(min_interval - elapsed)
            self._last_request_time = time.monotonic()

    @retry(
        wait=wait_exponential_jitter(initial=1, max=30),
        stop=stop_after_attempt(3),
        retry=retry_if_exception_type((httpx.RequestError, httpx.HTTPStatusError, RateLimitError)),
        reraise=True,
    )
    async def _get(self, path: str, params: dict[str, Any] | None = None) -> dict[str, Any]:
        """Make GET request with rate limiting and retry."""
        async with self._semaphore:
            await self._rate_limit()

            if not self._client:
                raise RuntimeError("Client not initialized. Use async context manager.")

            response = await self._client.get(path, params=params)

            if response.status_code == HTTP_TOO_MANY_REQUESTS:
                retry_after = float(response.headers.get("Retry-After", 60))
                logger.warning("rate_limit_exceeded", retry_after=retry_after)
                raise RateLimitError(retry_after)

            response.raise_for_status()
            return response.json()

    async def list_deputados(
        self,
        pagina: int = 1,
        itens: int = 100,
        data_inicio: date | None = None,
        uf: str | None = None,
        partido: str | None = None,
    ) -> dict[str, Any]:
        """List deputies with pagination and filters."""
        params = {"pagina": pagina, "itens": min(itens, 100)}
        if data_inicio:
            params["dataInicio"] = data_inicio.isoformat()
        if uf:
            params["uf"] = uf.upper()
        if partido:
            params["partido"] = partido.upper()

        return await self._get("/deputados", params)

    async def get_deputado(self, deputado_id: int) -> dict[str, Any]:
        """Get detailed deputy information."""
        return await self._get(f"/deputados/{deputado_id}")

    async def get_mandatos(self, deputado_id: int) -> dict[str, Any]:
        """Get deputy mandates.

        .. deprecated::
            The Câmara API v2 endpoint /deputados/{id}/mandatos returns HTTP 405.
            Use ingest_mandatos.py which downloads the CSV from dadosabertos.camara.leg.br
            as an alternative source.
        """
        raise NotImplementedError(
            "Get /deputados/{id}/mandatos returns HTTP 405. Use ingest_mandatos.py instead."
        )

    async def list_proposicoes(
        self,
        pagina: int = 1,
        itens: int = 100,
        data_apresentacao_inicio: date | None = None,
        sigla_tipo: str | None = None,
        id_deputado_autor: int | None = None,
    ) -> dict[str, Any]:
        """List propositions with pagination and filters."""
        params = {"pagina": pagina, "itens": min(itens, 100)}
        if data_apresentacao_inicio:
            params["dataApresentacaoInicio"] = data_apresentacao_inicio.isoformat()
        if sigla_tipo:
            params["siglaTipo"] = sigla_tipo.upper()
        if id_deputado_autor:
            params["idDeputadoAutor"] = id_deputado_autor

        return await self._get("/proposicoes", params)

    async def get_proposicao(self, proposicao_id: int) -> dict[str, Any]:
        """Get detailed proposition information."""
        return await self._get(f"/proposicoes/{proposicao_id}")

    async def get_votacoes_proposicao(self, proposicao_id: int) -> dict[str, Any]:
        """Get votings for a proposition."""
        return await self._get(f"/proposicoes/{proposicao_id}/votacoes")

    async def get_votos_votacao(self, votacao_id: int) -> dict[str, Any]:
        """Get individual votes for a voting session."""
        return await self._get(f"/votacoes/{votacao_id}/votos")

    async def list_despesas(
        self,
        deputado_id: int,
        ano: int | None = None,
        mes: int | None = None,
        pagina: int = 1,
        itens: int = 100,
    ) -> dict[str, Any]:
        """List expenses for a deputy."""
        params = {"pagina": pagina, "itens": min(itens, 100)}
        if ano:
            params["ano"] = ano
        if mes:
            params["mes"] = mes

        return await self._get(f"/deputados/{deputado_id}/despesas", params)
