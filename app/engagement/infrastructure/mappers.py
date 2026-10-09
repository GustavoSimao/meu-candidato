"""Engagement infrastructure mappers - conversion between Domain, ORM, and DTO."""

from datetime import date, datetime
from typing import Any

from app.engagement.domain.entities import Badge, BadgeRule, Follow, BadgeType
from app.engagement.infrastructure.models import BadgeORM, BadgeRuleORM, FollowORM
from app.engagement.application.dtos import (
    BadgeDTO,
    BadgeRuleDTO,
    FollowDTO,
)


def map_follow_orm_to_domain(orm: FollowORM) -> Follow:
    return Follow(
        id=orm.id,
        user_id=orm.user_id,
        politician_id=orm.politician_id,
        created_at=orm.created_at,
    )


def map_follow_domain_to_orm(domain: Follow) -> FollowORM:
    return FollowORM(
        id=domain.id,
        user_id=domain.user_id,
        politician_id=domain.politician_id,
        created_at=domain.created_at or datetime.now(),
    )


def map_follow_domain_to_dto(domain: Follow) -> FollowDTO:
    return FollowDTO(
        id=domain.id,
        user_id=domain.user_id,
        politician_id=domain.politician_id,
        created_at=domain.created_at,
    )


def map_badge_orm_to_domain(orm: BadgeORM) -> Badge:
    return Badge(
        id=orm.id,
        politician_id=orm.politician_id,
        badge_type=BadgeType(orm.badge_type),
        earned_at=orm.earned_at,
        metadata=orm.metadata or {},
    )


def map_badge_domain_to_orm(domain: Badge) -> BadgeORM:
    return BadgeORM(
        id=domain.id,
        politician_id=domain.politician_id,
        badge_type=domain.badge_type.value,
        earned_at=domain.earned_at,
        metadata=domain.metadata,
    )


def map_badge_domain_to_dto(domain: Badge) -> BadgeDTO:
    return BadgeDTO(
        id=domain.id,
        politician_id=domain.politician_id,
        badge_type=domain.badge_type.value,
        earned_at=domain.earned_at,
        metadata=domain.metadata,
    )


def map_badge_rule_orm_to_domain(orm: BadgeRuleORM) -> BadgeRule:
    return BadgeRule(
        id=orm.id,
        badge_type=BadgeType(orm.badge_type),
        name=orm.name,
        description=orm.description or "",
        condition=orm.condition,
        threshold=orm.threshold,
        is_active=bool(orm.is_active),
    )


def map_badge_rule_domain_to_orm(domain: BadgeRule) -> BadgeRuleORM:
    return BadgeRuleORM(
        id=domain.id,
        badge_type=domain.badge_type.value,
        name=domain.name,
        description=domain.description,
        condition=domain.condition,
        threshold=domain.threshold,
        is_active=1 if domain.is_active else 0,
    )


def map_badge_rule_domain_to_dto(domain: BadgeRule) -> BadgeRuleDTO:
    return BadgeRuleDTO(
        id=domain.id,
        badge_type=domain.badge_type.value,
        name=domain.name,
        description=domain.description,
        condition=domain.condition,
        threshold=domain.threshold,
        is_active=domain.is_active,
    )