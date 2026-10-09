"""Financial API router."""

from datetime import date
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status

from app.financial.api.dependencies import (
    campaign_finance_filter,
    campaign_finance_service,
    expense_filter,
    expense_service,
)
from app.financial.application.dtos import (
    CampaignFinanceCreateDTO,
    CampaignFinanceDetailDTO,
    CampaignFinanceListDTO,
    CampaignFinanceSummaryDTO,
    CampaignFinanceUpdateDTO,
    ExpenseDTO,
    ExpenseDetailDTO,
    ExpenseListDTO,
    ExpenseSummaryDTO,
)
from app.financial.application.filters import CampaignFinanceFilterDTO, ExpenseFilterDTO
from app.financial.application.services import CampaignFinanceService, ExpenseService
from app.financial.domain.exceptions import CampaignFinanceNotFoundError, ExpenseNotFoundError

router = APIRouter(prefix="/financial", tags=["financial"])


# Expenses endpoints
@router.post("/expenses", response_model=ExpenseDetailDTO, status_code=status.HTTP_201_CREATED)
async def create_expense(
    data: ExpenseDTO,
    service: Annotated[ExpenseService, Depends(expense_service)],
) -> ExpenseDetailDTO:
    return await service.create(data)


@router.get("/expenses", response_model=ExpenseListDTO)
async def list_expenses(
    filters: Annotated[ExpenseFilterDTO, Depends(expense_filter)],
    service: Annotated[ExpenseService, Depends(expense_service)],
) -> ExpenseListDTO:
    return await service.list(filters)


@router.get("/expenses/summary", response_model=ExpenseSummaryDTO)
async def get_expenses_summary(
    service: Annotated[ExpenseService, Depends(expense_service)],
    politician_id: Annotated[int, Query()],
    year: int | None = None,
) -> ExpenseSummaryDTO:
    return await service.get_summary(politician_id, year)


@router.get("/expenses/{expense_id}", response_model=ExpenseDetailDTO)
async def get_expense(
    expense_id: int,
    service: Annotated[ExpenseService, Depends(expense_service)],
) -> ExpenseDetailDTO:
    try:
        return await service.get(expense_id)
    except ExpenseNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Expense not found")


@router.delete("/expenses/{expense_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_expense(
    expense_id: int,
    service: Annotated[ExpenseService, Depends(expense_service)],
) -> None:
    deleted = await service.delete(expense_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Expense not found")


# Campaign Finance endpoints
@router.post("/campaigns", response_model=CampaignFinanceDetailDTO, status_code=status.HTTP_201_CREATED)
async def create_campaign_finance(
    data: CampaignFinanceCreateDTO,
    service: Annotated[CampaignFinanceService, Depends(campaign_finance_service)],
) -> CampaignFinanceDetailDTO:
    return await service.create(data)


@router.get("/campaigns", response_model=CampaignFinanceListDTO)
async def list_campaigns(
    filters: Annotated[CampaignFinanceFilterDTO, Depends(campaign_finance_filter)],
    service: Annotated[CampaignFinanceService, Depends(campaign_finance_service)],
) -> CampaignFinanceListDTO:
    return await service.list(filters)


@router.get("/campaigns/summary", response_model=CampaignFinanceSummaryDTO)
async def get_campaigns_summary(
    service: Annotated[CampaignFinanceService, Depends(campaign_finance_service)],
    politician_id: Annotated[int, Query()],
    election_year: int | None = None,
) -> CampaignFinanceSummaryDTO:
    return await service.get_summary(politician_id, election_year)


@router.get("/campaigns/{finance_id}", response_model=CampaignFinanceDetailDTO)
async def get_campaign(
    finance_id: int,
    service: Annotated[CampaignFinanceService, Depends(campaign_finance_service)],
) -> CampaignFinanceDetailDTO:
    try:
        return await service.get(finance_id)
    except CampaignFinanceNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Campaign finance not found")


@router.patch("/campaigns/{finance_id}", response_model=CampaignFinanceDetailDTO)
async def update_campaign(
    finance_id: int,
    data: CampaignFinanceUpdateDTO,
    service: Annotated[CampaignFinanceService, Depends(campaign_finance_service)],
) -> CampaignFinanceDetailDTO:
    try:
        return await service.update(finance_id, data)
    except CampaignFinanceNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Campaign finance not found")


@router.delete("/campaigns/{finance_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_campaign(
    finance_id: int,
    service: Annotated[CampaignFinanceService, Depends(campaign_finance_service)],
) -> None:
    deleted = await service.delete(finance_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Campaign finance not found")