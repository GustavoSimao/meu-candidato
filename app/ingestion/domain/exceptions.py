"""Ingestion domain exceptions."""

from app.shared.kernel.exceptions import DomainError, ValidationError


class IngestionError(DomainError):
    def __init__(self, message: str, data_source: str, dataset: str):
        super().__init__(
            message,
            code="INGESTION_ERROR",
            details={"data_source": data_source, "dataset": dataset},
        )
        self.data_source = data_source
        self.dataset = dataset


class RateLimitError(IngestionError):
    def __init__(self, data_source: str, retry_after: float):
        super().__init__(
            f"Rate limit exceeded for {data_source}, retry after {retry_after}s",
            data_source,
            "rate_limit",
        )
        self.retry_after = retry_after


class ExternalAPIError(IngestionError):
    def __init__(self, data_source: str, status_code: int, message: str):
        super().__init__(
            f"External API error from {data_source}: {status_code} - {message}",
            data_source,
            "api_error",
        )
        self.status_code = status_code


class QuarantineError(DomainError):
    def __init__(self, message: str, record_id: int | None = None):
        super().__init__(
            message,
            code="QUARANTINE_ERROR",
            details={"record_id": record_id},
        )
        self.record_id = record_id


class MappingError(ValidationError):
    def __init__(self, field: str, value: str, reason: str):
        super().__init__(
            f"Mapping error for field '{field}' with value '{value}': {reason}",
            field=field,
        )
        self.value = value
        self.reason = reason