"""Main ingestion worker entry point."""

import asyncio

import structlog

from app.ingestion.domain.entities import (
    DataSource,
    IngestionDataset,
    IngestionJob,
    IngestionStatus,
)
from app.ingestion.infrastructure.repository import (
    SQLAlchemyIngestionJobRepository,
    SQLAlchemyQuarantineRepository,
)
from app.shared.kernel.database import async_session_maker
from app.workers.ingest_deputados import ingest_deputados
from app.workers.ingest_despesas import ingest_despesas
from app.workers.ingest_mandatos import ingest_mandatos
from app.workers.ingest_proposicoes import ingest_proposicoes

structlog.configure(
    processors=[
        structlog.processors.TimeStamper(fmt="iso"),
        structlog.processors.add_log_level,
        structlog.processors.JSONRenderer(),
    ]
)

logger = structlog.get_logger(__name__)

GLOBAL_SEMAPHORE_SIZE = 15


async def _run_phase(
    phase_func,
    dataset: IngestionDataset,
    shared_semaphore: asyncio.Semaphore,
    **kwargs,
) -> None:
    """Run a single ingestion phase with its own session and job."""
    async with async_session_maker() as session:
        job_repo = SQLAlchemyIngestionJobRepository(session)
        quarantine_repo = SQLAlchemyQuarantineRepository(session)

        job = IngestionJob(
            data_source=DataSource.CAMARA,
            dataset=dataset,
            status=IngestionStatus.RUNNING,
        )
        job.mark_running()
        job = await job_repo.save(job)

        try:
            job = await phase_func(
                job_repo, quarantine_repo, job, shared_semaphore=shared_semaphore, **kwargs
            )
            await session.commit()
        except Exception as e:
            logger.error("phase_failed", dataset=dataset.value, error=str(e))
            job.mark_failed(str(e))
            await job_repo.save(job)
            await session.commit()
            raise


async def run_ingestion() -> None:
    """Run all ingestion phases concurrently."""
    logger.info("ingestion_started")

    shared_semaphore = asyncio.Semaphore(GLOBAL_SEMAPHORE_SIZE)

    await asyncio.gather(
        _run_phase(ingest_deputados, IngestionDataset.DEPUTADOS, shared_semaphore),
        _run_phase(ingest_proposicoes, IngestionDataset.PROPOSICOES, shared_semaphore),
        _run_phase(ingest_despesas, IngestionDataset.DESPESAS, shared_semaphore),
        _run_phase(ingest_mandatos, IngestionDataset.MANDATOS, shared_semaphore),
        return_exceptions=True,
    )

    logger.info("ingestion_all_phases_completed")


async def run_single_dataset(dataset: str, **kwargs) -> None:
    """Run ingestion for a single dataset."""
    logger.info("single_dataset_start", dataset=dataset)

    dataset_enum = IngestionDataset(dataset)
    shared_semaphore = asyncio.Semaphore(GLOBAL_SEMAPHORE_SIZE)

    phase_map = {
        "deputados": ingest_deputados,
        "proposicoes": ingest_proposicoes,
        "despesas": ingest_despesas,
        "mandatos": ingest_mandatos,
    }

    if dataset not in phase_map:
        raise ValueError(f"Unknown dataset: {dataset}")

    await _run_phase(phase_map[dataset], dataset_enum, shared_semaphore, **kwargs)

    logger.info("single_dataset_completed", dataset=dataset)


if __name__ == "__main__":
    import sys

    if sys.platform == "win32":
        asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())

    if len(sys.argv) > 1:
        dataset = sys.argv[1]
        asyncio.run(run_single_dataset(dataset))
    else:
        asyncio.run(run_ingestion())
