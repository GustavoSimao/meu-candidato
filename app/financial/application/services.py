"""Financial application services - use cases."""

from app.financial.application.dtos import (
    CampaignFinanceCreateDTO,
    CampaignFinanceDetailDTO,
    CampaignFinanceDTO,
    CampaignFinanceListDTO,
    CampaignFinanceSummaryDTO,
    CampaignFinanceUpdateDTO,
    ExpenseDTO,
    ExpenseDetailDTO,
    ExpenseListDTO,
    ExpenseSummaryDTO,
)
from app.financial.application.filters import CampaignFinanceFilterDTO, ExpenseFilterDTO
from app.financial.application.ports import CampaignFinanceRepository, ExpenseRepository
from app.financial.domain.entities import CampaignFinance, Expense
from app.financial.domain.exceptions import CampaignFinanceNotFoundError, ExpenseNotFoundError
from app.financial.infrastructure.mappers import (
    map_campaign_finance_create_dto_to_domain,
    map_campaign_finance_domain_to_detail_dto,
    map_campaign_finance_domain_to_dto,
    map_expense_create_dto_to_domain,
    map_expense_domain_to_detail_dto,
    map_expense_domain_to_dto,
)


class ExpenseService:
    def __init__(self, repository: ExpenseRepository):
        self.repository = repository

    async def create(self, data: ExpenseDTO) -> ExpenseDetailDTO:
        expense = Expense(**map_expense_create_dto_to_domain(data))
        saved = await self.repository.save(expense)
        return map_expense_domain_to_detail_dto(saved)

    async def get(self, expense_id: int) -> ExpenseDetailDTO:
        expense = await self.repository.get_by_id(expense_id)
        if expense is None:
            raise ExpenseNotFoundError(expense_id)
        return map_expense_domain_to_detail_dto(expense)

    async def list(self, filters: ExpenseFilterDTO) -> ExpenseListDTO:
        expenses, total = await self.repository.list(filters)
        items = [map_expense_domain_to_detail_dto(e) for e in expenses]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return ExpenseListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def get_summary(self, politician_id: int, year: int | None = None) -> ExpenseSummaryDTO:
        summary = await self.repository.get_summary(politician_id, year)
        return ExpenseSummaryDTO(
            total_amount=summary["total_amount"],
            by_type=summary["by_type"],
            by_month=summary["by_month"],
        )

    async def delete(self, expense_id: int) -> bool:
        return await self.repository.delete(expense_id)


class CampaignFinanceService:
    def __init__(self, repository: CampaignFinanceRepository):
        self.repository = repository

    async def create(self, data: CampaignFinanceCreateDTO) -> CampaignFinanceDetailDTO:
        finance = CampaignFinance(**map_campaign_finance_create_dto_to_domain(data))
        saved = await self.repository.save(finance)
        return map_campaign_finance_domain_to_detail_dto(saved)

    async def get(self, finance_id: int) -> CampaignFinanceDetailDTO:
        finance = await self.repository.get_by_id(finance_id)
        if finance is None:
            raise CampaignFinanceNotFoundError(finance_id)
        return map_campaign_finance_domain_to_detail_dto(finance)

    async def list(self, filters: CampaignFinanceFilterDTO) -> CampaignFinanceListDTO:
        finances, total = await self.repository.list(filters)
        items = [map_campaign_finance_domain_to_dto(f) for f in finances]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return CampaignFinanceListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def get_summary(self, politician_id: int, election_year: int | None = None) -> CampaignFinanceSummaryDTO:
        summary = await self.repository.get_summary(politician_id, election_year)
        return CampaignFinanceSummaryDTO(
            total_amount=summary["total_amount"],
            by_donor_type=summary["by_donor_type"],
            by_year=summary["by_year"],
            top_donors=summary["top_donors"],
        )

    async def update(self, finance_id: int, data: CampaignFinanceUpdateDTO) -> CampaignFinanceDetailDTO:
        finance = await self.repository.get_by_id(finance_id)
        if finance is None:
            raise CampaignFinanceNotFoundError(finance_id)

        update_data = data.model_dump(exclude_none=True, exclude_unset=True)
        for field_name, field_value in update_data.items():
            setattr(finance, field_name, field_value)

        saved = await self.repository.save(finance)
        return map_campaign_finance_domain_to_detail_dto(saved)

    async def delete(self, finance_id: int) -> bool:
        return await self.repository.delete(finance_id)