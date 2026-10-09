"""Politician API dependencies - FastAPI dependency providers."""

from typing import Annotated

from fastapi import Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession

from app.politician.application.filters import PoliticianFilterDTO
from app.politician.application.ports import PoliticianRepository
from app.politician.application.services import PoliticianService
from app.politician.infrastructure.repository import SQLAlchemyPoliticianRepository
from app.shared.kernel.database import get_session


def politician_filter(
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    uf: str | None = Query(None),
    party: str | None = Query(None),
) -> PoliticianFilterDTO:
    return PoliticianFilterDTO(
        page=page,
        per_page=per_page,
        uf=uf,
        party=party,
    )


async def politician_repository(
    session: Annotated[AsyncSession, Depends(get_session)],
) -> PoliticianRepository:
    return SQLAlchemyPoliticianRepository(session)


async def politician_service(
    repository: Annotated[PoliticianRepository, Depends(politician_repository)],
) -> PoliticianService:
    return PoliticianService(repository)
