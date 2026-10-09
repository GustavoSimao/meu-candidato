"""Politician infrastructure repository - SQLAlchemy implementation."""

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.politician.application.filters import PoliticianFilterDTO
from app.politician.application.ports import PoliticianRepository
from app.politician.domain.entities import Politician
from app.politician.infrastructure.mappers import (
    map_politician_domain_to_orm,
    map_politician_orm_to_domain,
)
from app.politician.infrastructure.models import PoliticianORM


class SQLAlchemyPoliticianRepository(PoliticianRepository):
    """SQLAlchemy implementation of PoliticianRepository."""

    def __init__(self, session: AsyncSession):
        self.session = session

    async def save(self, politician: Politician) -> Politician:
        orm = map_politician_domain_to_orm(politician)
        self.session.add(orm)
        await self.session.flush()
        await self.session.refresh(orm, attribute_names=["mandates"])
        return map_politician_orm_to_domain(orm)

    async def upsert(self, politician: Politician) -> Politician:
        """Upsert a politician: try CPF first, then external_id, then insert."""
        # Try CPF first (unification by CPF - ADR-002)
        if politician.cpf:
            existing = await self.get_by_cpf(politician.cpf)
            if existing is not None:
                # Update existing record (unify by CPF)
                return await self._update(existing.id, politician)

        # Try external_id
        if politician.external_id is not None:
            existing = await self.get_by_external_id(politician.external_id)
            if existing is not None:
                # Update existing record
                return await self._update(existing.id, politician)

        # Insert new
        return await self.save(politician)

    async def _update(self, politician_id: int, politician: Politician) -> Politician:
        """Update an existing politician."""
        orm = await self.session.get(PoliticianORM, politician_id)
        if orm is None:
            raise ValueError(f"Politician {politician_id} not found")

        # Update fields (keep id, created_at)
        orm.name = politician.name
        orm.party = politician.party
        orm.uf = politician.uf
        orm.number = politician.number
        orm.external_id = politician.external_id
        orm.cpf = politician.cpf
        orm.email = politician.email
        orm.office_address = politician.office_address
        orm.office_phone = politician.office_phone
        orm.biography = politician.biography
        orm.social_media = politician.social_media
        orm.education = politician.education
        orm.photo_url = politician.photo_url

        await self.session.flush()
        await self.session.refresh(orm, attribute_names=["mandates"])
        return map_politician_orm_to_domain(orm)

    async def get_by_id(self, politician_id: int) -> Politician | None:
        query = (
            select(PoliticianORM)
            .options(selectinload(PoliticianORM.mandates))
            .where(PoliticianORM.id == politician_id)
        )
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return None
        return map_politician_orm_to_domain(orm)

    async def get_by_external_id(self, external_id: int) -> Politician | None:
        query = (
            select(PoliticianORM)
            .options(selectinload(PoliticianORM.mandates))
            .where(PoliticianORM.external_id == external_id)
        )
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return None
        return map_politician_orm_to_domain(orm)

    async def get_by_cpf(self, cpf: str) -> Politician | None:
        query = (
            select(PoliticianORM)
            .options(selectinload(PoliticianORM.mandates))
            .where(PoliticianORM.cpf == cpf)
        )
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return None
        return map_politician_orm_to_domain(orm)

    async def list(self, filters: PoliticianFilterDTO) -> tuple[list[Politician], int]:
        query = select(PoliticianORM).options(selectinload(PoliticianORM.mandates))

        if filters.uf:
            query = query.where(PoliticianORM.uf == filters.uf.upper())
        if filters.party:
            query = query.where(PoliticianORM.party.ilike(f"%{filters.party}%"))

        # Count total
        total_query = select(func.count()).select_from(query.subquery())
        total = await self.session.scalar(total_query) or 0

        # Paginate
        query = (
            query.order_by(PoliticianORM.name)
            .offset((filters.page - 1) * filters.per_page)
            .limit(filters.per_page)
        )
        result = await self.session.execute(query)
        orms = result.scalars().all()

        politicians = [map_politician_orm_to_domain(orm) for orm in orms]
        return politicians, total

    async def exists_by_cpf(self, cpf: str) -> bool:
        query = select(PoliticianORM.id).where(PoliticianORM.cpf == cpf)
        result = await self.session.execute(query)
        return result.scalar_one_or_none() is not None

    async def delete(self, politician_id: int) -> bool:
        query = select(PoliticianORM).where(PoliticianORM.id == politician_id)
        result = await self.session.execute(query)
        orm = result.scalar_one_or_none()
        if orm is None:
            return False
        await self.session.delete(orm)
        await self.session.flush()
        return True
