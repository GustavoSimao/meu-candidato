"""Tests for ingestion domain exceptions."""

import pytest

from app.ingestion.domain.exceptions import (
    IngestionError,
    RateLimitError,
    ExternalAPIError,
    QuarantineError,
    MappingError,
)


class TestIngestionError:
    def test_creation(self):
        error = IngestionError("Failed to ingest", "camara", "deputados")
        assert str(error) == "Failed to ingest"
        assert error.code == "INGESTION_ERROR"
        assert error.details["data_source"] == "camara"
        assert error.details["dataset"] == "deputados"
        assert error.data_source == "camara"
        assert error.dataset == "deputados"


class TestRateLimitError:
    def test_creation(self):
        error = RateLimitError("camara", 60.0)
        assert "Rate limit exceeded for camara" in str(error)
        assert error.retry_after == 60.0
        assert error.data_source == "camara"
        assert error.dataset == "rate_limit"


class TestExternalAPIError:
    def test_creation(self):
        error = ExternalAPIError("camara", 500, "Internal Server Error")
        assert "External API error from camara: 500" in str(error)
        assert error.status_code == 500
        assert error.data_source == "camara"
        assert error.dataset == "api_error"


class TestQuarantineError:
    def test_creation(self):
        error = QuarantineError("Failed to quarantine", record_id=42)
        assert str(error) == "Failed to quarantine"
        assert error.code == "QUARANTINE_ERROR"
        assert error.details["record_id"] == 42
        assert error.record_id == 42

    def test_creation_without_record_id(self):
        error = QuarantineError("Failed to quarantine")
        assert error.record_id is None
        assert error.details["record_id"] is None


class TestMappingError:
    def test_creation(self):
        error = MappingError("cpf", "123.456.789-00", "Invalid format")
        assert "Mapping error for field 'cpf'" in str(error)
        assert error.field == "cpf"
        assert error.value == "123.456.789-00"
        assert error.reason == "Invalid format"