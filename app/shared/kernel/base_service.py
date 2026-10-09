"""Shared kernel base service - generic service operations."""

from abc import ABC, abstractmethod
from typing import Generic, TypeVar, Optional
from app.shared.kernel.exceptions import NotFoundError

RepositoryT = TypeVar("RepositoryT")
CreateDTO = TypeVar("CreateDTO")
UpdateDTO = TypeVar("UpdateDTO")
DetailDTO = TypeVar("DetailDTO")
ListDTO = TypeVar("ListDTO")
FilterDTO = TypeVar("FilterDTO")


class BaseService(Generic[RepositoryT, CreateDTO, UpdateDTO, DetailDTO, ListDTO, FilterDTO], ABC):
    """Generic base service with common CRUD operations."""

    def __init__(self, repository: RepositoryT):
        self.repository = repository

    @abstractmethod
    async def _create_entity(self, data: CreateDTO) -> DetailDTO:
        """Create entity from DTO. Implement in subclass."""
        ...

    @abstractmethod
    async def _update_entity(self, entity_id: int, data: UpdateDTO) -> DetailDTO:
        """Update entity from DTO. Implement in subclass."""
        ...

    @abstractmethod
    def _entity_to_detail_dto(self, entity) -> DetailDTO:
        """Convert entity to detail DTO. Implement in subclass."""
        ...

    @abstractmethod
    def _entity_to_list_item_dto(self, entity) -> DetailDTO:
        """Convert entity to list item DTO. Implement in subclass."""
        ...

    async def create(self, data: CreateDTO) -> DetailDTO:
        """Create a new entity."""
        return await self._create_entity(data)

    async def get(self, entity_id: int) -> DetailDTO:
        """Get entity by ID."""
        entity = await self.repository.get_by_id(entity_id)
        if entity is None:
            raise NotFoundError(self._entity_name(), entity_id)
        return self._entity_to_detail_dto(entity)

    async def list(self, filters: FilterDTO) -> ListDTO:
        """List entities with pagination."""
        entities, total = await self.repository.list(filters)
        items = [self._entity_to_list_item_dto(e) for e in entities]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return self._build_list_dto(items, total, filters.page, filters.per_page, pages)

    async def update(self, entity_id: int, data: UpdateDTO) -> DetailDTO:
        """Update an entity."""
        return await self._update_entity(entity_id, data)

    async def delete(self, entity_id: int) -> bool:
        """Delete an entity."""
        return await self.repository.delete(entity_id)

    @abstractmethod
    def _entity_name(self) -> str:
        """Return entity name for error messages."""
        ...

    @abstractmethod
    def _build_list_dto(self, items, total, page, per_page, pages) -> ListDTO:
        """Build list DTO. Implement in subclass."""
        ...