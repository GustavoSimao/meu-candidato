"""Legislative Activity application filter DTOs."""

from datetime import date
from typing import Optional

from pydantic import BaseModel, ConfigDict, Field


class VoteFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    politician_id: Optional[int] = None
    proposition_id: Optional[int] = None
    vote_value: Optional[str] = None
    session_date_from: Optional[date] = None
    session_date_to: Optional[date] = None

    model_config = ConfigDict(extra="forbid")


class PropositionFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    politician_id: Optional[int] = None
    type: Optional[str] = None
    status: Optional[str] = None
    house: Optional[str] = None
    presentation_date_from: Optional[date] = None
    presentation_date_to: Optional[date] = None

    model_config = ConfigDict(extra="forbid")