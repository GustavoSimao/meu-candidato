"""Politician infrastructure mappers - conversion between Domain, ORM, and DTO."""

from datetime import date
from typing import Any

from app.politician.application.dtos import (
    MandateDTO,
    PoliticianBaseDTO,
    PoliticianDetailDTO,
    PoliticianListItemDTO,
)
from app.politician.domain.entities import Mandate, Politician
from app.politician.infrastructure.models import MandateORM, PoliticianORM


def map_mandate_orm_to_domain(orm: MandateORM) -> Mandate:
    """Convert Mandate ORM to domain entity."""
    return Mandate(
        id=orm.id,
        politician_id=orm.politician_id,
        house=orm.house,
        role=orm.role,
        uf=orm.uf,
        start_date=orm.start_date,
        end_date=orm.end_date,
        is_suplente=orm.is_suplente,
    )


def map_mandate_domain_to_orm(domain: Mandate) -> MandateORM:
    """Convert Mandate domain entity to ORM."""
    return MandateORM(
        id=domain.id,
        politician_id=domain.politician_id,
        house=domain.house,
        role=domain.role,
        uf=domain.uf,
        start_date=domain.start_date,
        end_date=domain.end_date,
        is_suplente=domain.is_suplente,
    )


def map_mandate_domain_to_dto(domain: Mandate) -> MandateDTO:
    """Convert Mandate domain entity to DTO."""
    return MandateDTO(
        house=domain.house,
        role=domain.role,
        uf=domain.uf,
        start_date=domain.start_date,
        end_date=domain.end_date,
        is_suplente=domain.is_suplente,
    )


def map_politician_orm_to_domain(orm: PoliticianORM) -> Politician:
    """Convert Politician ORM to domain entity."""
    mandates = [map_mandate_orm_to_domain(m) for m in orm.mandates]
    return Politician(
        id=orm.id,
        name=orm.name,
        party=orm.party,
        uf=orm.uf,
        number=orm.number,
        external_id=orm.external_id,
        cpf=orm.cpf,
        email=orm.email,
        office_address=orm.office_address,
        office_phone=orm.office_phone,
        biography=orm.biography,
        social_media=orm.social_media,
        education=orm.education,
        photo_url=orm.photo_url,
        mandates=mandates,
        created_at=orm.created_at,
        updated_at=orm.updated_at,
    )


def map_politician_domain_to_orm(domain: Politician) -> PoliticianORM:
    """Convert Politician domain entity to ORM."""
    return PoliticianORM(
        id=domain.id,
        name=domain.name,
        party=domain.party,
        uf=domain.uf,
        number=domain.number,
        external_id=domain.external_id,
        cpf=domain.cpf,
        email=domain.email,
        office_address=domain.office_address,
        office_phone=domain.office_phone,
        biography=domain.biography,
        social_media=domain.social_media,
        education=domain.education,
        photo_url=domain.photo_url,
        created_at=domain.created_at or date.today(),
        updated_at=domain.updated_at or date.today(),
    )


def map_politician_domain_to_base_dto(domain: Politician) -> PoliticianBaseDTO:
    """Convert Politician domain entity to base DTO."""
    return PoliticianBaseDTO(
        name=domain.name,
        party=domain.party,
        uf=domain.uf,
        number=domain.number,
        external_id=domain.external_id,
        cpf=domain.cpf,
        email=domain.email,
        office_address=domain.office_address,
        office_phone=domain.office_phone,
        biography=domain.biography,
        social_media=domain.social_media,
        education=domain.education,
        photo_url=domain.photo_url,
    )


def map_politician_domain_to_list_item_dto(
    domain: Politician, badges: list[str] | None = None
) -> PoliticianListItemDTO:
    """Convert Politician domain entity to list item DTO."""
    base_dto = map_politician_domain_to_base_dto(domain)
    return PoliticianListItemDTO(
        id=domain.id,
        name=base_dto.name,
        party=base_dto.party,
        uf=base_dto.uf,
        number=base_dto.number,
        cpf=base_dto.cpf,
        email=base_dto.email,
        office_address=base_dto.office_address,
        office_phone=base_dto.office_phone,
        biography=base_dto.biography,
        social_media=base_dto.social_media,
        education=base_dto.education,
        photo_url=base_dto.photo_url,
        badges=badges or [],
    )


def map_politician_domain_to_detail_dto(
    domain: Politician, badges: list[str] | None = None
) -> PoliticianDetailDTO:
    """Convert Politician domain entity to detail DTO."""
    base_dto = map_politician_domain_to_base_dto(domain)
    mandates_dto = [map_mandate_domain_to_dto(m) for m in domain.mandates]
    return PoliticianDetailDTO(
        id=domain.id,
        name=base_dto.name,
        party=base_dto.party,
        uf=base_dto.uf,
        number=base_dto.number,
        cpf=base_dto.cpf,
        email=base_dto.email,
        office_address=base_dto.office_address,
        office_phone=base_dto.office_phone,
        biography=base_dto.biography,
        social_media=base_dto.social_media,
        education=base_dto.education,
        photo_url=base_dto.photo_url,
        mandates=mandates_dto,
        badges=badges or [],
    )


def map_politician_create_dto_to_domain(data: PoliticianBaseDTO) -> dict[str, Any]:
    """Convert PoliticianCreate DTO to dict for ORM creation."""
    return data.model_dump(exclude_none=True)


def map_politician_update_dto_to_domain(data: PoliticianBaseDTO) -> dict[str, Any]:
    """Convert PoliticianUpdate DTO to dict for ORM update."""
    return data.model_dump(exclude_none=True, exclude_unset=True)
