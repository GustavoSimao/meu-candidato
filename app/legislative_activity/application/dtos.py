"""Legislative Activity application DTOs - Pydantic schemas."""

from datetime import date
from typing import Optional

from pydantic import BaseModel, ConfigDict


class VoteDTO(BaseModel):
    id: Optional[int] = None
    politician_id: int
    proposition_id: int
    session_date: date
    vote_value: str
    session_number: Optional[str] = None

    model_config = ConfigDict(from_attributes=True)


class VoteDetailDTO(VoteDTO):
    """Extended vote DTO with related data."""
    proposition_title: Optional[str] = None
    proposition_type: Optional[str] = None


class VoteListDTO(BaseModel):
    items: list[VoteDetailDTO]
    total: int
    page: int
    per_page: int
    pages: int


class VoteStatsDTO(BaseModel):
    favor: int
    contra: int
    abstencao: int
    ausente: int
    obstrucao: int
    art17: int
    desconhecido: int


class PropositionDTO(BaseModel):
    id: Optional[int] = None
    external_id: str
    politician_id: int
    type: str
    title: str
    house: str
    summary: Optional[str] = None
    status: Optional[str] = None
    presentation_date: Optional[date] = None
    url: Optional[str] = None

    model_config = ConfigDict(from_attributes=True)


class PropositionCreateDTO(PropositionDTO):
    pass


class PropositionUpdateDTO(BaseModel):
    external_id: Optional[str] = None
    politician_id: Optional[int] = None
    type: Optional[str] = None
    title: Optional[str] = None
    house: Optional[str] = None
    summary: Optional[str] = None
    status: Optional[str] = None
    presentation_date: Optional[date] = None
    url: Optional[str] = None


class PropositionDetailDTO(PropositionDTO):
    votes: list[VoteDTO] = []

    model_config = ConfigDict(from_attributes=True)


class PropositionListDTO(BaseModel):
    items: list[PropositionDTO]
    total: int
    page: int
    per_page: int
    pages: int