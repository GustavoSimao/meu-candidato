"""Politician application service - use cases for politician domain."""

from app.politician.application.dtos import (
    PoliticianCreateDTO,
    PoliticianDetailDTO,
    PoliticianListDTO,
    PoliticianUpdateDTO,
)
from app.politician.application.filters import PoliticianFilterDTO
from app.politician.application.ports import PoliticianRepository
from app.politician.domain.entities import Politician
from app.politician.domain.exceptions import PoliticianNotFoundError
from app.politician.infrastructure.mappers import (
    map_politician_create_dto_to_domain,
    map_politician_domain_to_detail_dto,
    map_politician_domain_to_list_item_dto,
    map_politician_update_dto_to_domain,
)


class PoliticianService:
    """Application service for politician use cases."""

    def __init__(self, repository: PoliticianRepository):
        self.repository = repository

    async def create(self, data: PoliticianCreateDTO) -> PoliticianDetailDTO:
        """Create a new politician."""
        # Check for duplicate CPF
        if data.cpf and await self.repository.exists_by_cpf(data.cpf):
            from app.politician.domain.exceptions import DuplicateCPFError
            raise DuplicateCPFError(data.cpf)

        politician_data = map_politician_create_dto_to_domain(data)
        politician = Politician(**politician_data)
        saved = await self.repository.save(politician)
        return map_politician_domain_to_detail_dto(saved)

    async def get(self, politician_id: int) -> PoliticianDetailDTO:
        """Get a politician by ID."""
        politician = await self.repository.get_by_id(politician_id)
        if politician is None:
            raise PoliticianNotFoundError(politician_id)
        return map_politician_domain_to_detail_dto(politician)

    async def list(self, filters: PoliticianFilterDTO) -> PoliticianListDTO:
        """List politicians with pagination and filters."""
        politicians, total = await self.repository.list(filters)

        items = [map_politician_domain_to_list_item_dto(p) for p in politicians]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0

        return PoliticianListDTO(
            items=items,
            total=total,
            page=filters.page,
            per_page=filters.per_page,
            pages=pages,
        )

    async def update(self, politician_id: int, data: PoliticianUpdateDTO) -> PoliticianDetailDTO:
        """Update a politician."""
        politician = await self.repository.get_by_id(politician_id)
        if politician is None:
            raise PoliticianNotFoundError(politician_id)

        update_data = map_politician_update_dto_to_domain(data)
        for field_name, field_value in update_data.items():
            setattr(politician, field_name, field_value)

        saved = await self.repository.save(politician)
        return map_politician_domain_to_detail_dto(saved)

    async def delete(self, politician_id: int) -> bool:
        """Delete a politician."""
        return await self.repository.delete(politician_id)
