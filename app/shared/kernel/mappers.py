"""Shared kernel mappers - base interfaces for domain/ORM/DTO mapping."""

from abc import ABC, abstractmethod
from typing import Any, Generic, TypeVar

DomainT = TypeVar("DomainT")
ORMT = TypeVar("ORMT")
DTOT = TypeVar("DTOT")


class Mapper(Generic[DomainT, ORMT, DTOT], ABC):
    """Base mapper interface."""

    @abstractmethod
    def to_domain(self, orm: ORMT) -> DomainT:
        """Convert ORM model to domain entity."""
        ...

    @abstractmethod
    def to_orm(self, domain: DomainT) -> ORMT:
        """Convert domain entity to ORM model."""
        ...

    @abstractmethod
    def to_dto(self, domain: DomainT) -> DTOT:
        """Convert domain entity to DTO."""
        ...


class DomainMapper(Mapper[DomainT, ORMT, DTOT], ABC):
    """Mapper that handles all three conversions: Domain <-> ORM <-> DTO."""

    @abstractmethod
    def orm_to_domain(self, orm: ORMT) -> DomainT:
        """Convert ORM model to domain entity."""
        ...

    @abstractmethod
    def domain_to_orm(self, domain: DomainT) -> ORMT:
        """Convert domain entity to ORM model."""
        ...

    @abstractmethod
    def domain_to_dto(self, domain: DomainT) -> DTOT:
        """Convert domain entity to DTO."""
        ...

    def to_domain(self, orm: ORMT) -> DomainT:
        return self.orm_to_domain(orm)

    def to_orm(self, domain: DomainT) -> ORMT:
        return self.domain_to_orm(domain)

    def to_dto(self, domain: DomainT) -> DTOT:
        return self.domain_to_dto(domain)


def model_dump_for_create(data: Any) -> dict[str, Any]:
    """Convert Pydantic model to dict for ORM creation (exclude None)."""
    return data.model_dump(exclude_none=True)


def model_dump_for_update(data: Any) -> dict[str, Any]:
    """Convert Pydantic model to dict for ORM update (exclude None and unset)."""
    return data.model_dump(exclude_none=True, exclude_unset=True)
