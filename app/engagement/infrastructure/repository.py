"""Engagement infrastructure repository - SQLAlchemy implementations."""

from __future__ import annotations

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.engagement.application.filters import BadgeFilterDTO, BadgeRuleFilterDTO, FollowFilterDTO
from app.engagement.application.ports import BadgeRepository, BadgeRuleRepository, FollowRepository
from app.engagement.domain.entities import Badge, BadgeRule, Follow
from app.engagement.infrastructure.mappers import (
    map_badge_domain_to_orm,
    map_badge_orm_to_domain,
    map_badge_rule_domain_to_orm,
    map_badge_rule_orm_to_domain,
    map_follow_domain_to_orm,
    map_follow_orm_to_domain,
)
from app.engagement.infrastructure.models import BadgeORM, BadgeRuleORM, FollowORM


class SQLAlchemyFollowRepository(FollowRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, follow: Follow) -> Follow:
        orm = map_follow_domain_to_orm(follow)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_follow_orm_to_domain(orm)

    async def get_by_user_and_politician(self, user_id: str, politician_id: int) -> Follow | None:
        query = select(FollowORM).where(
            FollowORM.user_id == user_id,
            FollowORM.politician_id == politician_id,
        )
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_follow_orm_to_domain(orm) if orm else None

    async def list(self, filters: FollowFilterDTO) -> tuple[list[Follow], int]:
        query = select(FollowORM)

        if filters.user_id:
            query = query.where(FollowORM.user_id == filters.user_id)
        if filters.politician_id:
            query = query.where(FollowORM.politician_id == filters.politician_id)

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = (
            query.order_by(FollowORM.created_at.desc())
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_follow_orm_to_domain(orm) for orm in orms], total

    async def delete(self, user_id: str, politician_id: int) -> bool:
        query = select(FollowORM).where(
            FollowORM.user_id == user_id,
            FollowORM.politician_id == politician_id,
        )
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True

    async def exists(self, user_id: str, politician_id: int) -> bool:
        query = select(FollowORM.id).where(
            FollowORM.user_id == user_id,
            FollowORM.politician_id == politician_id,
        )
        result = await self.session.execute(query)
        return result.scalar_one_or_none() is not None

    async def get_by_user(self, user_id: str) -> list[Follow]:
        query = select(FollowORM).where(FollowORM.user_id == user_id).order_by(FollowORM.created_at.desc())
        result = await self.session.execute(query)
        orms = result.scalars().all()
        return [map_follow_orm_to_domain(orm) for orm in orms]


class SQLAlchemyBadgeRepository(BadgeRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, badge: Badge) -> Badge:
        orm = map_badge_domain_to_orm(badge)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_badge_orm_to_domain(orm)

    async def get_by_id(self, badge_id: int) -> Badge | None:
        query = select(BadgeORM).where(BadgeORM.id == badge_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_badge_orm_to_domain(orm) if orm else None

    async def list(self, filters: BadgeFilterDTO) -> tuple[list[Badge], int]:
        query = select(BadgeORM)

        if filters.politician_id:
            query = query.where(BadgeORM.politician_id == filters.politician_id)
        if filters.badge_type:
            query = query.where(BadgeORM.badge_type == filters.badge_type)

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = (
            query.order_by(BadgeORM.earned_at.desc())
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_badge_orm_to_domain(orm) for orm in orms], total

    async def get_by_politician_and_type(self, politician_id: int, badge_type: str) -> Badge | None:
        query = select(BadgeORM).where(
            BadgeORM.politician_id == politician_id,
            BadgeORM.badge_type == badge_type,
        )
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_badge_orm_to_domain(orm) if orm else None

    async def delete(self, badge_id: int) -> bool:
        query = select(BadgeORM).where(BadgeORM.id == badge_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True


class SQLAlchemyBadgeRuleRepository(BadgeRuleRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, rule: BadgeRule) -> BadgeRule:
        orm = map_badge_rule_domain_to_orm(rule)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_badge_rule_orm_to_domain(orm)

    async def get_by_id(self, rule_id: int) -> BadgeRule | None:
        query = select(BadgeRuleORM).where(BadgeRuleORM.id == rule_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_badge_rule_orm_to_domain(orm) if orm else None

    async def get_by_badge_type(self, badge_type: str) -> BadgeRule | None:
        query = select(BadgeRuleORM).where(BadgeRuleORM.badge_type == badge_type)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_badge_rule_orm_to_domain(orm) if orm else None

    async def list(self, filters: BadgeRuleFilterDTO) -> tuple[list[BadgeRule], int]:
        query = select(BadgeRuleORM)

        if filters.badge_type:
            query = query.where(BadgeRuleORM.badge_type == filters.badge_type)
        if filters.is_active is not None:
            query = query.where(BadgeRuleORM.is_active == (1 if filters.is_active else 0))

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = query.offset((filters.page - 1) * filters.per_page).limit(filters.per_page)
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_badge_rule_orm_to_domain(orm) for orm in orms], total

    async def delete(self, rule_id: int) -> bool:
        query = select(BadgeRuleORM).where(BadgeRuleORM.id == rule_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True