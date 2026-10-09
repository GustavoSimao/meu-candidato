"""Financial API dependencies."""

from datetime import date
from typing import Annotated

from fastapi import Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession

from app.financial.application.filters import CampaignFinanceFilterDTO, ExpenseFilterDTO
from app.financial.application.ports import CampaignFinanceRepository, ExpenseRepository
from app.financial.application.services import CampaignFinanceService, ExpenseService
from app.financial.infrastructure.repository import SQLAlchemyCampaignFinanceRepository, SQLAlchemyExpenseRepository
from app.shared.kernel.database import get_session


def expense_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    politician_id: int | None = Query(None),
    expense_type: str | None = Query(None),
    year: int | None = Query(None),
    month: int | None = Query(None, ge=1, le=12),
    expense_date_from: date | None = Query(None),
    expense_date_to: date | None = Query(None),
) -> ExpenseFilterDTO:
    return ExpenseFilterDTO(
        page=page,
        per_page=per_page,
        politician_id=politician_id,
        expense_type=expense_type,
        year=year,
        month=month,
        expense_date_from=expense_date_from,
        expense_date_to=expense_date_to,
    )


def campaign_finance_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    politician_id: int | None = Query(None),
    election_year: int | None = Query(None),
    election_type: str | None = Query(None),
    donor_type: str | None = Query(None),
) -> CampaignFinanceFilterDTO:
    return CampaignFinanceFilterDTO(
        page=page,
        per_page=per_page,
        politician_id=politician_id,
        election_year=election_year,
        election_type=election_type,
        donor_type=donor_type,
    )


async def expense_repository(session: Annotated[AsyncSession, Depends(get_session)]) -> ExpenseRepository:
    return SQLAlchemyExpenseRepository(session)


async def campaign_finance_repository(session: Annotated[AsyncSession, Depends(get_session)]) -> CampaignFinanceRepository:
    return SQLAlchemyCampaignFinanceRepository(session)


async def expense_service(repository: Annotated[ExpenseRepository, Depends(expense_repository)]) -> ExpenseService:
    return ExpenseService(repository)


async def campaign_finance_service(repository: Annotated[CampaignFinanceRepository, Depends(campaign_finance_repository)]) -> CampaignFinanceService:
    return CampaignFinanceService(repository)