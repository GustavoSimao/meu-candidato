"""Engagement ORM models - SQLAlchemy models for persistence."""

from datetime import date, datetime

from sqlalchemy import Column, Date, DateTime, ForeignKey, Index, Integer, String, Text
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.orm import relationship

from app.shared.kernel.database import Base


class FollowORM(Base):
    __tablename__ = "follows"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(String(100), nullable=False, index=True)
    politician_id = Column(Integer, ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False)
    created_at = Column(DateTime, default=datetime.now, nullable=False)

    # Relationship defined in PoliticianORM
    # politician = relationship("PoliticianORM")

    __table_args__ = (
        Index("ix_follows_user_politician", "user_id", "politician_id", unique=True),
        Index("ix_follows_user_created", "user_id", "created_at"),
    )


class BadgeORM(Base):
    __tablename__ = "badges"

    id = Column(Integer, primary_key=True, index=True)
    politician_id = Column(Integer, ForeignKey("politicians.id", ondelete="CASCADE"), nullable=False)
    badge_type = Column(String(50), nullable=False)
    earned_at = Column(Date, default=date.today, nullable=False)
    metadata = Column(JSONB, default=dict)

    # Relationship defined in PoliticianORM
    # politician = relationship("PoliticianORM")

    __table_args__ = (
        Index("ix_badges_politician_type", "politician_id", "badge_type"),
    )


class BadgeRuleORM(Base):
    __tablename__ = "badge_rules"

    id = Column(Integer, primary_key=True, index=True)
    badge_type = Column(String(50), nullable=False, unique=True)
    name = Column(String(100), nullable=False)
    description = Column(Text)
    condition = Column(Text, nullable=False)
    threshold = Column(Integer, nullable=False)
    is_active = Column(Integer, default=1)  # Using Integer for boolean compatibility