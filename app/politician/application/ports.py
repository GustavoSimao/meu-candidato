"""Politician application ports - repository interfaces."""

from abc import ABC, abstractmethod

from app.politician.application.filters import PoliticianFilterDTO
from app.politician.domain.entities import Politician


class PoliticianRepository(ABC):
    """Repository interface for politician persistence."""

    @abstractmethod
    async def save(self, politician: Politician) -> Politician:
        """Save a politician and return the saved entity with ID."""
        ...

    @abstractmethod
    async def upsert(self, politician: Politician) -> Politician:
        """Upsert a politician (by CPF, then external_id, then insert)."""
        ...

    @abstractmethod
    async def get_by_id(self, politician_id: int) -> Politician | None:
        """Get a politician by ID with mandates loaded."""
        ...

    @abstractmethod
    async def get_by_external_id(self, external_id: int) -> Politician | None:
        """Get a politician by external ID."""
        ...

    @abstractmethod
    async def get_by_cpf(self, cpf: str) -> Politician | None:
        """Get a politician by CPF."""
        ...

    @abstractmethod
    async def list(self, filters: PoliticianFilterDTO) -> tuple[list[Politician], int]:
        """List politicians with filters, returns (items, total_count)."""
        ...

    @abstractmethod
    async def exists_by_cpf(self, cpf: str) -> bool:
        """Check if a politician with given CPF exists."""
        ...

    @abstractmethod
    async def delete(self, politician_id: int) -> bool:
        """Delete a politician by ID. Returns True if deleted."""
        ...
