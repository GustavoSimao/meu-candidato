"""Shared kernel - core utilities shared across all bounded contexts."""

from app.shared.kernel.config import settings
from app.shared.kernel.database import (
    Base,
    async_session_maker,
    close_db,
    engine,
    get_session,
    init_db,
)
from app.shared.kernel.exceptions import (
    BusinessRuleError,
    ConflictError,
    DomainError,
    NotFoundError,
    ValidationError,
)
from app.shared.kernel.mappers import (
    DomainMapper,
    Mapper,
    model_dump_for_create,
    model_dump_for_update,
)
from app.shared.kernel.base_repository import BaseRepository
from app.shared.kernel.base_service import BaseService
from app.shared.kernel.base_mapper import DomainMapper as BaseDomainMapper, model_dump_for_create as base_model_dump_for_create, model_dump_for_update as base_model_dump_for_update, apply_update_to_domain

__all__ = [
    "settings",
    "Base",
    "engine",
    "async_session_maker",
    "get_session",
    "init_db",
    "close_db",
    "DomainError",
    "NotFoundError",
    "ValidationError",
    "ConflictError",
    "BusinessRuleError",
    "Mapper",
    "DomainMapper",
    "model_dump_for_create",
    "model_dump_for_update",
    "BaseRepository",
    "BaseService",
    "BaseDomainMapper",
    "base_model_dump_for_create",
    "base_model_dump_for_update",
    "apply_update_to_domain",
]
