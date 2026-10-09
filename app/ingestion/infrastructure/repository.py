"""Ingestion infrastructure repository - SQLAlchemy implementations."""

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.ingestion.application.filters import IngestionJobFilterDTO, QuarantineRecordFilterDTO
from app.ingestion.application.ports import IngestionJobRepository, QuarantineRepository
from app.ingestion.domain.entities import (
    DataSource,
    IngestionDataset,
    IngestionJob,
    IngestionStatus,
    QuarantineRecord,
)
from app.ingestion.infrastructure.mappers import (
    map_ingestion_job_domain_to_orm,
    map_ingestion_job_orm_to_domain,
    map_quarantine_record_domain_to_orm,
    map_quarantine_record_orm_to_domain,
)
from app.ingestion.infrastructure.models import IngestionJobORM, QuarantineRecordORM


class SQLAlchemyIngestionJobRepository(IngestionJobRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, job: IngestionJob) -> IngestionJob:
        orm = map_ingestion_job_domain_to_orm(job)
        if orm.id is not None:
            orm = await self.session.merge(orm)
        else:
            self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_ingestion_job_orm_to_domain(orm)

    async def get_by_id(self, job_id: int) -> IngestionJob | None:
        query = select(IngestionJobORM).where(IngestionJobORM.id == job_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_ingestion_job_orm_to_domain(orm) if orm else None

    async def get_latest_by_source_dataset(
        self, data_source: str, dataset: str
    ) -> IngestionJob | None:
        query = (
            select(IngestionJobORM)
            .where(
                IngestionJobORM.data_source == data_source,
                IngestionJobORM.dataset == dataset,
            )
            .order_by(IngestionJobORM.created_at.desc())
            .limit(1)
        )
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_ingestion_job_orm_to_domain(orm) if orm else None

    async def list(self, filters: IngestionJobFilterDTO) -> tuple[list[IngestionJob], int]:
        query = select(IngestionJobORM)

        if filters.data_source:
            query = query.where(IngestionJobORM.data_source == filters.data_source)
        if filters.dataset:
            query = query.where(IngestionJobORM.dataset == filters.dataset)
        if filters.status:
            query = query.where(IngestionJobORM.status == filters.status)
        if filters.started_from:
            query = query.where(IngestionJobORM.started_at >= filters.started_from)
        if filters.started_to:
            query = query.where(IngestionJobORM.started_at <= filters.started_to)

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = (
            query.order_by(IngestionJobORM.created_at.desc())
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_ingestion_job_orm_to_domain(orm) for orm in orms], total

    async def delete(self, job_id: int) -> bool:
        query = select(IngestionJobORM).where(IngestionJobORM.id == job_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True


class SQLAlchemyQuarantineRepository(QuarantineRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, record: QuarantineRecord) -> QuarantineRecord:
        orm = map_quarantine_record_domain_to_orm(record)
        if orm.id is not None:
            orm = await self.session.merge(orm)
        else:
            self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_quarantine_record_orm_to_domain(orm)

    async def save_batch(self, records: list[QuarantineRecord]) -> list[QuarantineRecord]:
        orms = [map_quarantine_record_domain_to_orm(r) for r in records]
        self.session.add_all(orms)
        await self.session.flush()
        for orm in orms:
            await self.session.refresh(orm)
        return [map_quarantine_record_orm_to_domain(orm) for orm in orms]

    async def get_by_id(self, record_id: int) -> QuarantineRecord | None:
        query = select(QuarantineRecordORM).where(QuarantineRecordORM.id == record_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_quarantine_record_orm_to_domain(orm) if orm else None

    async def list(self, filters: QuarantineRecordFilterDTO) -> tuple[list[QuarantineRecord], int]:
        query = select(QuarantineRecordORM)

        if filters.data_source:
            query = query.where(QuarantineRecordORM.data_source == filters.data_source)
        if filters.dataset:
            query = query.where(QuarantineRecordORM.dataset == filters.dataset)
        if filters.resolved is not None:
            query = query.where(QuarantineRecordORM.resolved == (1 if filters.resolved else 0))
        if filters.received_from:
            query = query.where(QuarantineRecordORM.received_at >= filters.received_from)
        if filters.received_to:
            query = query.where(QuarantineRecordORM.received_at <= filters.received_to)

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = (
            query.order_by(QuarantineRecordORM.received_at.desc())
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_quarantine_record_orm_to_domain(orm) for orm in orms], total

    async def mark_resolved(self, record_id: int) -> bool:
        query = select(QuarantineRecordORM).where(QuarantineRecordORM.id == record_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        orm.resolved = 1
        await self.session.flush()
        return True

    async def delete(self, record_id: int) -> bool:
        query = select(QuarantineRecordORM).where(QuarantineRecordORM.id == record_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True