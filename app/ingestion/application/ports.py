"""Ingestion application ports - repository interfaces."""

from abc import ABC, abstractmethod
from typing import Optional, Tuple, List

from app.ingestion.domain.entities import IngestionJob, QuarantineRecord
from app.ingestion.application.filters import IngestionJobFilterDTO, QuarantineRecordFilterDTO


class IngestionJobRepository(ABC):
    @abstractmethod
    async def save(self, job: IngestionJob) -> IngestionJob:
        ...

    @abstractmethod
    async def get_by_id(self, job_id: int) -> Optional[IngestionJob]:
        ...

    @abstractmethod
    async def get_latest_by_source_dataset(
        self, data_source: str, dataset: str
    ) -> Optional[IngestionJob]:
        ...

    @abstractmethod
    async def list(self, filters: IngestionJobFilterDTO) -> Tuple[List[IngestionJob], int]:
        ...

    @abstractmethod
    async def delete(self, job_id: int) -> bool:
        ...


class QuarantineRepository(ABC):
    @abstractmethod
    async def save(self, record: QuarantineRecord) -> QuarantineRecord:
        ...

    @abstractmethod
    async def save_batch(self, records: List[QuarantineRecord]) -> List[QuarantineRecord]:
        ...

    @abstractmethod
    async def get_by_id(self, record_id: int) -> Optional[QuarantineRecord]:
        ...

    @abstractmethod
    async def list(self, filters: QuarantineRecordFilterDTO) -> Tuple[List[QuarantineRecord], int]:
        ...

    @abstractmethod
    async def mark_resolved(self, record_id: int) -> bool:
        ...

    @abstractmethod
    async def delete(self, record_id: int) -> bool:
        ...