"""Ingestion flow for proposicoes, votacoes, and votos - parallelized."""

import asyncio
from datetime import date
from typing import Any
from urllib.parse import parse_qs, urlparse

import structlog

from app.ingestion.application.ports import IngestionJobRepository, QuarantineRepository
from app.ingestion.domain.entities import (
    DataSource,
    IngestionDataset,
    IngestionJob,
)
from app.workers.camara_client import CamaraClient
from app.workers.storage import save_raw

logger = structlog.get_logger(__name__)

MAX_CONCURRENT_PROPOSICOES = 20


async def _fetch_page(
    client: CamaraClient,
    pagina: int,
    data_apresentacao_inicio: date | None,
) -> tuple[int, dict[str, Any] | None, list[dict[str, Any]]]:
    """Fetch a single page of proposicoes."""
    try:
        response = await client.list_proposicoes(
            pagina=pagina,
            data_apresentacao_inicio=data_apresentacao_inicio,
        )
        dados = response.get("dados", [])
        save_raw(
            DataSource.CAMARA.value,
            IngestionDataset.PROPOSICOES.value,
            date.today(),
            pagina,
            response,
        )
        return pagina, response, dados
    except Exception as e:
        logger.error("proposicoes_fetch_failed", pagina=pagina, error=str(e))
        return pagina, None, []


async def _fetch_votacoes_for_proposicao(
    client: CamaraClient,
    proposicao_id: int,
) -> list[dict[str, Any]]:
    """Fetch votacoes for a single proposition."""
    try:
        votacoes_resp = await client.get_votacoes_proposicao(proposicao_id)
        save_raw(
            DataSource.CAMARA.value,
            IngestionDataset.VOTACOES.value,
            date.today(),
            proposicao_id,
            votacoes_resp,
        )
        return votacoes_resp.get("dados", [])
    except Exception as e:
        logger.warning(
            "votacoes_fetch_failed",
            proposicao_id=proposicao_id,
            error=str(e),
        )
        return []


async def _fetch_votos_for_votacao(
    client: CamaraClient,
    votacao_id: int,
) -> None:
    """Fetch votos for a single votacao."""
    try:
        votos_resp = await client.get_votos_votacao(votacao_id)
        save_raw(
            DataSource.CAMARA.value,
            IngestionDataset.VOTOS.value,
            date.today(),
            votacao_id,
            votos_resp,
        )
    except Exception as e:
        logger.warning(
            "votos_fetch_failed",
            votacao_id=votacao_id,
            error=str(e),
        )


async def ingest_proposicoes(
    job_repo: IngestionJobRepository,
    quarantine_repo: QuarantineRepository,
    job: IngestionJob,
    data_apresentacao_inicio: date | None = None,
    shared_semaphore: asyncio.Semaphore | None = None,
) -> IngestionJob:
    """Ingest proposicoes, votacoes, and votos from Câmara API (parallelized)."""
    inserted = 0
    failed = 0

    async with CamaraClient(semaphore=shared_semaphore) as client:
        first_result = await _fetch_page(client, 1, data_apresentacao_inicio)
        _, first_resp, first_dados = first_result

        if not first_dados:
            logger.warning("no_proposicoes_found")
            job.mark_completed(inserted=0, updated=0, failed=1)
            return await job_repo.save(job)

        links = first_resp.get("links", []) if first_resp else []
        last_link = next((link for link in links if link.get("rel") == "last"), None)
        total_pages = 1
        if last_link:
            parsed = urlparse(last_link["href"])
            params = parse_qs(parsed.query)
            total_pages = int(params.get("pagina", ["1"])[0])
        else:
            next_link = next((link for link in links if link.get("rel") == "next"), None)
            if next_link:
                total_pages = 2

        logger.info("proposicoes_pagination", total_pages=total_pages)

        all_proposicoes: list[dict[str, Any]] = list(first_dados)

        async def fetch_page(pagina: int):
            nonlocal all_proposicoes
            _, _, dados = await _fetch_page(client, pagina, data_apresentacao_inicio)
            all_proposicoes.extend(dados)

        if total_pages > 1:
            await asyncio.gather(*[fetch_page(p) for p in range(2, total_pages + 1)])

        inserted += len(all_proposicoes)

        prop_sem = asyncio.Semaphore(MAX_CONCURRENT_PROPOSICOES)

        async def process_proposicao(prop: dict[str, Any]):
            nonlocal failed
            async with prop_sem:
                try:
                    votacoes = await _fetch_votacoes_for_proposicao(client, prop["id"])
                    if votacoes:
                        await asyncio.gather(
                            *[_fetch_votos_for_votacao(client, v["id"]) for v in votacoes]
                        )
                except Exception as e:
                    logger.warning(
                        "votacoes_fetch_failed",
                        proposicao_id=prop.get("id"),
                        error=str(e),
                    )
                    failed += 1

        await asyncio.gather(*[process_proposicao(p) for p in all_proposicoes])

    job.mark_completed(inserted=inserted, updated=0, failed=failed)
    return await job_repo.save(job)
