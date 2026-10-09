"""Ingestion API - public exports."""

from app.ingestion.api.router import router as ingestion_router

__all__ = ["ingestion_router"]