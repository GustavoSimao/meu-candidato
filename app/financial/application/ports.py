"""Financial application ports - repository interfaces."""

from abc import ABC, abstractmethod
from typing import Optional, Tuple, List

from app.financial.domain.entities import CampaignFinance, Expense
from app.financial.application.filters import CampaignFinanceFilterDTO, ExpenseFilterDTO


class ExpenseRepository(ABC):
    @abstractmethod
    async def save(self, expense: Expense) -> Expense:
        ...

    @abstractmethod
    async def get_by_id(self, expense_id: int) -> Optional[Expense]:
        ...

    @abstractmethod
    async def list(self, filters: ExpenseFilterDTO) -> Tuple[List[Expense], int]:
        ...

    @abstractmethod
    async def get_summary(self, politician_id: int, year: int | None = None) -> dict:
        ...

    @abstractmethod
    async def delete(self, expense_id: int) -> bool:
        ...


class CampaignFinanceRepository(ABC):
    @abstractmethod
    async def save(self, finance: CampaignFinance) -> CampaignFinance:
        ...

    @abstractmethod
    async def get_by_id(self, finance_id: int) -> Optional[CampaignFinance]:
        ...

    @abstractmethod
    async def list(self, filters: CampaignFinanceFilterDTO) -> Tuple[List[CampaignFinance], int]:
        ...

    @abstractmethod
    async def get_summary(self, politician_id: int, election_year: int | None = None) -> dict:
        ...

    @abstractmethod
    async def delete(self, finance_id: int) -> bool:
        ...