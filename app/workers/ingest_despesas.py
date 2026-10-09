"""Ingestion flow for despesas (parliamentary expenses) - parallelized."""

import asyncio
from datetime import date
from typing import Any

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


async def _fetch_despesas_for_deputado(
    client: CamaraClient,
    deputado_id: int,
    ano: int | None,
    mes: int | None,
) -> tuple[int, int]:
    """Fetch all despesas pages for a single deputy. Returns (inserted, failed)."""
    inserted = 0
    failed = 0
    pagina = 1

    while True:
        try:
            despesas_resp = await client.list_despesas(
                deputado_id=deputado_id,
                ano=ano,
                mes=mes,
                pagina=pagina,
            )
        except Exception as e:
            logger.error(
                "despesas_fetch_failed",
                deputado_id=deputado_id,
                pagina=pagina,
                error=str(e),
            )
            failed += 1
            break

        dados = despesas_resp.get("dados", [])
        if not dados:
            break

        save_raw(
            DataSource.CAMARA.value,
            IngestionDataset.DESPESAS.value,
            date.today(),
            deputado_id * 1000 + pagina,
            despesas_resp,
        )

        for despesa in dados:
            try:
                inserted += 1
            except Exception as e:
                logger.error("despesa_process_failed", despesa=despesa, error=str(e))
                failed += 1
                quarantine = QuarantineRecord(
                    data_source=DataSource.CAMARA,
                    dataset=IngestionDataset.DESPESAS,
                    raw_data=despesa,
                    errors=[str(e)],
                )
                save_quarantine(
                    DataSource.CAMARA.value,
                    IngestionDataset.DESPESAS.value,
                    date.today(),
                    [quarantine.raw_data],
                )

        links = despesas_resp.get("links", [])
        next_link = next((link for link in links if link.get("rel") == "next"), None)
        if not next_link:
            break
        pagina += 1

    return inserted, failed


async def ingest_despesas(
    job_repo: IngestionJobRepository,
    quarantine_repo: QuarantineRepository,
    job: IngestionJob,
    ano: int | None = None,
    mes: int | None = None,
    shared_semaphore: asyncio.Semaphore | None = None,
) -> IngestionJob:
    """Ingest despesas for all deputies from Câmara API (parallelized per deputy)."""
    inserted = 0
    failed = 0

    async with CamaraClient(semaphore=shared_semaphore) as client:
        try:
            deputados_resp = await client.list_deputados(itens=1000)
        except Exception as e:
            logger.error("deputados_list_failed", error=str(e))
            job.mark_failed(str(e))
            return await job_repo.save(job)

        deputados = deputados_resp.get("dados", [])
        logger.info("despesas_start", deputado_count=len(deputados))

        async def process_deputado(dep: dict[str, Any]):
            nonlocal inserted, failed
            ins, fail = await _fetch_despesas_for_deputado(client, dep["id"], ano, mes)
            inserted += ins
            failed += fail

        await asyncio.gather(*[process_deputado(d) for d in deputados])

    job.mark_completed(inserted=inserted, updated=0, failed=failed)
    return await job_repo.save(job)
