"""Politician ORM models - SQLAlchemy models for persistence."""

from datetime import date

from sqlalchemy import Boolean, Column, Date, ForeignKey, Index, Integer, String, Text
from sqlalchemy.orm import relationship

from app.shared.kernel.database import Base


class PoliticianORM(Base):
    __tablename__ = "politicians"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), nullable=False, index=True)
    party = Column(String(100), nullable=False, index=True)
    uf = Column(String(2), nullable=False, index=True)
    number = Column(Integer, nullable=False)
    external_id = Column(Integer, unique=True, index=True)
    cpf = Column(String(14), unique=True, index=True)
    email = Column(String(255))
    office_address = Column(Text)
    office_phone = Column(String(50))
    biography = Column(Text)
    social_media = Column(Text)  # JSON string
    education = Column(String(255))
    photo_url = Column(String(500))
    created_at = Column(Date, default=date.today)
    updated_at = Column(Date, default=date.today, onupdate=date.today)

    mandates = relationship("MandateORM", back_populates="politician", cascade="all, delete-orphan")

    __table_args__ = (
        Index("ix_politicians_party_uf", "party", "uf"),
        Index("ix_politicians_name_search", "name"),
    )


class MandateORM(Base):
    __tablename__ = "mandates"

    id = Column(Integer, primary_key=True, index=True)
    politician_id = Column(Integer, ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False)
    house = Column(String(50), nullable=False)  # camara, senado
    role = Column(String(100), nullable=False)  # deputado, senador
    uf = Column(String(2), nullable=False)
    start_date = Column(Date, nullable=False)
    end_date = Column(Date)
    is_suplente = Column(Boolean, default=False)

    politician = relationship("PoliticianORM", back_populates="mandates")

    __table_args__ = (
        # Unique constraint for idempotent upserts (Decision 23)
        # A politician can have multiple mandates, but not duplicate (house, start_date)
        Index("uq_mandates_politician_house_start", "politician_id", "house", "start_date", unique=True),
    )
