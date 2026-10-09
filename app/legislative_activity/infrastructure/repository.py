"""Legislative Activity infrastructure repository - SQLAlchemy implementations."""

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.legislative_activity.application.filters import PropositionFilterDTO, VoteFilterDTO
from app.legislative_activity.application.ports import PropositionRepository, VoteRepository
from app.legislative_activity.domain.entities import Proposition, Vote
from app.legislative_activity.infrastructure.mappers import (
    map_proposition_domain_to_orm,
    map_proposition_orm_to_domain,
    map_vote_domain_to_orm,
    map_vote_orm_to_domain,
)
from app.legislative_activity.infrastructure.models import PropositionORM, VoteORM


class SQLAlchemyVoteRepository(VoteRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, vote: Vote) -> Vote:
        orm = map_vote_domain_to_orm(vote)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_vote_orm_to_domain(orm)

    async def get_by_id(self, vote_id: int) -> Vote | None:
        query = select(VoteORM).where(VoteORM.id == vote_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_vote_orm_to_domain(orm) if orm else None

    async def list(self, filters: VoteFilterDTO) -> tuple[list[Vote], int]:
        query = select(VoteORM)

        if filters.politician_id:
            query = query.where(VoteORM.politician_id == filters.politician_id)
        if filters.proposition_id:
            query = query.where(VoteORM.proposition_id == filters.proposition_id)
        if filters.vote_value:
            query = query.where(VoteORM.vote_value == filters.vote_value)
        if filters.session_date_from:
            query = query.where(VoteORM.session_date >= filters.session_date_from)
        if filters.session_date_to:
            query = query.where(VoteORM.session_date <= filters.session_date_to)

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = (
            query.order_by(VoteORM.session_date.desc())
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_vote_orm_to_domain(orm) for orm in orms], total

    async def delete(self, vote_id: int) -> bool:
        query = select(VoteORM).where(VoteORM.id == vote_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True

    async def get_vote_stats(self, politician_id: int | None = None) -> dict[str, int]:
        from app.legislative_activity.domain.value_objects import VoteValue

        query = select(VoteORM.vote_value, func.count().label("count"))

        if politician_id:
            query = query.where(VoteORM.politician_id == politician_id)

        query = query.group_by(VoteORM.vote_value)
        result = await self.session.execute(query)

        stats = {v.value: 0 for v in VoteValue}
        for row in result:
            stats[row.vote_value] = row.count

        return stats


class SQLAlchemyPropositionRepository(PropositionRepository):
    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, proposition: Proposition) -> Proposition:
        orm = map_proposition_domain_to_orm(proposition)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm)
        return map_proposition_orm_to_domain(orm)

    async def get_by_id(self, proposition_id: int) -> Proposition | None:
        query = select(PropositionORM).where(PropositionORM.id == proposition_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_proposition_orm_to_domain(orm) if orm else None

    async def get_by_external_id(self, external_id: str) -> Proposition | None:
        query = select(PropositionORM).where(PropositionORM.external_id == external_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return map_proposition_orm_to_domain(orm) if orm else None

    async def list(self, filters: PropositionFilterDTO) -> tuple[list[Proposition], int]:
        query = select(PropositionORM)

        if filters.politician_id:
            query = query.where(PropositionORM.politician_id == filters.politician_id)
        if filters.type:
            query = query.where(PropositionORM.type == filters.type)
        if filters.status:
            query = query.where(PropositionORM.status == filters.status)
        if filters.house:
            query = query.where(PropositionORM.house == filters.house)
        if filters.presentation_date_from:
            query = query.where(PropositionORM.presentation_date >= filters.presentation_date_from)
        if filters.presentation_date_to:
            query = query.where(PropositionORM.presentation_date <= filters.presentation_date_to)

        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        query = (
            query.order_by(PropositionORM.presentation_date.desc())
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [map_proposition_orm_to_domain(orm) for orm in orms], total

    async def delete(self, proposition_id: int) -> bool:
        query = select(PropositionORM).where(PropositionORM.id == proposition_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True