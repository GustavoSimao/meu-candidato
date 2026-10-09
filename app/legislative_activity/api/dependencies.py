"""Legislative Activity API dependencies."""

from datetime import date
from typing import Annotated

from fastapi import Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession

from app.legislative_activity.application.filters import PropositionFilterDTO, VoteFilterDTO
from app.legislative_activity.application.ports import PropositionRepository, VoteRepository
from app.legislative_activity.application.services import PropositionService, VoteService
from app.legislative_activity.infrastructure.repository import SQLAlchemyPropositionRepository, SQLAlchemyVoteRepository
from app.shared.kernel.database import get_session


def vote_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    politician_id: int | None = Query(None),
    proposition_id: int | None = Query(None),
    vote_value: str | None = Query(None),
    session_date_from: date | None = Query(None),
    session_date_to: date | None = Query(None),
) -> VoteFilterDTO:
    return VoteFilterDTO(
        page=page,
        per_page=per_page,
        politician_id=politician_id,
        proposition_id=proposition_id,
        vote_value=vote_value,
        session_date_from=session_date_from,
        session_date_to=session_date_to,
    )


def proposition_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    politician_id: int | None = Query(None),
    type: str | None = Query(None),
    status: str | None = Query(None),
    house: str | None = Query(None),
    presentation_date_from: date | None = Query(None),
    presentation_date_to: date | None = Query(None),
) -> PropositionFilterDTO:
    return PropositionFilterDTO(
        page=page,
        per_page=per_page,
        politician_id=politician_id,
        type=type,
        status=status,
        house=house,
        presentation_date_from=presentation_date_from,
        presentation_date_to=presentation_date_to,
    )


async def vote_repository(session: Annotated[AsyncSession, Depends(get_session)]) -> VoteRepository:
    return SQLAlchemyVoteRepository(session)


async def proposition_repository(session: Annotated[AsyncSession, Depends(get_session)]) -> PropositionRepository:
    return SQLAlchemyPropositionRepository(session)


async def vote_service(repository: Annotated[VoteRepository, Depends(vote_repository)]) -> VoteService:
    return VoteService(repository)


async def proposition_service(repository: Annotated[PropositionRepository, Depends(proposition_repository)]) -> PropositionService:
    return PropositionService(repository)