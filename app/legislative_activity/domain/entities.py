"""Legislative Activity domain entities - pure Python domain models."""

from dataclasses import dataclass, field
from datetime import date
from typing import Optional

from app.legislative_activity.domain.value_objects import VoteValue


@dataclass
class Vote:
    """Domain entity for a vote."""

    politician_id: int
    proposition_id: int
    session_date: date
    vote_value: VoteValue
    session_number: Optional[str] = None
    id: Optional[int] = None

    def is_favorable(self) -> bool:
        return self.vote_value.is_favorable()

    def is_against(self) -> bool:
        return self.vote_value.is_against()

    def is_absent(self) -> bool:
        return self.vote_value.is_absent()

    def is_abstention(self) -> bool:
        return self.vote_value.is_abstention()

    def is_obstruction(self) -> bool:
        return self.vote_value.is_obstruction()

    def is_art17(self) -> bool:
        return self.vote_value.is_art17()

    def is_unknown(self) -> bool:
        return self.vote_value.is_unknown()


@dataclass
class Proposition:
    """Domain entity for a legislative proposition."""

    politician_id: int
    external_id: str
    type: str  # "PL", "PDC", "REQ", etc.
    title: str
    house: str  # "camara", "senado"
    summary: Optional[str] = None
    status: Optional[str] = None
    presentation_date: Optional[date] = None
    url: Optional[str] = None
    id: Optional[int] = None

    def is_pl(self) -> bool:
        return self.type.upper() == "PL"