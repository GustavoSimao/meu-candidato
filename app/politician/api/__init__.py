"""Politician API - public exports."""

from app.politician.api.router import router as politician_router

__all__ = ["politician_router"]
