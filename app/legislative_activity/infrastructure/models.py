"""Legislative Activity ORM models - SQLAlchemy models for persistence."""

from datetime import date
from typing import Optional

from sqlalchemy import Column, Date, ForeignKey, Index, Integer, String, Text
from sqlalchemy.orm import relationship

from app.shared.kernel.database import Base


class VoteORM(Base):
    __tablename__ = "votes"

    id = Column(Integer, primary_key=True, index=True)
    politician_id = Column(Integer, ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False)
    proposition_id = Column(Integer, ForeignKey("propositions.id", ondelete="CASCADE"), nullable=False)
    session_date = Column(Date, nullable=False, index=True)
    vote_value = Column(String(20), nullable=False)  # favor, contra, ausente, abstencao
    session_number = Column(String(50))

    # Relationships are defined in PoliticianORM and PropositionORM
    # politician = relationship("PoliticianORM", back_populates="votes")
    # proposition = relationship("PropositionORM", back_populates="votes")

    __table_args__ = (
        Index("ix_votes_politician_date", "politician_id", "session_date"),
        Index("ix_votes_proposition", "proposition_id"),
    )


class PropositionORM(Base):
    __tablename__ = "propositions"

    id = Column(Integer, primary_key=True, index=True)
    external_id = Column(String(100), unique=True, index=True)
    politician_id = Column(Integer, ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False)
    type = Column(String(50), nullable=False)
    title = Column(Text, nullable=False)
    summary = Column(Text)
    status = Column(String(50))
    presentation_date = Column(Date, nullable=False)
    house = Column(String(50), nullable=False)
    url = Column(String(500))

    # Relationships are defined in PoliticianORM
    # politician = relationship("PoliticianORM", back_populates="propositions")
    # votes = relationship("VoteORM", back_populates="proposition")

    __table_args__ = (
        Index("ix_propositions_politician_date", "politician_id", "presentation_date"),
        Index("ix_propositions_type_status", "type", "status"),
    )