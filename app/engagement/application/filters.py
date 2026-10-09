"""Engagement application filter DTOs."""

from typing import Optional

from pydantic import BaseModel, ConfigDict, Field


class FollowFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    user_id: Optional[str] = None
    politician_id: Optional[int] = None

    model_config = ConfigDict(extra="forbid")


class BadgeFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    politician_id: Optional[int] = None
    badge_type: Optional[str] = None

    model_config = ConfigDict(extra="forbid")


class BadgeRuleFilterDTO(BaseModel):
    page: int = Field(default=1, ge=1)
    per_page: int = Field(default=20, ge=1, le=100)
    badge_type: Optional[str] = None
    is_active: Optional[bool] = None

    model_config = ConfigDict(extra="forbid")