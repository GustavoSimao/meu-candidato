"""Tests for ingestion value objects."""

import pytest

from app.ingestion.domain.value_objects import (
    RateLimitConfig,
    RetryConfig,
    TimeoutConfig,
    BatchConfig,
)


class TestRateLimitConfig:
    def test_default_config(self):
        config = RateLimitConfig()
        assert config.requests_per_second == 30.0
        assert config.burst_limit == 10

    def test_custom_config(self):
        config = RateLimitConfig(requests_per_second=10.0, burst_limit=5)
        assert config.requests_per_second == 10.0
        assert config.burst_limit == 5

    def test_delay_seconds_property(self):
        config = RateLimitConfig(requests_per_second=20.0)
        assert config.delay_seconds == 0.05


class TestRetryConfig:
    def test_default_config(self):
        config = RetryConfig()
        assert config.max_attempts == 3
        assert config.initial_wait_seconds == 1.0
        assert config.max_wait_seconds == 30.0
        assert config.exponential_base == 2.0

    def test_custom_config(self):
        config = RetryConfig(
            max_attempts=5,
            initial_wait_seconds=2.0,
            max_wait_seconds=60.0,
            exponential_base=3.0,
        )
        assert config.max_attempts == 5
        assert config.initial_wait_seconds == 2.0
        assert config.max_wait_seconds == 60.0
        assert config.exponential_base == 3.0


class TestTimeoutConfig:
    def test_default_config(self):
        config = TimeoutConfig()
        assert config.total_seconds == 30.0
        assert config.connect_seconds == 10.0

    def test_custom_config(self):
        config = TimeoutConfig(total_seconds=60.0, connect_seconds=15.0)
        assert config.total_seconds == 60.0
        assert config.connect_seconds == 15.0


class TestBatchConfig:
    def test_default_config(self):
        config = BatchConfig()
        assert config.batch_size == 1000
        assert config.max_parallel == 5

    def test_custom_config(self):
        config = BatchConfig(batch_size=500, max_parallel=3)
        assert config.batch_size == 500
        assert config.max_parallel == 3