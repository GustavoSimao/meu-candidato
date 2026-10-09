"""Legislative Activity API - public exports."""

from app.legislative_activity.api.router import router as legislative_router

__all__ = ["legislative_router"]