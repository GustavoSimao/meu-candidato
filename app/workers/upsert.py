"""Upsert repository for ingestion entities.

Handles bulk upserts for Mandate, Proposition, Vote, and Expense.
Uses SQLAlchemy 2.0 insert().on_conflict_do_update() for idempotent upserts.
"""

from datetime import date

from sqlalchemy import delete, insert, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.financial.domain.entities import Expense
from app.financial.infrastructure.models import ExpenseORM
from app.legislative_activity.domain.entities import Proposition, Vote
from app.legislative_activity.infrastructure.models import PropositionORM, VoteORM
from app.politician.domain.entities import Mandate
from app.politician.infrastructure.models import MandateORM, PoliticianORM


class UpsertRepository:
    """Repository for bulk upsert operations during ingestion."""

    def __init__(self, session: AsyncSession):
        self.session = session

    # --- Mandate ---

    async def upsert_mandate(self, mandate: Mandate) -> Mandate:
        """Upsert a mandate by (politician_id, house, start_date)."""
        stmt = insert(MandateORM).values(
            politician_id=mandate.politician_id,
            house=mandate.house,
            role=mandate.role,
            uf=mandate.uf,
            start_date=mandate.start_date,
            end_date=mandate.end_date,
            is_suplente=mandate.is_suplente,
        )
        stmt = stmt.on_conflict_do_update(
            index_elements=["politician_id", "house", "start_date"],
            set_={
                "role": mandate.role,
                "uf": mandate.uf,
                "end_date": mandate.end_date,
                "is_suplente": mandate.is_suplente,
            },
        )
        result = await self.session.execute(stmt)
        await self.session.flush()

        # Get the ID (inserted or existing)
        mandate_id = result.inserted_primary_key[0] if result.inserted_primary_key else None
        if mandate_id is None:
            # Conflict occurred, fetch existing
            existing = await self.session.execute(
                select(MandateORM).where(
                    MandateORM.politician_id == mandate.politician_id,
                    MandateORM.house == mandate.house,
                    MandateORM.start_date == mandate.start_date,
                )
            )
            orm = existing.scalar_one_or_none()
            mandate_id = orm.id if orm else None

        return Mandate(
            id=mandate_id,
            politician_id=mandate.politician_id,
            house=mandate.house,
            role=mandate.role,
            uf=mandate.uf,
            start_date=mandate.start_date,
            end_date=mandate.end_date,
            is_suplente=mandate.is_suplente,
        )

    async def upsert_mandates_batch(self, mandates: list[Mandate]) -> int:
        """Upsert a batch of mandates. Returns count of upserted records."""
        if not mandates:
            return 0

        values = [
            {
                "politician_id": m.politician_id,
                "house": m.house,
                "role": m.role,
                "uf": m.uf,
                "start_date": m.start_date,
                "end_date": m.end_date,
                "is_suplente": m.is_suplente,
            }
            for m in mandates
        ]

        stmt = insert(MandateORM).values(values)
        stmt = stmt.on_conflict_do_update(
            index_elements=["politician_id", "house", "start_date"],
            set_={
                "role": stmt.excluded.role,
                "uf": stmt.excluded.uf,
                "end_date": stmt.excluded.end_date,
                "is_suplente": stmt.excluded.is_suplente,
            },
        )
        await self.session.execute(stmt)
        await self.session.flush()
        return len(mandates)

    # --- Proposition ---

    async def upsert_proposition(self, proposition: Proposition) -> Proposition:
        """Upsert a proposition by external_id."""
        stmt = insert(PropositionORM).values(
            external_id=proposition.external_id,
            politician_id=proposition.politician_id,
            type=proposition.type,
            title=proposition.title,
            house=proposition.house,
            summary=proposition.summary,
            status=proposition.status,
            presentation_date=proposition.presentation_date,
            url=proposition.url,
        )
        stmt = stmt.on_conflict_do_update(
            index_elements=["external_id"],
            set_={
                "politician_id": proposition.politician_id,
                "type": proposition.type,
                "title": proposition.title,
                "house": proposition.house,
                "summary": proposition.summary,
                "status": proposition.status,
                "presentation_date": proposition.presentation_date,
                "url": proposition.url,
            },
        )
        result = await self.session.execute(stmt)
        await self.session.flush()

        proposition_id = result.inserted_primary_key[0] if result.inserted_primary_key else None
        if proposition_id is None:
            existing = await self.session.execute(
                select(PropositionORM).where(
                    PropositionORM.external_id == proposition.external_id
                )
            )
            orm = existing.scalar_one_or_none()
            proposition_id = orm.id if orm else None

        return Proposition(
            id=proposition_id,
            politician_id=proposition.politician_id,
            external_id=proposition.external_id,
            type=proposition.type,
            title=proposition.title,
            house=proposition.house,
            summary=proposition.summary,
            status=proposition.status,
            presentation_date=proposition.presentation_date,
            url=proposition.url,
        )

    async def upsert_propositions_batch(self, propositions: list[Proposition]) -> int:
        """Upsert a batch of propositions. Returns count of upserted records."""
        if not propositions:
            return 0

        values = [
            {
                "external_id": p.external_id,
                "politician_id": p.politician_id,
                "type": p.type,
                "title": p.title,
                "house": p.house,
                "summary": p.summary,
                "status": p.status,
                "presentation_date": p.presentation_date,
                "url": p.url,
            }
            for p in propositions
        ]

        stmt = insert(PropositionORM).values(values)
        stmt = stmt.on_conflict_do_update(
            index_elements=["external_id"],
            set_={
                "politician_id": stmt.excluded.politician_id,
                "type": stmt.excluded.type,
                "title": stmt.excluded.title,
                "house": stmt.excluded.house,
                "summary": stmt.excluded.summary,
                "status": stmt.excluded.status,
                "presentation_date": stmt.excluded.presentation_date,
                "url": stmt.excluded.url,
            },
        )
        await self.session.execute(stmt)
        await self.session.flush()
        return len(propositions)

    # --- Vote ---

    async def upsert_vote(self, vote: Vote) -> Vote:
        """Upsert a vote by (politician_id, proposition_id)."""
        stmt = insert(VoteORM).values(
            politician_id=vote.politician_id,
            proposition_id=vote.proposition_id,
            session_date=vote.session_date,
            vote_value=vote.vote_value.value if hasattr(vote.vote_value, "value") else vote.vote_value,
            session_number=vote.session_number,
        )
        stmt = stmt.on_conflict_do_update(
            index_elements=["politician_id", "proposition_id"],
            set_={
                "session_date": stmt.excluded.session_date,
                "vote_value": stmt.excluded.vote_value,
                "session_number": stmt.excluded.session_number,
            },
        )
        result = await self.session.execute(stmt)
        await self.session.flush()

        vote_id = result.inserted_primary_key[0] if result.inserted_primary_key else None
        if vote_id is None:
            existing = await self.session.execute(
                select(VoteORM).where(
                    VoteORM.politician_id == vote.politician_id,
                    VoteORM.proposition_id == vote.proposition_id,
                )
            )
            orm = existing.scalar_one_or_none()
            vote_id = orm.id if orm else None

        return Vote(
            id=vote_id,
            politician_id=vote.politician_id,
            proposition_id=vote.proposition_id,
            session_date=vote.session_date,
            vote_value=vote.vote_value,
            session_number=vote.session_number,
        )

    async def upsert_votes_batch(self, votes: list[Vote]) -> int:
        """Upsert a batch of votes. Returns count of upserted records."""
        if not votes:
            return 0

        values = [
            {
                "politician_id": v.politician_id,
                "proposition_id": v.proposition_id,
                "session_date": v.session_date,
                "vote_value": v.vote_value.value if hasattr(v.vote_value, "value") else v.vote_value,
                "session_number": v.session_number,
            }
            for v in votes
        ]

        stmt = insert(VoteORM).values(values)
        stmt = stmt.on_conflict_do_update(
            index_elements=["politician_id", "proposition_id"],
            set_={
                "session_date": stmt.excluded.session_date,
                "vote_value": stmt.excluded.vote_value,
                "session_number": stmt.excluded.session_number,
            },
        )
        await self.session.execute(stmt)
        await self.session.flush()
        return len(votes)

    # --- Expense ---

    async def upsert_expense(self, expense: Expense) -> Expense:
        """Upsert an expense by (politician_id, expense_type, expense_date, document_number)."""
        stmt = insert(ExpenseORM).values(
            politician_id=expense.politician_id,
            expense_type=expense.expense_type,
            amount=expense.amount,
            expense_date=expense.expense_date,
            year=expense.year,
            month=expense.month,
            description=expense.description,
            provider=expense.provider,
            document_number=expense.document_number,
            document_url=expense.document_url,
        )
        stmt = stmt.on_conflict_do_update(
            index_elements=["politician_id", "expense_type", "expense_date", "document_number"],
            set_={
                "amount": stmt.excluded.amount,
                "year": stmt.excluded.year,
                "month": stmt.excluded.month,
                "description": stmt.excluded.description,
                "provider": stmt.excluded.provider,
                "document_url": stmt.excluded.document_url,
            },
        )
        result = await self.session.execute(stmt)
        await self.session.flush()

        expense_id = result.inserted_primary_key[0] if result.inserted_primary_key else None
        if expense_id is None:
            existing = await self.session.execute(
                select(ExpenseORM).where(
                    ExpenseORM.politician_id == expense.politician_id,
                    ExpenseORM.expense_type == expense.expense_type,
                    ExpenseORM.expense_date == expense.expense_date,
                    ExpenseORM.document_number == expense.document_number,
                )
            )
            orm = existing.scalar_one_or_none()
            expense_id = orm.id if orm else None

        return Expense(
            id=expense_id,
            politician_id=expense.politician_id,
            expense_type=expense.expense_type,
            amount=expense.amount,
            expense_date=expense.expense_date,
            year=expense.year,
            month=expense.month,
            description=expense.description,
            provider=expense.provider,
            document_number=expense.document_number,
            document_url=expense.document_url,
        )

    async def upsert_expenses_batch(self, expenses: list[Expense]) -> int:
        """Upsert a batch of expenses. Returns count of upserted records."""
        if not expenses:
            return 0

        values = [
            {
                "politician_id": e.politician_id,
                "expense_type": e.expense_type,
                "amount": e.amount,
                "expense_date": e.expense_date,
                "year": e.year,
                "month": e.month,
                "description": e.description,
                "provider": e.provider,
                "document_number": e.document_number,
                "document_url": e.document_url,
            }
            for e in expenses
        ]

        stmt = insert(ExpenseORM).values(values)
        stmt = stmt.on_conflict_do_update(
            index_elements=["politician_id", "expense_type", "expense_date", "document_number"],
            set_={
                "amount": stmt.excluded.amount,
                "year": stmt.excluded.year,
                "month": stmt.excluded.month,
                "description": stmt.excluded.description,
                "provider": stmt.excluded.provider,
                "document_url": stmt.excluded.document_url,
            },
        )
        await self.session.execute(stmt)
        await self.session.flush()
        return len(expenses)

    # --- Lookup helpers (for two-pass vote linking) ---

    async def get_politician_id_by_external_id(self, external_id: int) -> int | None:
        """Get internal politician_id from external_id."""
        result = await self.session.execute(
            select(PoliticianORM.id).where(PoliticianORM.external_id == external_id)
        )
        return result.scalar_one_or_none()

    async def get_proposition_id_by_external_id(self, external_id: str) -> int | None:
        """Get internal proposition_id from external_id."""
        result = await self.session.execute(
            select(PropositionORM.id).where(PropositionORM.external_id == external_id)
        )
        return result.scalar_one_or_none()

    async def build_politician_map(self) -> dict[int, int]:
        """Build map: external_id -> internal_id for all politicians."""
        result = await self.session.execute(
            select(PoliticianORM.external_id, PoliticianORM.id).where(
                PoliticianORM.external_id.isnot(None)
            )
        )
        return {row[0]: row[1] for row in result.all()}

    async def build_proposition_map(self) -> dict[str, int]:
        """Build map: external_id -> internal_id for all propositions."""
        result = await self.session.execute(
            select(PropositionORM.external_id, PropositionORM.id)
        )
        return {row[0]: row[1] for row in result.all()}

    # --- Cleanup ---

    async def delete_votes_for_proposition(self, proposition_id: int) -> int:
        """Delete all votes for a proposition (before re-ingestion)."""
        result = await self.session.execute(
            delete(VoteORM).where(VoteORM.proposition_id == proposition_id)
        )
        await self.session.flush()
        return result.rowcount or 0
