"""Shared kernel base repository - generic CRUD operations."""

from abc import ABC, abstractmethod
from typing import Generic, TypeVar, Optional, List, Tuple
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, func
from sqlalchemy.orm import selectinload

DomainT = TypeVar("DomainT")
ORMT = TypeVar("ORMT")
FilterT = TypeVar("FilterT")


class BaseRepository(Generic[DomainT, ORMT, FilterT], ABC):
    """Generic base repository with common CRUD operations."""

    def __init__(self, session: AsyncSession, orm_class: type[ORMT]):
        self.session = session
        self.orm_class = orm_class

    @abstractmethod
    def _orm_to_domain(self, orm: ORMT) -> DomainT:
        """Convert ORM model to domain entity."""
        ...

    @abstractmethod
    def _domain_to_orm(self, domain: DomainT) -> ORMT:
        """Convert domain entity to ORM model."""
        ...

    def _apply_filters(self, query, filters: FilterT):
        """Apply filters to query. Override in subclasses."""
        return query

    def _get_eager_loads(self):
        """Get relationships to eager load. Override in subclasses."""
        return []

    async def save(self, domain: DomainT) -> DomainT:
        """Save domain entity and return with generated ID."""
        orm = self._domain_to_orm(domain)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm, attribute_names=self._get_eager_loads())
        return self._orm_to_domain(orm)

    async def get_by_id(self, entity_id: int) -> Optional[DomainT]:
        """Get entity by ID with eager loading."""
        query = select(self.orm_class).where(self.orm_class.id == entity_id)
        for load in self._get_eager_loads():
            query = query.options(selectinload(load))
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        return self._orm_to_domain(orm) if orm else None

    async def list(self, filters: FilterT) -> Tuple[List[DomainT], int]:
        """List entities with filters and pagination."""
        query = select(self.orm_class)
        for load in self._get_eager_loads():
            query = query.options(selectinload(load))

        query = self._apply_filters(query, filters)

        # Count total
        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        # Paginate
        query = (
            query.order_by(self.orm_class.id)
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        return [self._orm_to_domain(orm) for orm in orms], total

    async def delete(self, entity_id: int) -> bool:
        """Delete entity by ID."""
        query = select(self.orm_class).where(self.orm_class.id == entity_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True