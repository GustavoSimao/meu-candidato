"""Shared kernel configuration - application settings."""

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        case_sensitive=False,
        extra="ignore",
    )

    database_url: str | None = None
    environment: str = "development"
    api_host: str = "0.0.0.0"
    api_port: int = 8000

    camara_base_url: str = "https://dadosabertos.camara.leg.br"
    senado_base_url: str = "https://legis.senado.leg.br"
    tse_base_url: str = "https://cdn.tse.jus.br"

    ingestion_schedule_cron: str = "0 3 * * *"
    ingestion_batch_size: int = 1000
    ingestion_timeout_seconds: int = 300
    ingestion_job_timeout_seconds: int = 1800
    quarantine_threshold_pct: int = 5
    tse_election_year: str = "auto"
    worker_count: int = 1
    worker_poll_interval: int = 30
    worker_heartbeat_interval: int = 60
    tz: str = "America/Sao_Paulo"


settings = Settings()

def get_database_url() -> str:
    """Retorna DATABASE_URL requerido, levantando erro claro se não configurado."""
    url = settings.database_url
    if url is None:
        raise RuntimeError(
            "DATABASE_URL environment variable is required. "
            "Set it via Fly.io secrets: flyctl secrets set DATABASE_URL=<your-database-url>"
        )
    return url
