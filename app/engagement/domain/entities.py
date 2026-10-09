"""Engagement domain entities - pure Python domain models."""

from dataclasses import dataclass, field
from datetime import date, datetime
from typing import Optional
from enum import Enum


class BadgeType(str, Enum):
    """Available badge types."""

    FICHA_LIMPA = "ficha_limpa"
    PRESENCA_ALTA = "presenca_alta"
    LEGISLADOR_ATIVO = "legislador_ativo"

    @classmethod
    def all_values(cls) -> list[str]:
        return [v.value for v in cls]


@dataclass
class Follow:
    """Domain entity for a user following a politician."""

    user_id: str
    politician_id: int
    created_at: datetime = field(default_factory=datetime.now)
    id: Optional[int] = None

    def is_following(self, politician_id: int) -> bool:
        return self.politician_id == politician_id


@dataclass
class Badge:
    """Domain entity for a politician badge."""

    politician_id: int
    badge_type: BadgeType
    earned_at: date = field(default_factory=date.today)
    metadata: dict = field(default_factory=dict)  # e.g., {"proposals_count": 50, "year": 2023}
    id: Optional[int] = None


@dataclass
class BadgeRule:
    """Domain entity for a badge assignment rule."""

    badge_type: BadgeType
    name: str
    description: str
    condition: str  # Human-readable condition, e.g., "Mais de 10 proposições no ano"
    threshold: int  # Numeric threshold for the condition
    is_active: bool = True
    id: Optional[int] = None

    def check_condition(self, value: int) -> bool:
        """Check if a value meets the threshold."""
        return value >= self.threshold