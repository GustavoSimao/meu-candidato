"""Politician API router - FastAPI routes for politician endpoints."""

from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status

from app.politician.api.dependencies import politician_filter, politician_service
from app.politician.application.dtos import (
    PoliticianCreateDTO,
    PoliticianDetailDTO,
    PoliticianListDTO,
    PoliticianUpdateDTO,
)
from app.politician.application.filters import PoliticianFilterDTO
from app.politician.application.services import PoliticianService
from app.politician.domain.exceptions import (
    DuplicateCPFError,
    PoliticianNotFoundError,
)

router = APIRouter(prefix="/politicians", tags=["politicians"])


@router.post("", response_model=PoliticianDetailDTO, status_code=status.HTTP_201_CREATED)
async def create_politician(
    data: PoliticianCreateDTO,
    service: Annotated[PoliticianService, Depends(politician_service)],
) -> PoliticianDetailDTO:
    try:
        return await service.create(data)
    except DuplicateCPFError as e:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(e))


@router.get("", response_model=PoliticianListDTO)
async def list_politicians(
    filters: Annotated[PoliticianFilterDTO, Depends(politician_filter)],
    service: Annotated[PoliticianService, Depends(politician_service)],
) -> PoliticianListDTO:
    return await service.list(filters)


@router.get("/{politician_id}", response_model=PoliticianDetailDTO)
async def get_politician(
    politician_id: int,
    service: Annotated[PoliticianService, Depends(politician_service)],
) -> PoliticianDetailDTO:
    try:
        return await service.get(politician_id)
    except PoliticianNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Politician not found")


@router.patch("/{politician_id}", response_model=PoliticianDetailDTO)
async def update_politician(
    politician_id: int,
    data: PoliticianUpdateDTO,
    service: Annotated[PoliticianService, Depends(politician_service)],
) -> PoliticianDetailDTO:
    try:
        return await service.update(politician_id, data)
    except PoliticianNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Politician not found")


@router.delete("/{politician_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_politician(
    politician_id: int,
    service: Annotated[PoliticianService, Depends(politician_service)],
) -> None:
    deleted = await service.delete(politician_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Politician not found")
