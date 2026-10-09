"""Financial API - public exports."""

from app.financial.api.router import router as financial_router

__all__ = ["financial_router"]