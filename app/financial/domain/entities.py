"""Financial domain entities - pure Python domain models."""

from dataclasses import dataclass, field
from datetime import date
from typing import Optional


@dataclass
class Expense:
    """Domain entity for a parliamentary expense."""

    politician_id: int
    expense_type: str
    amount: int  # in centavos
    expense_date: date
    year: int
    month: int
    description: Optional[str] = None
    provider: Optional[str] = None
    document_number: Optional[str] = None
    document_url: Optional[str] = None
    id: Optional[int] = None

    @property
    def amount_reais(self) -> float:
        return self.amount / 100


@dataclass
class CampaignFinance:
    """Domain entity for campaign finance."""

    politician_id: int
    election_year: int
    election_type: str  # federal, estadual, municipal
    donor_type: str  # pessoa_fisica, pessoa_juridica, fundo_partidario, recursos_proprios
    amount: int  # in centavos
    donation_date: date
    donor_name: Optional[str] = None
    donor_cpf_cnpj: Optional[str] = None
    receipt_url: Optional[str] = None
    id: Optional[int] = None

    @property
    def amount_reais(self) -> float:
        return self.amount / 100

    def is_corporate_donation(self) -> bool:
        return self.donor_type == "pessoa_juridica"

    def is_party_fund(self) -> bool:
        return self.donor_type == "fundo_partidario"