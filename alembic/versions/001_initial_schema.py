"""initial_schema

Revision ID: 001
Revises: 
Create Date: 2026-10-07

"""
from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects.postgresql import JSONB

# revision identifiers, used by Alembic.
revision: str = "001"
down_revision: Union[str, None] = None
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    # politicians table
    op.create_table(
        "politicians",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("external_id", sa.Integer, unique=True, index=True),
        sa.Column("name", sa.String(255), nullable=False, index=True),
        sa.Column("party", sa.String(100), nullable=False, index=True),
        sa.Column("uf", sa.String(2), nullable=False, index=True),
        sa.Column("number", sa.Integer, nullable=False),
        sa.Column("cpf", sa.String(14), unique=True, index=True),
        sa.Column("email", sa.String(255)),
        sa.Column("office_address", sa.Text),
        sa.Column("office_phone", sa.String(50)),
        sa.Column("biography", sa.Text),
        sa.Column("social_media", sa.Text),
        sa.Column("education", sa.String(255)),
        sa.Column("photo_url", sa.String(500)),
        sa.Column("created_at", sa.Date, server_default=sa.text("CURRENT_DATE")),
        sa.Column("updated_at", sa.Date, server_default=sa.text("CURRENT_DATE"), onupdate=sa.text("CURRENT_DATE")),
    )
    op.create_index("ix_politicians_party_uf", "politicians", ["party", "uf"])
    op.create_index("ix_politicians_name_search", "politicians", ["name"])

    # mandates table
    op.create_table(
        "mandates",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("politician_id", sa.Integer, sa.ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False),
        sa.Column("house", sa.String(50), nullable=False),
        sa.Column("role", sa.String(100), nullable=False),
        sa.Column("uf", sa.String(2), nullable=False),
        sa.Column("start_date", sa.Date, nullable=False),
        sa.Column("end_date", sa.Date),
        sa.Column("is_suplente", sa.Boolean, default=False),
    )

    # propositions table
    op.create_table(
        "propositions",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("external_id", sa.String(100), unique=True, index=True),
        sa.Column("politician_id", sa.Integer, sa.ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False),
        sa.Column("type", sa.String(50), nullable=False),
        sa.Column("title", sa.Text, nullable=False),
        sa.Column("summary", sa.Text),
        sa.Column("status", sa.String(50)),
        sa.Column("presentation_date", sa.Date, nullable=False),
        sa.Column("house", sa.String(50), nullable=False),
        sa.Column("url", sa.String(500)),
    )
    op.create_index("ix_propositions_politician_date", "propositions", ["politician_id", "presentation_date"])
    op.create_index("ix_propositions_type_status", "propositions", ["type", "status"])

    # votes table
    op.create_table(
        "votes",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("politician_id", sa.Integer, sa.ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False),
        sa.Column("proposition_id", sa.Integer, sa.ForeignKey("propositions.id", ondelete="CASCADE"), nullable=False),
        sa.Column("session_date", sa.Date, nullable=False, index=True),
        sa.Column("vote_value", sa.String(20), nullable=False),
        sa.Column("session_number", sa.String(50)),
    )
    op.create_index("ix_votes_politician_date", "votes", ["politician_id", "session_date"])
    op.create_index("ix_votes_proposition", "votes", ["proposition_id"])
    op.create_unique_constraint("uq_votes_politician_proposition", "votes", ["politician_id", "proposition_id"])

    # expenses table
    op.create_table(
        "expenses",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("politician_id", sa.Integer, sa.ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False),
        sa.Column("expense_type", sa.String(100), nullable=False, index=True),
        sa.Column("description", sa.Text),
        sa.Column("amount", sa.Integer, nullable=False),
        sa.Column("expense_date", sa.Date, nullable=False, index=True),
        sa.Column("provider", sa.String(255)),
        sa.Column("document_number", sa.String(100)),
        sa.Column("document_url", sa.String(500)),
        sa.Column("year", sa.Integer, nullable=False, index=True),
        sa.Column("month", sa.Integer, nullable=False),
    )
    op.create_index("ix_expenses_politician_year_month", "expenses", ["politician_id", "year", "month"])
    op.create_index("ix_expenses_type_date", "expenses", ["expense_type", "expense_date"])
    op.create_unique_constraint("uq_expenses_politician_type_date_doc", "expenses", ["politician_id", "expense_type", "expense_date", "document_number"])

    # campaign_finances table
    op.create_table(
        "campaign_finances",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("politician_id", sa.Integer, sa.ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False),
        sa.Column("election_year", sa.Integer, nullable=False, index=True),
        sa.Column("election_type", sa.String(50), nullable=False),
        sa.Column("donor_type", sa.String(50), nullable=False),
        sa.Column("donor_name", sa.String(255)),
        sa.Column("donor_cpf_cnpj", sa.String(18)),
        sa.Column("amount", sa.Integer, nullable=False),
        sa.Column("donation_date", sa.Date, nullable=False),
        sa.Column("receipt_url", sa.String(500)),
    )
    op.create_index("ix_campaign_politician_year", "campaign_finances", ["politician_id", "election_year"])
    op.create_index("ix_campaign_donor_type", "campaign_finances", ["donor_type"])

    # follows table
    op.create_table(
        "follows",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("user_id", sa.String(100), nullable=False, index=True),
        sa.Column("politician_id", sa.Integer, sa.ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False),
        sa.Column("created_at", sa.DateTime, nullable=False, server_default=sa.func.now()),
    )
    op.create_index("ix_follows_user_politician", "follows", ["user_id", "politician_id"], unique=True)
    op.create_index("ix_follows_user_created", "follows", ["user_id", "created_at"])

    # badges table
    op.create_table(
        "badges",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("politician_id", sa.Integer, sa.ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False),
        sa.Column("badge_type", sa.String(50), nullable=False),
        sa.Column("earned_at", sa.Date, nullable=False, server_default=sa.text("CURRENT_DATE")),
        sa.Column("metadata", JSONB, default={}),
    )
    op.create_index("ix_badges_politician_type", "badges", ["politician_id", "badge_type"])

    # badge_rules table
    op.create_table(
        "badge_rules",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("badge_type", sa.String(50), nullable=False, unique=True),
        sa.Column("name", sa.String(100), nullable=False),
        sa.Column("description", sa.Text),
        sa.Column("condition", sa.Text, nullable=False),
        sa.Column("threshold", sa.Integer, nullable=False),
        sa.Column("is_active", sa.Integer, default=1),
    )

    # ingestion_jobs table
    op.create_table(
        "ingestion_jobs",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("data_source", sa.String(50), nullable=False),
        sa.Column("dataset", sa.String(50), nullable=False),
        sa.Column("status", sa.String(20), nullable=False, default="pending"),
        sa.Column("started_at", sa.DateTime),
        sa.Column("completed_at", sa.DateTime),
        sa.Column("records_processed", sa.Integer, default=0),
        sa.Column("records_inserted", sa.Integer, default=0),
        sa.Column("records_updated", sa.Integer, default=0),
        sa.Column("records_failed", sa.Integer, default=0),
        sa.Column("error_message", sa.Text),
        sa.Column("metadata", JSONB, default={}),
        sa.Column("created_at", sa.DateTime, nullable=False, server_default=sa.func.now()),
    )
    op.create_index("ix_ingestion_jobs_source_dataset", "ingestion_jobs", ["data_source", "dataset"])
    op.create_index("ix_ingestion_jobs_status", "ingestion_jobs", ["status"])
    op.create_index("ix_ingestion_jobs_created", "ingestion_jobs", ["created_at"])

    # quarantine_records table
    op.create_table(
        "quarantine_records",
        sa.Column("id", sa.Integer, primary_key=True, autoincrement=True),
        sa.Column("data_source", sa.String(50), nullable=False),
        sa.Column("dataset", sa.String(50), nullable=False),
        sa.Column("raw_data", JSONB, nullable=False),
        sa.Column("errors", JSONB, nullable=False),
        sa.Column("received_at", sa.DateTime, nullable=False, server_default=sa.func.now()),
        sa.Column("resolved", sa.Integer, default=0),
    )
    op.create_index("ix_quarantine_source_dataset", "quarantine_records", ["data_source", "dataset"])
    op.create_index("ix_quarantine_received", "quarantine_records", ["received_at"])


def downgrade() -> None:
    op.drop_table("quarantine_records")
    op.drop_table("ingestion_jobs")
    op.drop_table("badge_rules")
    op.drop_table("badges")
    op.drop_table("follows")
    op.drop_table("campaign_finances")
    op.drop_table("expenses")
    op.drop_table("votes")
    op.drop_table("propositions")
    op.drop_table("mandates")
    op.drop_table("politicians")