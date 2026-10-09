"""Financial application filter DTOs."""

from datetime import date
from typing import Optional

from pydantic import BaseModel, ConfigDict, Field


class ExpenseFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    politician_id: Optional[int] = None
    expense_type: Optional[str] = None
    year: Optional[int] = None
    month: Optional[int] = Field(default=None, ge=1, le=12)
    expense_date_from: Optional[date] = None
    expense_date_to: Optional[date] = None

    model_config = ConfigDict(extra="forbid")


class CampaignFinanceFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    politician_id: Optional[int] = None
    election_year: Optional[int] = None
    election_type: Optional[str] = None
    donor_type: Optional[str] = None

    model_config = ConfigDict(extra="forbid")