"""Ingestion domain entities - pure Python domain models."""

from dataclasses import dataclass, field
from datetime import date, datetime
from enum import Enum
from typing import Optional


class DataSource(str, Enum):
    """External data sources."""

    CAMARA = "camara"
    SENADO = "senado"
    TSE = "tse"


class IngestionStatus(str, Enum):
    """Ingestion job status."""

    PENDING = "pending"
    RUNNING = "running"
    COMPLETED = "completed"
    FAILED = "failed"
    PARTIAL = "partial"


class IngestionDataset(str, Enum):
    """Types of datasets to ingest."""

    DEPUTADOS = "deputados"
    MANDATOS = "mandatos"
    PROPOSICOES = "proposicoes"
    VOTACOES = "votacoes"
    VOTOS = "votos"
    DESPESAS = "despesas"


@dataclass
class IngestionJob:
    """Domain entity for an ingestion job."""

    data_source: DataSource
    dataset: IngestionDataset
    status: IngestionStatus = IngestionStatus.PENDING
    started_at: Optional[datetime] = None
    completed_at: Optional[datetime] = None
    records_processed: int = 0
    records_inserted: int = 0
    records_updated: int = 0
    records_failed: int = 0
    error_message: Optional[str] = None
    metadata: dict = field(default_factory=dict)
    id: Optional[int] = None

    def mark_running(self):
        self.status = IngestionStatus.RUNNING
        self.started_at = datetime.now()

    def mark_completed(self, inserted: int = 0, updated: int = 0, failed: int = 0):
        self.status = IngestionStatus.COMPLETED
        self.completed_at = datetime.now()
        self.records_inserted = inserted
        self.records_updated = updated
        self.records_failed = failed
        self.records_processed = inserted + updated + failed

    def mark_failed(self, error: str):
        self.status = IngestionStatus.FAILED
        self.completed_at = datetime.now()
        self.error_message = error

    def mark_partial(self, inserted: int = 0, updated: int = 0, failed: int = 0, error: str = ""):
        self.status = IngestionStatus.PARTIAL
        self.completed_at = datetime.now()
        self.records_inserted = inserted
        self.records_updated = updated
        self.records_failed = failed
        self.records_processed = inserted + updated + failed
        self.error_message = error


@dataclass
class QuarantineRecord:
    """Domain entity for a quarantined record."""

    data_source: DataSource
    dataset: IngestionDataset
    raw_data: dict
    errors: list[str]
    received_at: datetime = field(default_factory=datetime.now)
    resolved: bool = False
    id: Optional[int] = None