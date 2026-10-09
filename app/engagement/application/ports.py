"""Engagement application ports - repository interfaces."""

from abc import ABC, abstractmethod
from typing import Optional, Tuple, List

from app.engagement.domain.entities import Badge, BadgeRule, Follow
from app.engagement.application.filters import BadgeFilterDTO, BadgeRuleFilterDTO, FollowFilterDTO


class FollowRepository(ABC):
    @abstractmethod
    async def save(self, follow: Follow) -> Follow:
        ...

    @abstractmethod
    async def get_by_user_and_politician(self, user_id: str, politician_id: int) -> Optional[Follow]:
        ...

    @abstractmethod
    async def list(self, filters: FollowFilterDTO) -> Tuple[List[Follow], int]:
        ...

    @abstractmethod
    async def delete(self, user_id: str, politician_id: int) -> bool:
        ...

    @abstractmethod
    async def exists(self, user_id: str, politician_id: int) -> bool:
        ...

    @abstractmethod
    async def get_by_user(self, user_id: str) -> List[Follow]:
        ...


class BadgeRepository(ABC):
    @abstractmethod
    async def save(self, badge: Badge) -> Badge:
        ...

    @abstractmethod
    async def get_by_id(self, badge_id: int) -> Optional[Badge]:
        ...

    @abstractmethod
    async def list(self, filters: BadgeFilterDTO) -> Tuple[List[Badge], int]:
        ...

    @abstractmethod
    async def get_by_politician_and_type(self, politician_id: int, badge_type: str) -> Optional[Badge]:
        ...

    @abstractmethod
    async def delete(self, badge_id: int) -> bool:
        ...


class BadgeRuleRepository(ABC):
    @abstractmethod
    async def save(self, rule: BadgeRule) -> BadgeRule:
        ...

    @abstractmethod
    async def get_by_id(self, rule_id: int) -> Optional[BadgeRule]:
        ...

    @abstractmethod
    async def get_by_badge_type(self, badge_type: str) -> Optional[BadgeRule]:
        ...

    @abstractmethod
    async def list(self, filters: BadgeRuleFilterDTO) -> Tuple[List[BadgeRule], int]:
        ...

    @abstractmethod
    async def delete(self, rule_id: int) -> bool:
        ...