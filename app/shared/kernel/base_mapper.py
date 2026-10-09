"""Shared kernel base mappers - generic mapping utilities."""

from typing import Any
from pydantic import BaseModel


def model_dump_for_create(data: BaseModel) -> dict[str, Any]:
    """Convert Pydantic model to dict for ORM creation (exclude None)."""
    return data.model_dump(exclude_none=True, exclude={"id"})


def model_dump_for_update(data: BaseModel) -> dict[str, Any]:
    """Convert Pydantic model to dict for ORM update (exclude None and unset)."""
    return data.model_dump(exclude_none=True, exclude_unset=True, exclude={"id"})


def apply_update_to_domain(update_data: dict[str, Any], domain: object) -> object:
    """Apply update data to domain entity."""
    for field_name, field_value in update_data.items():
        setattr(domain, field_name, field_value)
    return domain


class DomainMapper:
    """Base class for domain mappers with common patterns."""

    @staticmethod
    def orm_to_domain(orm) -> object:
        """Convert ORM to domain. Override in subclass."""
        raise NotImplementedError

    @staticmethod
    def domain_to_orm(domain) -> object:
        """Convert domain to ORM. Override in subclass."""
        raise NotImplementedError

    @staticmethod
    def domain_to_dto(domain) -> object:
        """Convert domain to DTO. Override in subclass."""
        raise NotImplementedError

    @staticmethod
    def domain_to_detail_dto(domain) -> object:
        """Convert domain to detail DTO. Override in subclass."""
        raise NotImplementedError

    @staticmethod
    def create_dto_to_domain(data) -> dict[str, Any]:
        """Convert create DTO to domain dict. Override in subclass."""
        return model_dump_for_create(data)

    @staticmethod
    def update_dto_to_domain(data) -> dict[str, Any]:
        """Convert update DTO to domain dict. Override in subclass."""
        return model_dump_for_update(data)