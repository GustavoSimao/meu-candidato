"""Tests for ingestion infrastructure mappers."""

from datetime import date, datetime

import pytest

from app.ingestion.domain.entities import (
    DataSource,
    IngestionDataset,
    IngestionJob,
    IngestionStatus,
    QuarantineRecord,
)
from app.ingestion.infrastructure.models import IngestionJobORM, QuarantineRecordORM
from app.ingestion.infrastructure.mappers import (
    map_ingestion_job_orm_to_domain,
    map_ingestion_job_domain_to_orm,
    map_ingestion_job_domain_to_dto,
    map_ingestion_job_domain_to_detail_dto,
    map_quarantine_record_orm_to_domain,
    map_quarantine_record_domain_to_orm,
    map_quarantine_record_domain_to_dto,
    map_ingestion_job_create_dto_to_domain,
    map_quarantine_record_create_dto_to_domain,
)
from app.ingestion.application.dtos import (
    IngestionJobDTO,
    IngestionJobDetailDTO,
    QuarantineRecordDTO,
)


class TestIngestionJobMappers:
    def test_orm_to_domain(self):
        orm = IngestionJobORM(
            id=1,
            data_source="camara",
            dataset="deputados",
            status="completed",
            started_at=datetime(2024, 1, 1, 10, 0),
            completed_at=datetime(2024, 1, 1, 11, 0),
            records_processed=100,
            records_inserted=80,
            records_updated=15,
            records_failed=5,
            error_message=None,
            metadata={"key": "value"},
        )
        domain = map_ingestion_job_orm_to_domain(orm)
        assert domain.id == 1
        assert domain.data_source == DataSource.CAMARA
        assert domain.dataset == IngestionDataset.DEPUTADOS
        assert domain.status == IngestionStatus.COMPLETED
        assert domain.started_at == datetime(2024, 1, 1, 10, 0)
        assert domain.completed_at == datetime(2024, 1, 1, 11, 0)
        assert domain.records_processed == 100
        assert domain.records_inserted == 80
        assert domain.records_updated == 15
        assert domain.records_failed == 5
        assert domain.metadata == {"key": "value"}

    def test_domain_to_orm(self):
        domain = IngestionJob(
            id=1,
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
            status=IngestionStatus.COMPLETED,
            started_at=datetime(2024, 1, 1, 10, 0),
            completed_at=datetime(2024, 1, 1, 11, 0),
            records_processed=100,
            records_inserted=80,
            records_updated=15,
            records_failed=5,
            error_message=None,
            metadata={"key": "value"},
        )
        orm = map_ingestion_job_domain_to_orm(domain)
        assert orm.id == 1
        assert orm.data_source == "camara"
        assert orm.dataset == "deputados"
        assert orm.status == "completed"
        assert orm.started_at == datetime(2024, 1, 1, 10, 0)
        assert orm.completed_at == datetime(2024, 1, 1, 11, 0)
        assert orm.records_processed == 100
        assert orm.records_inserted == 80
        assert orm.records_updated == 15
        assert orm.records_failed == 5
        assert orm.metadata == {"key": "value"}

    def test_domain_to_dto(self):
        domain = IngestionJob(
            id=1,
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
            status=IngestionStatus.COMPLETED,
            started_at=datetime(2024, 1, 1, 10, 0),
            completed_at=datetime(2024, 1, 1, 11, 0),
            records_processed=100,
            records_inserted=80,
            records_updated=15,
            records_failed=5,
        )
        dto = map_ingestion_job_domain_to_dto(domain)
        assert dto.id == 1
        assert dto.data_source == "camara"
        assert dto.dataset == "deputados"
        assert dto.status == "completed"
        assert dto.records_processed == 100
        assert dto.records_inserted == 80
        assert dto.records_updated == 15
        assert dto.records_failed == 5

    def test_domain_to_detail_dto(self):
        domain = IngestionJob(
            id=1,
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
            status=IngestionStatus.COMPLETED,
            started_at=datetime(2024, 1, 1, 10, 0),
            completed_at=datetime(2024, 1, 1, 11, 0),
            records_processed=100,
            records_inserted=80,
            records_updated=15,
            records_failed=5,
            error_message="Some error",
            metadata={"key": "value"},
        )
        dto = map_ingestion_job_domain_to_detail_dto(domain)
        assert dto.id == 1
        assert dto.error_message == "Some error"
        assert dto.metadata == {"key": "value"}

    def test_create_dto_to_domain(self):
        dto = IngestionJobDTO(
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
        )
        data = map_ingestion_job_create_dto_to_domain(dto)
        assert data["data_source"] == DataSource.CAMARA
        assert data["dataset"] == IngestionDataset.DEPUTADOS
        assert "id" not in data


class TestQuarantineRecordMappers:
    def test_orm_to_domain(self):
        orm = QuarantineRecordORM(
            id=1,
            data_source="camara",
            dataset="deputados",
            raw_data={"id": 1, "name": "Test"},
            errors=["Invalid CPF"],
            received_at=datetime(2024, 1, 1, 10, 0),
            resolved=0,
        )
        domain = map_quarantine_record_orm_to_domain(orm)
        assert domain.id == 1
        assert domain.data_source == DataSource.CAMARA
        assert domain.dataset == IngestionDataset.DEPUTADOS
        assert domain.raw_data == {"id": 1, "name": "Test"}
        assert domain.errors == ["Invalid CPF"]
        assert domain.received_at == datetime(2024, 1, 1, 10, 0)
        assert domain.resolved is False

    def test_domain_to_orm(self):
        domain = QuarantineRecord(
            id=1,
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
            raw_data={"id": 1, "name": "Test"},
            errors=["Invalid CPF"],
            received_at=datetime(2024, 1, 1, 10, 0),
            resolved=False,
        )
        orm = map_quarantine_record_domain_to_orm(domain)
        assert orm.id == 1
        assert orm.data_source == "camara"
        assert orm.dataset == "deputados"
        assert orm.raw_data == {"id": 1, "name": "Test"}
        assert orm.errors == ["Invalid CPF"]
        assert orm.received_at == datetime(2024, 1, 1, 10, 0)
        assert orm.resolved == 0

    def test_domain_to_dto(self):
        domain = QuarantineRecord(
            id=1,
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
            raw_data={"id": 1, "name": "Test"},
            errors=["Invalid CPF"],
            received_at=datetime(2024, 1, 1, 10, 0),
            resolved=False,
        )
        dto = map_quarantine_record_domain_to_dto(domain)
        assert dto.id == 1
        assert dto.data_source == "camara"
        assert dto.dataset == "deputados"
        assert dto.raw_data == {"id": 1, "name": "Test"}
        assert dto.errors == ["Invalid CPF"]
        assert dto.received_at == datetime(2024, 1, 1, 10, 0)
        assert dto.resolved is False

    def test_create_dto_to_domain(self):
        dto = QuarantineRecordDTO(
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
            raw_data={"id": 1, "name": "Test"},
            errors=["Invalid CPF"],
        )
        data = map_quarantine_record_create_dto_to_domain(dto)
        assert data["data_source"] == DataSource.CAMARA
        assert data["dataset"] == IngestionDataset.DEPUTADOS
        assert data["raw_data"] == {"id": 1, "name": "Test"}
        assert data["errors"] == ["Invalid CPF"]
        assert "id" not in data