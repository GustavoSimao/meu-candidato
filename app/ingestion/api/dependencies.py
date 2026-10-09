"""Ingestion API dependencies."""

from typing import Annotated

from fastapi import Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession

from app.ingestion.application.filters import IngestionJobFilterDTO, QuarantineRecordFilterDTO
from app.ingestion.application.ports import IngestionJobRepository, QuarantineRepository
from app.ingestion.application.services import IngestionJobService, QuarantineService
from app.ingestion.infrastructure.repository import (
    SQLAlchemyIngestionJobRepository,
    SQLAlchemyQuarantineRepository,
)
from app.shared.kernel.database import get_session


def ingestion_job_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    data_source: str | None = Query(None),
    dataset: str | None = Query(None),
    status: str | None = Query(None),
) -> IngestionJobFilterDTO:
    return IngestionJobFilterDTO(
        page=page,
        per_page=per_page,
        data_source=data_source,
        dataset=dataset,
        status=status,
    )


def quarantine_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    data_source: str | None = Query(None),
    dataset: str | None = Query(None),
    resolved: bool | None = Query(None),
) -> QuarantineRecordFilterDTO:
    return QuarantineRecordFilterDTO(
        page=page,
        per_page=per_page,
        data_source=data_source,
        dataset=dataset,
        resolved=resolved,
    )


async def ingestion_job_repository(
    session: Annotated[AsyncSession, Depends(get_session)],
) -> IngestionJobRepository:
    return SQLAlchemyIngestionJobRepository(session)


async def quarantine_repository(
    session: Annotated[AsyncSession, Depends(get_session)],
) -> QuarantineRepository:
    return SQLAlchemyQuarantineRepository(session)


async def ingestion_job_service(
    repository: Annotated[IngestionJobRepository, Depends(ingestion_job_repository)],
) -> IngestionJobService:
    return IngestionJobService(repository)


async def quarantine_service(
    repository: Annotated[QuarantineRepository, Depends(quarantine_repository)],
) -> QuarantineService:
    return QuarantineService(repository)