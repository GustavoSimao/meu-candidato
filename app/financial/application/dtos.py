"""Financial application DTOs - Pydantic schemas."""

from datetime import date
from typing import Optional

from pydantic import BaseModel, ConfigDict


class ExpenseDTO(BaseModel):
    id: Optional[int] = None
    politician_id: int
    expense_type: str
    amount: int
    expense_date: date
    year: int
    month: int
    description: Optional[str] = None
    provider: Optional[str] = None
    document_number: Optional[str] = None
    document_url: Optional[str] = None

    model_config = ConfigDict(from_attributes=True)


class ExpenseDetailDTO(ExpenseDTO):
    """Extended expense DTO with computed fields."""
    amount_reais: float = 0.0

    model_config = ConfigDict(from_attributes=True)


class ExpenseListDTO(BaseModel):
    items: list[ExpenseDetailDTO]
    total: int
    page: int
    per_page: int
    pages: int


class ExpenseSummaryDTO(BaseModel):
    total_amount: int
    by_type: dict[str, dict[str, int]]
    by_month: dict[str, dict[str, int]]


class CampaignFinanceDTO(BaseModel):
    id: Optional[int] = None
    politician_id: int
    election_year: int
    election_type: str
    donor_type: str
    donor_name: Optional[str] = None
    donor_cpf_cnpj: Optional[str] = None
    amount: int
    donation_date: date
    receipt_url: Optional[str] = None

    model_config = ConfigDict(from_attributes=True)


class CampaignFinanceCreateDTO(CampaignFinanceDTO):
    pass


class CampaignFinanceUpdateDTO(BaseModel):
    politician_id: Optional[int] = None
    election_year: Optional[int] = None
    election_type: Optional[str] = None
    donor_type: Optional[str] = None
    donor_name: Optional[str] = None
    donor_cpf_cnpj: Optional[str] = None
    amount: Optional[int] = None
    donation_date: Optional[date] = None
    receipt_url: Optional[str] = None


class CampaignFinanceDetailDTO(CampaignFinanceDTO):
    """Extended campaign finance DTO with computed fields."""
    amount_reais: float = 0.0

    model_config = ConfigDict(from_attributes=True)


class CampaignFinanceListDTO(BaseModel):
    items: list[CampaignFinanceDetailDTO]
    total: int
    page: int
    per_page: int
    pages: int


class CampaignFinanceSummaryDTO(BaseModel):
    total_amount: int
    by_donor_type: dict[str, dict[str, int]]
    by_year: dict[str, dict[str, int]]
    top_donors: list[dict[str, int | str]]