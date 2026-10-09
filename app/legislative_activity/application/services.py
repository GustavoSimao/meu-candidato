"""Legislative Activity application services - use cases."""

from app.legislative_activity.application.dtos import (
    PropositionCreateDTO,
    PropositionDetailDTO,
    PropositionDTO,
    PropositionListDTO,
    PropositionUpdateDTO,
    VoteDTO,
    VoteDetailDTO,
    VoteListDTO,
    VoteStatsDTO,
)
from app.legislative_activity.application.filters import PropositionFilterDTO, VoteFilterDTO
from app.legislative_activity.application.ports import PropositionRepository, VoteRepository
from app.legislative_activity.domain.entities import Proposition, Vote
from app.legislative_activity.domain.exceptions import PropositionNotFoundError, VoteNotFoundError
from app.legislative_activity.infrastructure.mappers import (
    map_proposition_create_dto_to_domain,
    map_proposition_domain_to_detail_dto,
    map_proposition_domain_to_dto,
    map_proposition_update_dto_to_domain,
    map_vote_domain_to_detail_dto,
    map_vote_domain_to_dto,
)


class VoteService:
    def __init__(self, repository: VoteRepository):
        self.repository = repository

    async def create(self, data: VoteDTO) -> VoteDetailDTO:
        vote = Vote(**data.model_dump(exclude={"id"}))
        saved = await self.repository.save(vote)
        return map_vote_domain_to_detail_dto(saved)

    async def get(self, vote_id: int) -> VoteDetailDTO:
        vote = await self.repository.get_by_id(vote_id)
        if vote is None:
            raise VoteNotFoundError(vote_id)
        return map_vote_domain_to_detail_dto(vote)

    async def list(self, filters: VoteFilterDTO) -> VoteListDTO:
        votes, total = await self.repository.list(filters)
        items = [map_vote_domain_to_detail_dto(v) for v in votes]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return VoteListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def get_vote_stats(self, politician_id: int | None = None) -> VoteStatsDTO:
        stats = await self.repository.get_vote_stats(politician_id)
        return VoteStatsDTO(**stats)

    async def delete(self, vote_id: int) -> bool:
        return await self.repository.delete(vote_id)


class PropositionService:
    def __init__(self, repository: PropositionRepository):
        self.repository = repository

    async def create(self, data: PropositionCreateDTO) -> PropositionDetailDTO:
        # Check for duplicate external_id
        if await self.repository.get_by_external_id(data.external_id):
            from app.legislative_activity.domain.exceptions import LegislativeActivityDomainError
            raise LegislativeActivityDomainError(
                f"Proposition with external_id {data.external_id} already exists",
                code="DUPLICATE_EXTERNAL_ID",
                details={"external_id": data.external_id}
            )

        proposition_data = map_proposition_create_dto_to_domain(data)
        proposition = Proposition(**proposition_data)
        saved = await self.repository.save(proposition)
        return map_proposition_domain_to_detail_dto(saved)

    async def get(self, proposition_id: int) -> PropositionDetailDTO:
        proposition = await self.repository.get_by_id(proposition_id)
        if proposition is None:
            raise PropositionNotFoundError(proposition_id)
        return map_proposition_domain_to_detail_dto(proposition)

    async def get_by_external_id(self, external_id: str) -> PropositionDetailDTO:
        proposition = await self.repository.get_by_external_id(external_id)
        if proposition is None:
            raise PropositionNotFoundError(external_id)
        return map_proposition_domain_to_detail_dto(proposition)

    async def list(self, filters: PropositionFilterDTO) -> PropositionListDTO:
        propositions, total = await self.repository.list(filters)
        items = [map_proposition_domain_to_dto(p) for p in propositions]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return PropositionListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def update(self, proposition_id: int, data: PropositionUpdateDTO) -> PropositionDetailDTO:
        proposition = await self.repository.get_by_id(proposition_id)
        if proposition is None:
            raise PropositionNotFoundError(proposition_id)

        update_data = map_proposition_update_dto_to_domain(data)
        for key, value in update_data.items():
            setattr(proposition, key, value)

        saved = await self.repository.save(proposition)
        return map_proposition_domain_to_detail_dto(saved)

    async def delete(self, proposition_id: int) -> bool:
        return await self.repository.delete(proposition_id)