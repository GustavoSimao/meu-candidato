"""Ingestion flow for deputados - paginated and parallelized."""

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
    QuarantineRecord,
)
from app.workers.camara_client import CamaraClient
from app.workers.storage import save_quarantine, save_raw

logger = structlog.get_logger(__name__)


async def _fetch_page(
    client: CamaraClient,
    pagina: int,
    data_inicio: date | None,
) -> tuple[int, dict[str, Any] | None, list[dict[str, Any]]]:
    """Fetch a single page of deputados."""
    try:
        response = await client.list_deputados(pagina=pagina, data_inicio=data_inicio)
        dados = response.get("dados", [])
        save_raw(
            DataSource.CAMARA.value,
            IngestionDataset.DEPUTADOS.value,
            date.today(),
            pagina,
            response,
        )
        return pagina, response, dados
    except Exception as e:
        logger.error("deputados_fetch_failed", pagina=pagina, error=str(e))
        return pagina, None, []


async def _get_total_pages(client: CamaraClient) -> int:
    """Determine total number of pages from the first page response."""
    try:
        response = await client.list_deputados(pagina=1)
        dados = response.get("dados", [])
        if not dados:
            return 0

        save_raw(
            DataSource.CAMARA.value,
            IngestionDataset.DEPUTADOS.value,
            date.today(),
            1,
            response,
        )

        links = response.get("links", [])
        last_link = next((link for link in links if link.get("rel") == "last"), None)
        if last_link:
            parsed = urlparse(last_link["href"])
            params = parse_qs(parsed.query)
            return int(params.get("pagina", ["1"])[0])

        next_link = next((link for link in links if link.get("rel") == "next"), None)
        if not next_link:
            return 1

        return 2
    except Exception as e:
        logger.error("deputados_first_page_failed", error=str(e))
        return 0


async def ingest_deputados(
    job_repo: IngestionJobRepository,
    quarantine_repo: QuarantineRepository,
    job: IngestionJob,
    data_inicio: date | None = None,
    shared_semaphore: asyncio.Semaphore | None = None,
) -> IngestionJob:
    """Ingest deputados from Câmara API (parallelized, mandatos skipped)."""
    inserted = 0
    failed = 0

    async with CamaraClient(semaphore=shared_semaphore) as client:
        total_pages = await _get_total_pages(client)

        if total_pages == 0:
            logger.warning("no_deputados_pages", reason="first_page_empty_or_failed")
            job.mark_completed(inserted=0, updated=0, failed=1)
            return await job_repo.save(job)

        logger.info("deputados_pagination", total_pages=total_pages)

        pages_to_fetch = list(range(2, total_pages + 1))

        async def fetch_and_process(pagina: int):
            nonlocal inserted, failed
            _, _, dados = await _fetch_page(client, pagina, data_inicio)
            for dep in dados:
                try:
                    inserted += 1
                except Exception as e:
                    logger.error(
                        "deputado_process_failed",
                        deputado_id=dep.get("id"),
                        error=str(e),
                    )
                    failed += 1
                    quarantine = QuarantineRecord(
                        data_source=DataSource.CAMARA,
                        dataset=IngestionDataset.DEPUTADOS,
                        raw_data=dep,
                        errors=[str(e)],
                    )
                    save_quarantine(
                        DataSource.CAMARA.value,
                        IngestionDataset.DEPUTADOS.value,
                        date.today(),
                        [quarantine.raw_data],
                    )

        if pages_to_fetch:
            await asyncio.gather(*[fetch_and_process(p) for p in pages_to_fetch])

    job.mark_completed(inserted=inserted, updated=0, failed=failed)
    return await job_repo.save(job)
