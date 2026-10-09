"""Tests for ingestion domain entities and value objects."""

from datetime import date, datetime
import pytest

from app.ingestion.domain.entities import (
    DataSource,
    IngestionDataset,
    IngestionJob,
    IngestionStatus,
    QuarantineRecord,
)
from app.ingestion.domain.value_objects import (
    RateLimitConfig,
    RetryConfig,
    TimeoutConfig,
    BatchConfig,
)


class TestDataSourceEnum:
    def test_all_values(self):
        values = [v.value for v in DataSource]
        assert "camara" in values
        assert "senado" in values
        assert "tse" in values


class TestIngestionDatasetEnum:
    def test_all_values(self):
        values = [v.value for v in IngestionDataset]
        assert "deputados" in values
        assert "mandatos" in values
        assert "proposicoes" in values
        assert "votacoes" in values
        assert "votos" in values
        assert "despesas" in values


class TestIngestionStatusEnum:
    def test_all_values(self):
        values = [v.value for v in IngestionStatus]
        assert "pending" in values
        assert "running" in values
        assert "completed" in values
        assert "failed" in values
        assert "partial" in values


class TestIngestionJobEntity:
    def test_create_job(self):
        job = IngestionJob(
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
        )
        assert job.data_source == DataSource.CAMARA
        assert job.dataset == IngestionDataset.DEPUTADOS
        assert job.status == IngestionStatus.PENDING

    def test_mark_running(self):
        job = IngestionJob(
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
        )
        job.mark_running()
        assert job.status == IngestionStatus.RUNNING
        assert job.started_at is not None

    def test_mark_completed(self):
        job = IngestionJob(
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
        )
        job.mark_running()
        job.mark_completed(inserted=10, updated=5, failed=0)
        assert job.status == IngestionStatus.COMPLETED
        assert job.completed_at is not None
        assert job.records_inserted == 10
        assert job.records_updated == 5
        assert job.records_failed == 0
        assert job.records_processed == 15

    def test_mark_failed(self):
        job = IngestionJob(
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
        )
        job.mark_running()
        job.mark_failed("API timeout")
        assert job.status == IngestionStatus.FAILED
        assert job.completed_at is not None
        assert job.error_message == "API timeout"

    def test_mark_partial(self):
        job = IngestionJob(
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
        )
        job.mark_running()
        job.mark_partial(inserted=8, updated=2, failed=3, error="Some records failed")
        assert job.status == IngestionStatus.PARTIAL
        assert job.completed_at is not None
        assert job.records_inserted == 8
        assert job.records_updated == 2
        assert job.records_failed == 3
        assert job.records_processed == 13
        assert job.error_message == "Some records failed"


class TestQuarantineRecordEntity:
    def test_create_quarantine_record(self):
        record = QuarantineRecord(
            data_source=DataSource.CAMARA,
            dataset=IngestionDataset.DEPUTADOS,
            raw_data={"id": 1, "name": "Test"},
            errors=["Invalid CPF"],
        )
        assert record.data_source == DataSource.CAMARA
        assert record.dataset == IngestionDataset.DEPUTADOS
        assert record.raw_data == {"id": 1, "name": "Test"}
        assert record.errors == ["Invalid CPF"]
        assert record.resolved is False
        assert record.received_at is not None


class TestRateLimitConfigValueObject:
    def test_default_values(self):
        config = RateLimitConfig()
        assert config.requests_per_second == 30.0
        assert config.burst_limit == 10

    def test_delay_seconds(self):
        config = RateLimitConfig(requests_per_second=10.0)
        assert config.delay_seconds == 0.1


class TestRetryConfigValueObject:
    def test_default_values(self):
        config = RetryConfig()
        assert config.max_attempts == 3
        assert config.initial_wait_seconds == 1.0
        assert config.max_wait_seconds == 30.0
        assert config.exponential_base == 2.0


class TestTimeoutConfigValueObject:
    def test_default_values(self):
        config = TimeoutConfig()
        assert config.total_seconds == 30.0
        assert config.connect_seconds == 10.0


class TestBatchConfigValueObject:
    def test_default_values(self):
        config = BatchConfig()
        assert config.batch_size == 1000
        assert config.max_parallel == 5