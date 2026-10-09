"""Financial infrastructure repository - SQLAlchemy implementations."""

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.financial.application.filters import CampaignFinanceFilterDTO, ExpenseFilterDTO
from app.financial.application.ports import CampaignFinanceRepository, ExpenseRepository
from app.financial.domain.entities import CampaignFinance, Expense
from app.financial.infrastructure.mappers import (
    map_campaign_finance_domain_to_orm,
    map_campaign_finance_orm_to_domain,
    map_expense_domain_to_orm,
    map_expense_orm_to_domain,
)
from app.financial.infrastructure.models import CampaignFinanceORM, ExpenseORM


class SQLAlchemyExpenseRepository(ExpenseRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, expense: Expense) -> Expense:
        orm = map_expense_domain_to_orm(expense)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_expense_orm_to_domain(orm)

    async def get_by_id(self, expense_id: int) -> Expense | None:
        query = select(ExpenseORM).where(ExpenseORM.id == expense_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_expense_orm_to_domain(orm) if orm else None

    async def list(self, filters: ExpenseFilterDTO) -> tuple[list[Expense], int]:
        query = select(ExpenseORM)

        if filters.politician_id:
            query = query.where(ExpenseORM.politician_id == filters.politician_id)
        if filters.expense_type:
            query = query.where(ExpenseORM.expense_type == filters.expense_type)
        if filters.year:
            query = query.where(ExpenseORM.year == filters.year)
        if filters.month:
            query = query.where(ExpenseORM.month == filters.month)
        if filters.expense_date_from:
            query = query.where(ExpenseORM.expense_date >= filters.expense_date_from)
        if filters.expense_date_to:
            query = query.where(ExpenseORM.expense_date <= filters.expense_date_to)

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = (
            query.order_by(ExpenseORM.expense_date.desc())
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_expense_orm_to_domain(orm) for orm in orms], total

    async def get_summary(self, politician_id: int, year: int | None = None) -> dict:
        query = select(
            ExpenseORM.expense_type,
            func.sum(ExpenseORM.amount).label("total"),
            func.count().label("count")
        ).where(ExpenseORM.politician_id == politician_id)

        if year:
            query = query.where(ExpenseORM.year == year)

        query = query.group_by(ExpenseORM.expense_type)
        result = await self.session.execute(query)

        by_type = {}
        total_amount = 0
        for row in result:
            by_type[row.expense_type] = {"total": row.total, "count": row.count}
            total_amount += row.total

        # by_month aggregation
        month_query = select(
            ExpenseORM.year,
            ExpenseORM.month,
            func.sum(ExpenseORM.amount).label("total"),
            func.count().label("count")
        ).where(ExpenseORM.politician_id == politician_id)

        if year:
            month_query = month_query.where(ExpenseORM.year == year)

        month_query = month_query.group_by(ExpenseORM.year, ExpenseORM.month).order_by(ExpenseORM.year, ExpenseORM.month)
        month_result = await self.session.execute(month_query)

        by_month = {}
        for row in month_result:
            period = f"{row.year}-{row.month:02d}"
            by_month[period] = {"total": row.total, "count": row.count, "year": row.year, "month": row.month}

        return {"total_amount": total_amount, "by_type": by_type, "by_month": by_month}

    async def delete(self, expense_id: int) -> bool:
        query = select(ExpenseORM).where(ExpenseORM.id == expense_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True


class SQLAlchemyCampaignFinanceRepository(CampaignFinanceRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, finance: CampaignFinance) -> CampaignFinance:
        orm = map_campaign_finance_domain_to_orm(finance)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_campaign_finance_orm_to_domain(orm)

    async def get_by_id(self, finance_id: int) -> CampaignFinance | None:
        query = select(CampaignFinanceORM).where(CampaignFinanceORM.id == finance_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_campaign_finance_orm_to_domain(orm) if orm else None

    async def list(self, filters: CampaignFinanceFilterDTO) -> tuple[list[CampaignFinance], int]:
        query = select(CampaignFinanceORM)

        if filters.politician_id:
            query = query.where(CampaignFinanceORM.politician_id == filters.politician_id)
        if filters.election_year:
            query = query.where(CampaignFinanceORM.election_year == filters.election_year)
        if filters.election_type:
            query = query.where(CampaignFinanceORM.election_type == filters.election_type)
        if filters.donor_type:
            query = query.where(CampaignFinanceORM.donor_type == filters.donor_type)

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = (
            query.order_by(CampaignFinanceORM.donation_date.desc())
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_campaign_finance_orm_to_domain(orm) for orm in orms], total

    async def get_summary(self, politician_id: int, election_year: int | None = None) -> dict:
        query = select(
            CampaignFinanceORM.donor_type,
            func.sum(CampaignFinanceORM.amount).label("total"),
            func.count().label("count")
        ).where(CampaignFinanceORM.politician_id == politician_id)

        if election_year:
            query = query.where(CampaignFinanceORM.election_year == election_year)

        query = query.group_by(CampaignFinanceORM.donor_type)
        result = await self.session.execute(query)

        by_donor_type = {}
        total_amount = 0
        for row in result:
            by_donor_type[row.donor_type] = {"total": row.total, "count": row.count}
            total_amount += row.total

        # by_year aggregation
        year_query = select(
            CampaignFinanceORM.election_year,
            func.sum(CampaignFinanceORM.amount).label("total"),
            func.count().label("count")
        ).where(CampaignFinanceORM.politician_id == politician_id)

        year_query = year_query.group_by(CampaignFinanceORM.election_year).order_by(CampaignFinanceORM.election_year.desc())
        year_result = await self.session.execute(year_query)

        by_year = {}
        for row in year_result:
            by_year[str(row.election_year)] = {"total": row.total, "count": row.count}

        # top_donors aggregation
        donor_query = select(
            CampaignFinanceORM.donor_name,
            CampaignFinanceORM.donor_type,
            func.sum(CampaignFinanceORM.amount).label("total"),
            func.count().label("count")
        ).where(CampaignFinanceORM.politician_id == politician_id)

        if election_year:
            donor_query = donor_query.where(CampaignFinanceORM.election_year == election_year)

        donor_query = donor_query.group_by(CampaignFinanceORM.donor_name, CampaignFinanceORM.donor_type).order_by(func.sum(CampaignFinanceORM.amount).desc()).limit(10)
        donor_result = await self.session.execute(donor_query)

        top_donors = []
        for row in donor_result:
            if row.donor_name:
                top_donors.append({
                    "donor_name": row.donor_name,
                    "donor_type": row.donor_type,
                    "amount": row.total,
                    "count": row.count
                })

        return {"total_amount": total_amount, "by_donor_type": by_donor_type, "by_year": by_year, "top_donors": top_donors}

    async def delete(self, finance_id: int) -> bool:
        query = select(CampaignFinanceORM).where(CampaignFinanceORM.id == finance_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True