"""Ingestion domain value objects."""

from dataclasses import dataclass


@dataclass(frozen=True)
class RateLimitConfig:
    """Rate limiting configuration."""

    requests_per_second: float = 30.0
    burst_limit: int = 10

    @property
    def delay_seconds(self) -> float:
        return 1.0 / self.requests_per_second


@dataclass(frozen=True)
class RetryConfig:
    """Retry configuration."""

    max_attempts: int = 3
    initial_wait_seconds: float = 1.0
    max_wait_seconds: float = 30.0
    exponential_base: float = 2.0


@dataclass(frozen=True)
class TimeoutConfig:
    """Timeout configuration."""

    total_seconds: float = 30.0
    connect_seconds: float = 10.0


@dataclass(frozen=True)
class BatchConfig:
    """Batch processing configuration."""

    batch_size: int = 1000
    max_parallel: int = 5