"""add mandate unique constraint

Revision ID: 002
Revises: 001
Create Date: 2026-10-08

"""
from typing import Sequence, Union

from alembic import op

# revision identifiers, used by Alembic.
revision: str = "002"
down_revision: Union[str, None] = "001"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    # Unique constraint for idempotent upserts (Decision 23)
    # A politician can have multiple mandates, but not duplicate (house, start_date)
    op.create_unique_constraint(
        "uq_mandates_politician_house_start",
        "mandates",
        ["politician_id", "house", "start_date"],
    )


def downgrade() -> None:
    op.drop_constraint("uq_mandates_politician_house_start", "mandates", type_="unique")
