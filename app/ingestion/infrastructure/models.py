"""Ingestion ORM models - SQLAlchemy models for persistence."""

from datetime import datetime

from sqlalchemy import Column, DateTime, ForeignKey, Index, Integer, String, Text
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.orm import relationship

from app.shared.kernel.database import Base


class IngestionJobORM(Base):
    __tablename__ = "ingestion_jobs"

    id = Column(Integer, primary_key=True, index=True)
    data_source = Column(String(50), nullable=False)
    dataset = Column(String(50), nullable=False)
    status = Column(String(20), nullable=False, default="pending")
    started_at = Column(DateTime)
    completed_at = Column(DateTime)
    records_processed = Column(Integer, default=0)
    records_inserted = Column(Integer, default=0)
    records_updated = Column(Integer, default=0)
    records_failed = Column(Integer, default=0)
    error_message = Column(Text)
    metadata = Column(JSONB, default=dict)
    created_at = Column(DateTime, default=datetime.now)

    __table_args__ = (
        Index("ix_ingestion_jobs_source_dataset", "data_source", "dataset"),
        Index("ix_ingestion_jobs_status", "status"),
        Index("ix_ingestion_jobs_created", "created_at"),
    )


class QuarantineRecordORM(Base):
    __tablename__ = "quarantine_records"

    id = Column(Integer, primary_key=True, index=True)
    data_source = Column(String(50), nullable=False)
    dataset = Column(String(50), nullable=False)
    raw_data = Column(JSONB, nullable=False)
    errors = Column(JSONB, nullable=False)
    received_at = Column(DateTime, default=datetime.now, nullable=False)
    resolved = Column(Integer, default=0)  # 0 = false, 1 = true

    __table_args__ = (
        Index("ix_quarantine_source_dataset", "data_source", "dataset"),
        Index("ix_quarantine_received", "received_at"),
    )