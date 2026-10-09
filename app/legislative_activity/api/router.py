"""Legislative Activity API router."""

from datetime import date
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status

from app.legislative_activity.api.dependencies import (
    proposition_filter,
    proposition_service,
    vote_filter,
    vote_service,
)
from app.legislative_activity.application.dtos import (
    PropositionCreateDTO,
    PropositionDetailDTO,
    PropositionListDTO,
    PropositionUpdateDTO,
    VoteDTO,
    VoteDetailDTO,
    VoteListDTO,
    VoteStatsDTO,
)
from app.legislative_activity.application.filters import PropositionFilterDTO, VoteFilterDTO
from app.legislative_activity.application.services import PropositionService, VoteService
from app.legislative_activity.domain.exceptions import PropositionNotFoundError, VoteNotFoundError

router = APIRouter(prefix="/legislative", tags=["legislative-activity"])


# Votes endpoints
@router.post("/votes", response_model=VoteDetailDTO, status_code=status.HTTP_201_CREATED)
async def create_vote(
    data: VoteDTO,
    service: Annotated[VoteService, Depends(vote_service)],
) -> VoteDetailDTO:
    return await service.create(data)


@router.get("/votes", response_model=VoteListDTO)
async def list_votes(
    filters: Annotated[VoteFilterDTO, Depends(vote_filter)],
    service: Annotated[VoteService, Depends(vote_service)],
) -> VoteListDTO:
    return await service.list(filters)


@router.get("/votes/stats", response_model=VoteStatsDTO)
async def get_vote_stats(
    service: Annotated[VoteService, Depends(vote_service)],
    politician_id: int | None = None,
) -> VoteStatsDTO:
    return await service.get_vote_stats(politician_id)


@router.get("/votes/{vote_id}", response_model=VoteDetailDTO)
async def get_vote(
    vote_id: int,
    service: Annotated[VoteService, Depends(vote_service)],
) -> VoteDetailDTO:
    try:
        return await service.get(vote_id)
    except VoteNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Vote not found")


@router.delete("/votes/{vote_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_vote(
    vote_id: int,
    service: Annotated[VoteService, Depends(vote_service)],
) -> None:
    deleted = await service.delete(vote_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Vote not found")


# Propositions endpoints
@router.post("/propositions", response_model=PropositionDetailDTO, status_code=status.HTTP_201_CREATED)
async def create_proposition(
    data: PropositionCreateDTO,
    service: Annotated[PropositionService, Depends(proposition_service)],
) -> PropositionDetailDTO:
    try:
        return await service.create(data)
    except Exception as e:
        if "DUPLICATE_EXTERNAL_ID" in str(e):
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(e))
        raise


@router.get("/propositions", response_model=PropositionListDTO)
async def list_propositions(
    filters: Annotated[PropositionFilterDTO, Depends(proposition_filter)],
    service: Annotated[PropositionService, Depends(proposition_service)],
) -> PropositionListDTO:
    return await service.list(filters)


@router.get("/propositions/{proposition_id}", response_model=PropositionDetailDTO)
async def get_proposition(
    proposition_id: int,
    service: Annotated[PropositionService, Depends(proposition_service)],
) -> PropositionDetailDTO:
    try:
        return await service.get(proposition_id)
    except PropositionNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Proposition not found")


@router.get("/propositions/external/{external_id}", response_model=PropositionDetailDTO)
async def get_proposition_by_external_id(
    external_id: str,
    service: Annotated[PropositionService, Depends(proposition_service)],
) -> PropositionDetailDTO:
    try:
        return await service.get_by_external_id(external_id)
    except PropositionNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Proposition not found")


@router.patch("/propositions/{proposition_id}", response_model=PropositionDetailDTO)
async def update_proposition(
    proposition_id: int,
    data: PropositionUpdateDTO,
    service: Annotated[PropositionService, Depends(proposition_service)],
) -> PropositionDetailDTO:
    try:
        return await service.update(proposition_id, data)
    except PropositionNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Proposition not found")


@router.delete("/propositions/{proposition_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_proposition(
    proposition_id: int,
    service: Annotated[PropositionService, Depends(proposition_service)],
) -> None:
    deleted = await service.delete(proposition_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Proposition not found")