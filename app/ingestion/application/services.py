"""Ingestion application services - use cases."""

from app.ingestion.application.dtos import (
    IngestionJobCreateDTO,
    IngestionJobDetailDTO,
    IngestionJobDTO,
    IngestionJobListDTO,
    IngestionJobUpdateDTO,
    IngestionSummaryDTO,
    QuarantineRecordDTO,
    QuarantineRecordListDTO,
)
from app.ingestion.application.filters import IngestionJobFilterDTO, QuarantineRecordFilterDTO
from app.ingestion.application.ports import IngestionJobRepository, QuarantineRepository
from app.ingestion.domain.entities import IngestionJob, IngestionStatus, QuarantineRecord
from app.ingestion.domain.exceptions import IngestionError
from app.ingestion.infrastructure.mappers import (
    map_ingestion_job_create_dto_to_domain,
    map_ingestion_job_domain_to_detail_dto,
    map_ingestion_job_domain_to_dto,
    map_quarantine_record_create_dto_to_domain,
    map_quarantine_record_domain_to_dto,
)


class IngestionJobService:
    def __init__(self, repository: IngestionJobRepository):
        self.repository = repository

    async def start_job(self, data: IngestionJobCreateDTO) -> IngestionJobDetailDTO:
        job = IngestionJob(
            data_source=data.data_source,
            dataset=data.dataset,
        )
        job.mark_running()
        saved = await self.repository.save(job)
        return map_ingestion_job_domain_to_detail_dto(saved)

    async def complete_job(
        self,
        job_id: int,
        inserted: int = 0,
        updated: int = 0,
        failed: int = 0,
        error: str = "",
    ) -> IngestionJobDetailDTO:
        job = await self.repository.get_by_id(job_id)
        if job is None:
            raise IngestionError("Job not found", "system", "job")

        if failed > 0 and (inserted > 0 or updated > 0):
            job.mark_partial(inserted, updated, failed, error)
        elif failed > 0:
            job.mark_failed(error)
        else:
            job.mark_completed(inserted, updated, failed)

        saved = await self.repository.save(job)
        return map_ingestion_job_domain_to_detail_dto(saved)

    async def get_job(self, job_id: int) -> IngestionJobDetailDTO:
        job = await self.repository.get_by_id(job_id)
        if job is None:
            raise IngestionError("Job not found", "system", "job")
        return map_ingestion_job_domain_to_detail_dto(job)

    async def list_jobs(self, filters: IngestionJobFilterDTO) -> IngestionJobListDTO:
        jobs, total = await self.repository.list(filters)
        items = [map_ingestion_job_domain_to_dto(j) for j in jobs]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return IngestionJobListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def get_summary(self) -> IngestionSummaryDTO:
        # This would need additional queries - placeholder
        return IngestionSummaryDTO(
            total_jobs=0,
            successful_jobs=0,
            failed_jobs=0,
            partial_jobs=0,
            total_records_processed=0,
            by_source={},
        )

    async def get_latest_job(self, data_source: str, dataset: str) -> IngestionJobDetailDTO:
        job = await self.repository.get_latest_by_source_dataset(data_source, dataset)
        if job is None:
            raise IngestionError(f"No job found for {data_source}/{dataset}", data_source, dataset)
        return map_ingestion_job_domain_to_detail_dto(job)


class QuarantineService:
    def __init__(self, repository: QuarantineRepository):
        self.repository = repository

    async def add_record(self, data: QuarantineRecordDTO) -> QuarantineRecordDTO:
        record = QuarantineRecord(
            data_source=data.data_source,
            dataset=data.dataset,
            raw_data=data.raw_data,
            errors=data.errors,
        )
        saved = await self.repository.save(record)
        return map_quarantine_record_domain_to_dto(saved)

    async def add_batch(self, records: list[QuarantineRecordDTO]) -> list[QuarantineRecordDTO]:
        domain_records = [
            QuarantineRecord(
                data_source=r.data_source,
                dataset=r.dataset,
                raw_data=r.raw_data,
                errors=r.errors,
            )
            for r in records
        ]
        saved = await self.repository.save_batch(domain_records)
        return [map_quarantine_record_domain_to_dto(r) for r in saved]

    async def get_record(self, record_id: int) -> QuarantineRecordDTO:
        record = await self.repository.get_by_id(record_id)
        if record is None:
            raise IngestionError("Quarantine record not found", "system", "quarantine")
        return map_quarantine_record_domain_to_dto(record)

    async def list_records(self, filters: QuarantineRecordFilterDTO) -> QuarantineRecordListDTO:
        records, total = await self.repository.list(filters)
        items = [map_quarantine_record_domain_to_dto(r) for r in records]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return QuarantineRecordListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def resolve_record(self, record_id: int) -> bool:
        return await self.repository.mark_resolved(record_id)

    async def delete_record(self, record_id: int) -> bool:
        return await self.repository.delete(record_id)