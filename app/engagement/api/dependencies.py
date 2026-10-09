"""Engagement API dependencies."""

from typing import Annotated

from fastapi import Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession

from app.engagement.application.filters import BadgeFilterDTO, BadgeRuleFilterDTO, FollowFilterDTO
from app.engagement.application.ports import BadgeRepository, BadgeRuleRepository, FollowRepository
from app.engagement.application.services import BadgeRuleService, BadgeService, FollowService
from app.engagement.infrastructure.repository import (
    SQLAlchemyBadgeRepository,
    SQLAlchemyBadgeRuleRepository,
    SQLAlchemyFollowRepository,
)
from app.legislative_activity.application.services import PropositionService, VoteService
from app.legislative_activity.infrastructure.repository import (
    SQLAlchemyPropositionRepository,
    SQLAlchemyVoteRepository,
)
from app.shared.kernel.database import get_session


def follow_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    user_id: str | None = Query(None),
    politician_id: int | None = Query(None),
) -> FollowFilterDTO:
    return FollowFilterDTO(page=page, per_page=per_page, user_id=user_id, politician_id=politician_id)


def badge_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    politician_id: int | None = Query(None),
    badge_type: str | None = Query(None),
) -> BadgeFilterDTO:
    return BadgeFilterDTO(page=page, per_page=per_page, politician_id=politician_id, badge_type=badge_type)


def badge_rule_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    badge_type: str | None = Query(None),
    is_active: bool | None = Query(None),
) -> BadgeRuleFilterDTO:
    return BadgeRuleFilterDTO(page=page, per_page=per_page, badge_type=badge_type, is_active=is_active)


async def follow_repository(session: Annotated[AsyncSession, Depends(get_session)]) -> FollowRepository:
    return SQLAlchemyFollowRepository(session)


async def badge_repository(session: Annotated[AsyncSession, Depends(get_session)]) -> BadgeRepository:
    return SQLAlchemyBadgeRepository(session)


async def badge_rule_repository(session: Annotated[AsyncSession, Depends(get_session)]) -> BadgeRuleRepository:
    return SQLAlchemyBadgeRuleRepository(session)


async def follow_service(repository: Annotated[FollowRepository, Depends(follow_repository)]) -> FollowService:
    return FollowService(repository)


async def badge_service(
    repository: Annotated[BadgeRepository, Depends(badge_repository)],
    badge_rule_repo: Annotated[BadgeRuleRepository, Depends(badge_rule_repository)],
) -> BadgeService:
    return BadgeService(repository, badge_rule_repo)


async def badge_rule_service(repository: Annotated[BadgeRuleRepository, Depends(badge_rule_repository)]) -> BadgeRuleService:
    return BadgeRuleService(repository)


# Legislative activity dependencies for badge metrics
async def proposition_repository(session: Annotated[AsyncSession, Depends(get_session)]):
    return SQLAlchemyPropositionRepository(session)


async def vote_repository(session: Annotated[AsyncSession, Depends(get_session)]):
    return SQLAlchemyVoteRepository(session)


async def proposition_service(repo: Annotated[object, Depends(proposition_repository)]) -> PropositionService:
    return PropositionService(repo)


async def vote_service(repo: Annotated[object, Depends(vote_repository)]) -> VoteService:
    return VoteService(repo)