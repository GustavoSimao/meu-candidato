"""Legislative Activity application ports - repository interfaces."""

from abc import ABC, abstractmethod
from typing import Optional, Tuple, List

from app.legislative_activity.domain.entities import Proposition, Vote
from app.legislative_activity.application.filters import PropositionFilterDTO, VoteFilterDTO


class VoteRepository(ABC):
    @abstractmethod
    async def save(self, vote: Vote) -> Vote:
        ...

    @abstractmethod
    async def get_by_id(self, vote_id: int) -> Optional[Vote]:
        ...

    @abstractmethod
    async def list(self, filters: VoteFilterDTO) -> Tuple[List[Vote], int]:
        ...

    @abstractmethod
    async def delete(self, vote_id: int) -> bool:
        ...

    @abstractmethod
    async def get_vote_stats(self, politician_id: int | None = None) -> dict[str, int]:
        ...


class PropositionRepository(ABC):
    @abstractmethod
    async def save(self, proposition: Proposition) -> Proposition:
        ...

    @abstractmethod
    async def get_by_id(self, proposition_id: int) -> Optional[Proposition]:
        ...

    @abstractmethod
    async def get_by_external_id(self, external_id: str) -> Optional[Proposition]:
        ...

    @abstractmethod
    async def list(self, filters: PropositionFilterDTO) -> Tuple[List[Proposition], int]:
        ...

    @abstractmethod
    async def delete(self, proposition_id: int) -> bool:
        ...