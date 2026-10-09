"""Ingestion infrastructure mappers - conversion between Domain, ORM, and DTO."""

from datetime import datetime
from typing import Any

from app.ingestion.domain.entities import (
    DataSource,
    IngestionDataset,
    IngestionJob,
    IngestionStatus,
    QuarantineRecord,
)
from app.ingestion.infrastructure.models import IngestionJobORM, QuarantineRecordORM
from app.ingestion.application.dtos import (
    IngestionJobDTO,
    IngestionJobDetailDTO,
    QuarantineRecordDTO,
)


def map_ingestion_job_orm_to_domain(orm: IngestionJobORM) -> IngestionJob:
    return IngestionJob(
        id=orm.id,
        data_source=DataSource(orm.data_source),
        dataset=IngestionDataset(orm.dataset),
        status=IngestionStatus(orm.status),
        started_at=orm.started_at,
        completed_at=orm.completed_at,
        records_processed=orm.records_processed,
        records_inserted=orm.records_inserted,
        records_updated=orm.records_updated,
        records_failed=orm.records_failed,
        error_message=orm.error_message,
        metadata=orm.metadata or {},
    )


def map_ingestion_job_domain_to_orm(domain: IngestionJob) -> IngestionJobORM:
    return IngestionJobORM(
        id=domain.id,
        data_source=domain.data_source.value,
        dataset=domain.dataset.value,
        status=domain.status.value,
        started_at=domain.started_at,
        completed_at=domain.completed_at,
        records_processed=domain.records_processed,
        records_inserted=domain.records_inserted,
        records_updated=domain.records_updated,
        records_failed=domain.records_failed,
        error_message=domain.error_message,
        metadata=domain.metadata,
    )


def map_ingestion_job_domain_to_dto(domain: IngestionJob) -> IngestionJobDTO:
    return IngestionJobDTO(
        id=domain.id,
        data_source=domain.data_source.value,
        dataset=domain.dataset.value,
        status=domain.status.value,
        started_at=domain.started_at,
        completed_at=domain.completed_at,
        records_processed=domain.records_processed,
        records_inserted=domain.records_inserted,
        records_updated=domain.records_updated,
        records_failed=domain.records_failed,
    )


def map_ingestion_job_domain_to_detail_dto(domain: IngestionJob) -> IngestionJobDetailDTO:
    return IngestionJobDetailDTO(
        id=domain.id,
        data_source=domain.data_source.value,
        dataset=domain.dataset.value,
        status=domain.status.value,
        started_at=domain.started_at,
        completed_at=domain.completed_at,
        records_processed=domain.records_processed,
        records_inserted=domain.records_inserted,
        records_updated=domain.records_updated,
        records_failed=domain.records_failed,
        error_message=domain.error_message,
        metadata=domain.metadata,
    )


def map_quarantine_record_orm_to_domain(orm: QuarantineRecordORM) -> QuarantineRecord:
    return QuarantineRecord(
        id=orm.id,
        data_source=DataSource(orm.data_source),
        dataset=IngestionDataset(orm.dataset),
        raw_data=orm.raw_data or {},
        errors=orm.errors or [],
        received_at=orm.received_at,
        resolved=bool(orm.resolved),
    )


def map_quarantine_record_domain_to_orm(domain: QuarantineRecord) -> QuarantineRecordORM:
    return QuarantineRecordORM(
        id=domain.id,
        data_source=domain.data_source.value,
        dataset=domain.dataset.value,
        raw_data=domain.raw_data,
        errors=domain.errors,
        received_at=domain.received_at or datetime.now(),
        resolved=1 if domain.resolved else 0,
    )


def map_quarantine_record_domain_to_dto(domain: QuarantineRecord) -> QuarantineRecordDTO:
    return QuarantineRecordDTO(
        id=domain.id,
        data_source=domain.data_source.value,
        dataset=domain.dataset.value,
        raw_data=domain.raw_data,
        errors=domain.errors,
        received_at=domain.received_at,
        resolved=domain.resolved,
    )


def map_ingestion_job_create_dto_to_domain(data: IngestionJobDTO) -> dict[str, Any]:
    return data.model_dump(exclude_none=True, exclude={"id"})


def map_quarantine_record_create_dto_to_domain(data: QuarantineRecordDTO) -> dict[str, Any]:
    return data.model_dump(exclude_none=True, exclude={"id"})