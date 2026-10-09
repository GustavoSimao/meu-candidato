"""Ingestion API router."""

from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status

from app.ingestion.api.dependencies import (
    ingestion_job_filter,
    ingestion_job_service,
    quarantine_filter,
    quarantine_service,
)
from app.ingestion.application.dtos import (
    IngestionJobCreateDTO,
    IngestionJobDetailDTO,
    IngestionJobListDTO,
    IngestionJobUpdateDTO,
    IngestionSummaryDTO,
    QuarantineRecordDTO,
    QuarantineRecordListDTO,
)
from app.ingestion.application.filters import IngestionJobFilterDTO, QuarantineRecordFilterDTO
from app.ingestion.application.services import IngestionJobService, QuarantineService
from app.ingestion.domain.exceptions import IngestionError

router = APIRouter(prefix="/ingestion", tags=["ingestion"])


# Ingestion Job endpoints
@router.post("/jobs", response_model=IngestionJobDetailDTO, status_code=status.HTTP_201_CREATED)
async def start_ingestion_job(
    data: IngestionJobCreateDTO,
    service: Annotated[IngestionJobService, Depends(ingestion_job_service)],
) -> IngestionJobDetailDTO:
    return await service.start_job(data)


@router.get("/jobs", response_model=IngestionJobListDTO)
async def list_ingestion_jobs(
    filters: Annotated[IngestionJobFilterDTO, Depends(ingestion_job_filter)],
    service: Annotated[IngestionJobService, Depends(ingestion_job_service)],
) -> IngestionJobListDTO:
    return await service.list_jobs(filters)


@router.get("/jobs/summary", response_model=IngestionSummaryDTO)
async def get_ingestion_summary(
    service: Annotated[IngestionJobService, Depends(ingestion_job_service)],
) -> IngestionSummaryDTO:
    return await service.get_summary()


@router.get("/jobs/latest", response_model=IngestionJobDetailDTO)
async def get_latest_job(
    data_source: str,
    dataset: str,
    service: Annotated[IngestionJobService, Depends(ingestion_job_service)],
) -> IngestionJobDetailDTO:
    try:
        return await service.get_latest_job(data_source, dataset)
    except IngestionError as e:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(e))


@router.get("/jobs/{job_id}", response_model=IngestionJobDetailDTO)
async def get_ingestion_job(
    job_id: int,
    service: Annotated[IngestionJobService, Depends(ingestion_job_service)],
) -> IngestionJobDetailDTO:
    try:
        return await service.get_job(job_id)
    except IngestionError as e:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(e))


@router.patch("/jobs/{job_id}", response_model=IngestionJobDetailDTO)
async def update_ingestion_job(
    job_id: int,
    data: IngestionJobUpdateDTO,
    service: Annotated[IngestionJobService, Depends(ingestion_job_service)],
) -> IngestionJobDetailDTO:
    try:
        # This is a simplified update - in practice you'd call a proper update method
        job = await service.repository.get_by_id(job_id)
        if job is None:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")

        update_data = data.model_dump(exclude_none=True, exclude_unset=True)
        for key, value in update_data.items():
            setattr(job, key, value)

        saved = await service.repository.save(job)
        from app.ingestion.infrastructure.mappers import map_ingestion_job_domain_to_detail_dto
        return map_ingestion_job_domain_to_detail_dto(saved)
    except IngestionError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")


@router.delete("/jobs/{job_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_ingestion_job(
    job_id: int,
    service: Annotated[IngestionJobService, Depends(ingestion_job_service)],
) -> None:
    deleted = await service.repository.delete(job_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")


# Quarantine endpoints
@router.post("/quarantine", response_model=QuarantineRecordDTO, status_code=status.HTTP_201_CREATED)
async def add_quarantine_record(
    data: QuarantineRecordDTO,
    service: Annotated[QuarantineService, Depends(quarantine_service)],
) -> QuarantineRecordDTO:
    return await service.add_record(data)


@router.post("/quarantine/batch", response_model=list[QuarantineRecordDTO], status_code=status.HTTP_201_CREATED)
async def add_quarantine_batch(
    records: list[QuarantineRecordDTO],
    service: Annotated[QuarantineService, Depends(quarantine_service)],
) -> list[QuarantineRecordDTO]:
    return await service.add_batch(records)


@router.get("/quarantine", response_model=QuarantineRecordListDTO)
async def list_quarantine_records(
    filters: Annotated[QuarantineRecordFilterDTO, Depends(quarantine_filter)],
    service: Annotated[QuarantineService, Depends(quarantine_service)],
) -> QuarantineRecordListDTO:
    return await service.list_records(filters)


@router.get("/quarantine/{record_id}", response_model=QuarantineRecordDTO)
async def get_quarantine_record(
    record_id: int,
    service: Annotated[QuarantineService, Depends(quarantine_service)],
) -> QuarantineRecordDTO:
    try:
        return await service.get_record(record_id)
    except IngestionError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Quarantine record not found")


@router.patch("/quarantine/{record_id}/resolve", response_model=QuarantineRecordDTO)
async def resolve_quarantine_record(
    record_id: int,
    service: Annotated[QuarantineService, Depends(quarantine_service)],
) -> QuarantineRecordDTO:
    resolved = await service.resolve_record(record_id)
    if not resolved:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Quarantine record not found")
    return await service.get_record(record_id)


@router.delete("/quarantine/{record_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_quarantine_record(
    record_id: int,
    service: Annotated[QuarantineService, Depends(quarantine_service)],
) -> None:
    deleted = await service.delete_record(record_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Quarantine record not found")