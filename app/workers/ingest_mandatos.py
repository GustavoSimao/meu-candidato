"""Ingestion flow for mandatos (parliamentary mandates) via CSV fallback.

The Câmara API v2 endpoint `/deputados/{id}/mandatos` returns HTTP 405 (Method Not Allowed).
This module uses the CSV download from dadosabertos.camara.leg.br as an alternative source,
which includes `idLegislaturaInicial` and `idLegislaturaFinal` columns representing mandates.
Legislatura periods are fetched in parallel with a dedicated semaphore to avoid
blocking the shared semaphore used by other phases.
"""

import asyncio
from datetime import date
from typing import Any

import httpx
import pandas as pd
import structlog

from app.ingestion.application.ports import IngestionJobRepository, QuarantineRepository
from app.ingestion.domain.entities import (
    DataSource,
    IngestionDataset,
    IngestionJob,
    QuarantineRecord,
)
from app.workers.storage import save_quarantine, save_raw

logger = structlog.get_logger(__name__)

CAMARA_DEPUTADOS_CSV_URL = "https://dadosabertos.camara.leg.br/arquivos/deputados/csv/deputados.csv"
LEGURLS_URL = "https://dadosabertos.camara.leg.br/api/v2/legislaturas"
HTTP_OK = 200
MANDATOS_SEMAPHORE_SIZE = 20


async def _download_deputados_csv() -> pd.DataFrame:
    """Download deputados CSV from Câmara open data portal."""
    async with httpx.AsyncClient(timeout=60.0) as client:
        logger.info("mandatos_csv_download_start")
        resp = await client.get(CAMARA_DEPUTADOS_CSV_URL, follow_redirects=True)
        resp.raise_for_status()
        df = pd.read_csv(pd.io.common.StringIO(resp.text), sep=";")
        logger.info("mandatos_csv_downloaded", row_count=len(df))
        return df


async def _fetch_legislatura_info(
    client: httpx.AsyncClient,
    legislatura_id: int,
    cache: dict[int, dict[str, Any] | None],
    lock: asyncio.Lock,
) -> dict[str, Any] | None:
    """Fetch legislatura period info with caching to avoid duplicate requests."""
    async with lock:
        if legislatura_id in cache:
            return cache[legislatura_id]

    try:
        resp = await client.get(f"{LEGURLS_URL}/{legislatura_id}")
        if resp.status_code == HTTP_OK:
            data = resp.json()
            async with lock:
                cache[legislatura_id] = data
            return data
    except Exception as e:
        logger.warning("legislatura_fetch_failed", legislatura_id=legislatura_id, error=str(e))

    async with lock:
        cache[legislatura_id] = None
    return None


async def ingest_mandatos(
    job_repo: IngestionJobRepository,
    quarantine_repo: QuarantineRepository,
    job: IngestionJob,
    shared_semaphore: asyncio.Semaphore | None = None,
) -> IngestionJob:
    """Ingest mandatos from Câmara CSV download (parallelized).

    Uses the deputados.csv file from dadosabertos.camara.leg.br which contains
    idLegislaturaInicial and idLegislaturaFinal columns representing parliamentary
    terms, since the API endpoint /deputados/{id}/mandatos returns HTTP 405.

    A dedicated semaphore (size 20) is used for legislatura API calls to avoid
    competing with the shared semaphore for other phases.
    """
    inserted = 0
    failed = 0

    df = await _download_deputados_csv()

    save_raw(
        DataSource.CAMARA.value,
        IngestionDataset.MANDATOS.value,
        date.today(),
        "csv",
        {"row_count": len(df)},
    )

    mandatos_sem = asyncio.Semaphore(MANDATOS_SEMAPHORE_SIZE)
    legislatura_cache: dict[int, dict[str, Any] | None] = {}
    legislatura_lock = asyncio.Lock()

    async with httpx.AsyncClient(timeout=30.0) as client:

        async def process_deputado(row: pd.Series) -> tuple[int, int]:
            nonlocal inserted, failed
            async with mandatos_sem:
                try:
                    uri: str = row["uri"]
                    dep_id = int(uri.rsplit("/", 1)[-1])
                    leg_inicial = int(row["idLegislaturaInicial"])
                    leg_final = int(row["idLegislaturaFinal"])

                    leg_inicial_info = await _fetch_legislatura_info(
                        client, leg_inicial, legislatura_cache, legislatura_lock
                    )
                    leg_final_info = await _fetch_legislatura_info(
                        client, leg_final, legislatura_cache, legislatura_lock
                    )

                    mandato_data = {
                        "deputado_id": dep_id,
                        "idLegislaturaInicial": leg_inicial,
                        "idLegislaturaFinal": leg_final,
                    }
                    if leg_inicial_info:
                        dados = leg_inicial_info.get("dados", {})
                        mandato_data["dataInicio"] = dados.get("dataInicio")
                    if leg_final_info:
                        dados = leg_final_info.get("dados", {})
                        mandato_data["dataFim"] = dados.get("dataFim")

                    save_raw(
                        DataSource.CAMARA.value,
                        IngestionDataset.MANDATOS.value,
                        date.today(),
                        dep_id,
                        mandato_data,
                    )
                    inserted += 1
                    return 1, 0
                except Exception as e:
                    logger.error(
                        "mandato_process_failed",
                        uri=row.get("uri"),
                        error=str(e),
                    )
                    failed += 1
                    quarantine = QuarantineRecord(
                        data_source=DataSource.CAMARA,
                        dataset=IngestionDataset.MANDATOS,
                        raw_data=row.to_dict(),
                        errors=[str(e)],
                    )
                    save_quarantine(
                        DataSource.CAMARA.value,
                        IngestionDataset.MANDATOS.value,
                        date.today(),
                        [quarantine.raw_data],
                    )
                    return 0, 1

        await asyncio.gather(*[process_deputado(row) for _, row in df.iterrows()])

    job.mark_completed(inserted=inserted, updated=0, failed=failed)
    return await job_repo.save(job)
