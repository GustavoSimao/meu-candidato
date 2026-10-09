"""Engagement application DTOs - Pydantic schemas."""

from datetime import date, datetime
from typing import Optional

from pydantic import BaseModel, ConfigDict


class FollowDTO(BaseModel):
    id: Optional[int] = None
    user_id: str
    politician_id: int
    created_at: datetime

    model_config = ConfigDict(from_attributes=True)


class FollowCreateDTO(BaseModel):
    user_id: str
    politician_id: int


class FollowListDTO(BaseModel):
    items: list[FollowDTO]
    total: int
    page: int
    per_page: int
    pages: int


class BadgeDTO(BaseModel):
    id: Optional[int] = None
    politician_id: int
    badge_type: str
    earned_at: date
    metadata: dict = {}

    model_config = ConfigDict(from_attributes=True)


class BadgeListDTO(BaseModel):
    items: list[BadgeDTO]
    total: int
    page: int
    per_page: int
    pages: int


class BadgeRuleDTO(BaseModel):
    id: Optional[int] = None
    badge_type: str
    name: str
    description: str
    condition: str
    threshold: int
    is_active: bool = True

    model_config = ConfigDict(from_attributes=True)


class BadgeRuleCreateDTO(BaseModel):
    badge_type: str
    name: str
    description: str
    condition: str
    threshold: int
    is_active: bool = True


class BadgeRuleUpdateDTO(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    condition: Optional[str] = None
    threshold: Optional[int] = None
    is_active: Optional[bool] = None


class BadgeRuleListDTO(BaseModel):
    items: list[BadgeRuleDTO]
    total: int
    page: int
    per_page: int
    pages: int


class DashboardDTO(BaseModel):
    """User dashboard with followed politicians and their badges."""

    followed_politicians: list[int] = []
    badges: list[BadgeDTO] = []
    recent_activity: list[dict] = []