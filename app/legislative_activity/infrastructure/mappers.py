"""Legislative Activity infrastructure mappers - conversion between Domain, ORM, and DTO."""

from datetime import date
from typing import Any

from app.legislative_activity.domain.entities import Proposition, Vote
from app.legislative_activity.domain.value_objects import VoteValue
from app.legislative_activity.infrastructure.models import PropositionORM, VoteORM
from app.legislative_activity.application.dtos import (
    PropositionDTO,
    PropositionDetailDTO,
    VoteDTO,
    VoteDetailDTO,
)


VOTE_VALUE_TO_PORTUGUESE = {
    VoteValue.FAVOR: "favor",
    VoteValue.CONTRA: "contra",
    VoteValue.AUSENTE: "ausente",
    VoteValue.ABSTENCAO: "abstencao",
    VoteValue.OBSTRUCAO: "obstrucao",
    VoteValue.ART17: "art17",
    VoteValue.DESCONHECIDO: "desconhecido",
}


def vote_value_to_portuguese(vote_value: VoteValue) -> str:
    """Convert VoteValue enum to Portuguese label for API."""
    return VOTE_VALUE_TO_PORTUGUESE.get(vote_value, vote_value.value)


def map_vote_orm_to_domain(orm: VoteORM) -> Vote:
    return Vote(
        id=orm.id,
        politician_id=orm.politician_id,
        proposition_id=orm.proposition_id,
        session_date=orm.session_date,
        vote_value=VoteValue(orm.vote_value),
        session_number=orm.session_number,
    )


def map_vote_domain_to_orm(domain: Vote) -> VoteORM:
    return VoteORM(
        id=domain.id,
        politician_id=domain.politician_id,
        proposition_id=domain.proposition_id,
        session_date=domain.session_date,
        vote_value=domain.vote_value.value,
        session_number=domain.session_number,
    )


def map_vote_domain_to_dto(domain: Vote) -> VoteDTO:
    return VoteDTO(
        id=domain.id,
        politician_id=domain.politician_id,
        proposition_id=domain.proposition_id,
        session_date=domain.session_date,
        vote_value=vote_value_to_portuguese(domain.vote_value),
        session_number=domain.session_number,
    )


def map_vote_domain_to_detail_dto(domain: Vote) -> VoteDetailDTO:
    return VoteDetailDTO(
        id=domain.id,
        politician_id=domain.politician_id,
        proposition_id=domain.proposition_id,
        session_date=domain.session_date,
        vote_value=vote_value_to_portuguese(domain.vote_value),
        session_number=domain.session_number,
    )


def map_proposition_orm_to_domain(orm: PropositionORM) -> Proposition:
    return Proposition(
        id=orm.id,
        politician_id=orm.politician_id,
        external_id=orm.external_id,
        type=orm.type,
        title=orm.title,
        house=orm.house,
        summary=orm.summary,
        status=orm.status,
        presentation_date=orm.presentation_date,
        url=orm.url,
    )


def map_proposition_domain_to_orm(domain: Proposition) -> PropositionORM:
    return PropositionORM(
        id=domain.id,
        politician_id=domain.politician_id,
        external_id=domain.external_id,
        type=domain.type,
        title=domain.title,
        house=domain.house,
        summary=domain.summary,
        status=domain.status,
        presentation_date=domain.presentation_date,
        url=domain.url,
    )


def map_proposition_domain_to_dto(domain: Proposition) -> PropositionDTO:
    return PropositionDTO(
        id=domain.id,
        external_id=domain.external_id,
        politician_id=domain.politician_id,
        type=domain.type,
        title=domain.title,
        house=domain.house,
        summary=domain.summary,
        status=domain.status,
        presentation_date=domain.presentation_date,
        url=domain.url,
    )


def map_proposition_domain_to_detail_dto(domain: Proposition) -> PropositionDetailDTO:
    return PropositionDetailDTO(
        id=domain.id,
        external_id=domain.external_id,
        politician_id=domain.politician_id,
        type=domain.type,
        title=domain.title,
        house=domain.house,
        summary=domain.summary,
        status=domain.status,
        presentation_date=domain.presentation_date,
        url=domain.url,
    )


def map_proposition_create_dto_to_domain(data: PropositionDTO) -> dict[str, Any]:
    return data.model_dump(exclude_none=True, exclude={"id"})


def map_proposition_update_dto_to_domain(data: PropositionDTO) -> dict[str, Any]:
    return data.model_dump(exclude_none=True, exclude_unset=True, exclude={"id"})