"""Storage utilities for raw data and quarantine records."""

import json
from datetime import date, datetime
from pathlib import Path
from typing import Any

import polars as pl
import structlog

logger = structlog.get_logger(__name__)

BASE_RAW_DIR = Path("data/raw")
BASE_QUARANTINE_DIR = Path("data/quarantine")


def _get_raw_path(source: str, dataset: str, ingestion_date: date, page: int | str) -> Path:
    """Get path for raw data file."""
    dir_path = BASE_RAW_DIR / source / dataset / ingestion_date.isoformat()
    dir_path.mkdir(parents=True, exist_ok=True)
    filename = f"{page:04d}.json" if isinstance(page, int) else f"{page}.json"
    return dir_path / filename


def _get_quarantine_path(source: str, dataset: str, ingestion_date: date) -> Path:
    """Get path for quarantine parquet file."""
    dir_path = BASE_QUARANTINE_DIR / source / dataset
    dir_path.mkdir(parents=True, exist_ok=True)
    return dir_path / f"{ingestion_date.isoformat()}.parquet"


def save_raw(source: str, dataset: str, ingestion_date: date, page: int | str, data: dict[str, Any]) -> Path:
    """Save raw API response as JSON."""
    path = _get_raw_path(source, dataset, ingestion_date, page)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    logger.info("raw_saved", path=str(path), source=source, dataset=dataset, page=page)
    return path


def save_quarantine(
    source: str,
    dataset: str,
    ingestion_date: date,
    records: list[dict[str, Any]],
) -> Path:
    """Save quarantined records as Parquet."""
    path = _get_quarantine_path(source, dataset, ingestion_date)

    # Add metadata columns
    for record in records:
        record.setdefault("_quarantine_received_at", datetime.now().isoformat())
        record.setdefault("_quarantine_source", source)
        record.setdefault("_quarantine_dataset", dataset)

    df = pl.DataFrame(records)

    # Append to existing file if exists
    if path.exists():
        existing = pl.read_parquet(path)
        df = pl.concat([existing, df])

    df.write_parquet(path)
    logger.info("quarantine_saved", path=str(path), count=len(records), source=source, dataset=dataset)
    return path


def load_quarantine(source: str, dataset: str, ingestion_date: date) -> pl.DataFrame | None:
    """Load quarantined records from Parquet."""
    path = _get_quarantine_path(source, dataset, ingestion_date)
    if path.exists():
        return pl.read_parquet(path)
    return None