"""Financial ORM models - SQLAlchemy models for persistence."""

from datetime import date
from typing import Optional

from sqlalchemy import Column, Date, ForeignKey, Index, Integer, String, Text
from sqlalchemy.orm import relationship

from app.shared.kernel.database import Base


class ExpenseORM(Base):
    __tablename__ = "expenses"

    id = Column(Integer, primary_key=True, index=True)
    politician_id = Column(Integer, ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False)
    expense_type = Column(String(100), nullable=False, index=True)
    description = Column(Text)
    amount = Column(Integer, nullable=False)  # em centavos
    expense_date = Column(Date, nullable=False, index=True)
    provider = Column(String(255))
    document_number = Column(String(100))
    document_url = Column(String(500))
    year = Column(Integer, nullable=False, index=True)
    month = Column(Integer, nullable=False)

    # Relationship defined in PoliticianORM
    # politician = relationship("PoliticianORM", back_populates="expenses")

    __table_args__ = (
        Index("ix_expenses_politician_year_month", "politician_id", "year", "month"),
        Index("ix_expenses_type_date", "expense_type", "expense_date"),
    )


class CampaignFinanceORM(Base):
    __tablename__ = "campaign_finances"

    id = Column(Integer, primary_key=True, index=True)
    politician_id = Column(Integer, ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False)
    election_year = Column(Integer, nullable=False, index=True)
    election_type = Column(String(50), nullable=False)
    donor_type = Column(String(50), nullable=False)
    donor_name = Column(String(255))
    donor_cpf_cnpj = Column(String(18))
    amount = Column(Integer, nullable=False)  # em centavos
    donation_date = Column(Date, nullable=False)
    receipt_url = Column(String(500))

    # Relationship defined in PoliticianORM
    # politician = relationship("PoliticianORM", back_populates="campaign_finances")

    __table_args__ = (
        Index("ix_campaign_politician_year", "politician_id", "election_year"),
        Index("ix_campaign_donor_type", "donor_type"),
    )