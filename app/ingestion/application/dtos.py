"""Ingestion application DTOs - Pydantic schemas."""

from datetime import datetime
from typing import Optional

from pydantic import BaseModel, ConfigDict


class IngestionJobDTO(BaseModel):
    id: Optional[int] = None
    data_source: str
    dataset: str
    status: str
    started_at: Optional[datetime] = None
    completed_at: Optional[datetime] = None
    records_processed: int = 0
    records_inserted: int = 0
    records_updated: int = 0
    records_failed: int = 0

    model_config = ConfigDict(from_attributes=True)


class IngestionJobDetailDTO(IngestionJobDTO):
    error_message: Optional[str] = None
    metadata: dict = {}


class IngestionJobListDTO(BaseModel):
    items: list[IngestionJobDTO]
    total: int
    page: int
    per_page: int
    pages: int


class IngestionJobCreateDTO(BaseModel):
    data_source: str
    dataset: str


class IngestionJobUpdateDTO(BaseModel):
    status: Optional[str] = None
    started_at: Optional[datetime] = None
    completed_at: Optional[datetime] = None
    records_processed: Optional[int] = None
    records_inserted: Optional[int] = None
    records_updated: Optional[int] = None
    records_failed: Optional[int] = None
    error_message: Optional[str] = None
    metadata: Optional[dict] = None


class QuarantineRecordDTO(BaseModel):
    id: Optional[int] = None
    data_source: str
    dataset: str
    raw_data: dict
    errors: list[str]
    received_at: datetime
    resolved: bool = False

    model_config = ConfigDict(from_attributes=True)


class QuarantineRecordListDTO(BaseModel):
    items: list[QuarantineRecordDTO]
    total: int
    page: int
    per_page: int
    pages: int


class IngestionSummaryDTO(BaseModel):
    """Summary of recent ingestion runs."""

    total_jobs: int
    successful_jobs: int
    failed_jobs: int
    partial_jobs: int
    total_records_processed: int
    last_run: Optional[datetime] = None
    by_source: dict[str, int] = {}