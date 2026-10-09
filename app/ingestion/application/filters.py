"""Ingestion application filter DTOs."""

from datetime import datetime
from typing import Optional

from pydantic import BaseModel, ConfigDict, Field


class IngestionJobFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    data_source: Optional[str] = None
    dataset: Optional[str] = None
    status: Optional[str] = None
    started_from: Optional[datetime] = None
    started_to: Optional[datetime] = None

    model_config = ConfigDict(extra="forbid")


class QuarantineRecordFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    data_source: Optional[str] = None
    dataset: Optional[str] = None
    resolved: Optional[bool] = None
    received_from: Optional[datetime] = None
    received_to: Optional[datetime] = None

    model_config = ConfigDict(extra="forbid")