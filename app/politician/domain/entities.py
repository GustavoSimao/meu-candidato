"""Politician domain entities - pure Python domain models without framework dependencies."""

from dataclasses import dataclass, field
from datetime import date


@dataclass
class Mandate:
    """Domain entity for a political mandate."""

    house: str  # "camara" or "senado"
    role: str   # "deputado federal", "senador", etc.
    uf: str
    start_date: date
    end_date: date | None = None
    is_suplente: bool = False
    id: int | None = None
    politician_id: int | None = None

    def is_current(self, reference_date: date | None = None) -> bool:
        """Check if mandate is currently active."""
        ref = reference_date or date.today()
        if self.end_date is None:
            return self.start_date <= ref
        return self.start_date <= ref <= self.end_date


@dataclass
class Politician:
    """Domain entity for a politician."""

    name: str
    party: str
    uf: str
    number: int
    external_id: int | None = None
    cpf: str | None = None
    email: str | None = None
    office_address: str | None = None
    office_phone: str | None = None
    biography: str | None = None
    social_media: dict | None = None
    education: str | None = None
    photo_url: str | None = None
    mandates: list[Mandate] = field(default_factory=list)
    id: int | None = None
    created_at: date | None = None
    updated_at: date | None = None

    @property
    def current_mandate(self) -> Mandate | None:
        """Get the current active mandate if any."""
        for mandate in self.mandates:
            if mandate.is_current():
                return mandate
        return None

    @property
    def has_mandates(self) -> bool:
        return len(self.mandates) > 0
