"""Engagement application services - use cases."""

from app.engagement.application.dtos import (
    BadgeDTO,
    BadgeListDTO,
    BadgeRuleCreateDTO,
    BadgeRuleDTO,
    BadgeRuleListDTO,
    BadgeRuleUpdateDTO,
    FollowCreateDTO,
    FollowDTO,
    FollowListDTO,
)
from app.engagement.application.filters import BadgeFilterDTO, BadgeRuleFilterDTO, FollowFilterDTO
from app.engagement.application.ports import BadgeRepository, BadgeRuleRepository, FollowRepository
from app.engagement.domain.entities import Badge, BadgeRule, Follow, BadgeType
from app.engagement.domain.exceptions import (
    AlreadyFollowingError,
    BadgeNotFoundError,
    BadgeRuleNotFoundError,
    FollowNotFoundError,
    InvalidBadgeTypeError,
)
from app.engagement.infrastructure.mappers import (
    map_badge_domain_to_dto,
    map_badge_rule_domain_to_dto,
    map_follow_domain_to_dto,
)


class FollowService:
    def __init__(self, repository: FollowRepository):
        self.repository = repository

    async def follow(self, user_id: str, politician_id: int) -> FollowDTO:
        if await self.repository.exists(user_id, politician_id):
            raise AlreadyFollowingError(user_id, politician_id)

        follow = Follow(user_id=user_id, politician_id=politician_id)
        saved = await self.repository.save(follow)
        return map_follow_domain_to_dto(saved)

    async def unfollow(self, user_id: str, politician_id: int) -> bool:
        follow = await self.repository.get_by_user_and_politician(user_id, politician_id)
        if follow is None:
            raise FollowNotFoundError(user_id, politician_id)
        return await self.repository.delete(user_id, politician_id)

    async def list_follows(self, filters: FollowFilterDTO) -> FollowListDTO:
        follows, total = await self.repository.list(filters)
        items = [map_follow_domain_to_dto(f) for f in follows]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return FollowListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def get_user_dashboard(self, user_id: str) -> list[int]:
        follows = await self.repository.get_by_user(user_id)
        return [f.politician_id for f in follows]


class BadgeService:
    def __init__(self, repository: BadgeRepository, badge_rule_repository: BadgeRuleRepository):
        self.repository = repository
        self.badge_rule_repository = badge_rule_repository

    async def award_badge(self, politician_id: int, badge_type: str) -> BadgeDTO:
        try:
            badge_type_enum = BadgeType(badge_type)
        except ValueError:
            raise InvalidBadgeTypeError(badge_type)

        # Check if already has this badge
        existing = await self.repository.get_by_politician_and_type(politician_id, badge_type)
        if existing:
            return map_badge_domain_to_dto(existing)

        badge = Badge(politician_id=politician_id, badge_type=badge_type_enum)
        saved = await self.repository.save(badge)
        return map_badge_domain_to_dto(saved)

    async def get_badge(self, badge_id: int) -> BadgeDTO:
        badge = await self.repository.get_by_id(badge_id)
        if badge is None:
            raise BadgeNotFoundError(badge_id)
        return map_badge_domain_to_dto(badge)

    async def list_badges(self, filters: BadgeFilterDTO) -> BadgeListDTO:
        badges, total = await self.repository.list(filters)
        items = [map_badge_domain_to_dto(b) for b in badges]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return BadgeListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def get_politician_badges(self, politician_id: int) -> list[BadgeDTO]:
        filters = BadgeFilterDTO(politician_id=politician_id, page=1, per_page=100)
        badges, _ = await self.repository.list(filters)
        return [map_badge_domain_to_dto(b) for b in badges]

    async def check_and_award_badges(self, politician_id: int, metrics: dict) -> list[BadgeDTO]:
        """Check badge rules and award badges based on metrics."""
        awarded = []
        badge_rules, _ = await self.badge_rule_repository.list(BadgeRuleFilterDTO(is_active=True, page=1, per_page=100))

        for rule in badge_rules:
            if rule.check_condition(metrics.get(rule.badge_type.value, 0)):
                existing = await self.repository.get_by_politician_and_type(politician_id, rule.badge_type.value)
                if not existing:
                    badge = await self.award_badge(politician_id, rule.badge_type.value)
                    awarded.append(badge)

        return awarded


class BadgeRuleService:
    def __init__(self, repository: BadgeRuleRepository):
        self.repository = repository

    async def create_rule(self, data: BadgeRuleCreateDTO) -> BadgeRuleDTO:
        try:
            badge_type = BadgeType(data.badge_type)
        except ValueError:
            raise InvalidBadgeTypeError(data.badge_type)

        # Check if rule already exists
        existing = await self.repository.get_by_badge_type(data.badge_type)
        if existing:
            from app.shared.kernel.exceptions import ConflictError
            raise ConflictError("BadgeRule", "badge_type", data.badge_type)

        rule = BadgeRule(
            badge_type=badge_type,
            name=data.name,
            description=data.description,
            condition=data.condition,
            threshold=data.threshold,
            is_active=data.is_active,
        )
        saved = await self.repository.save(rule)
        return map_badge_rule_domain_to_dto(saved)

    async def get_rule(self, rule_id: int) -> BadgeRuleDTO:
        rule = await self.repository.get_by_id(rule_id)
        if rule is None:
            raise BadgeRuleNotFoundError(rule_id)
        return map_badge_rule_domain_to_dto(rule)

    async def get_rule_by_type(self, badge_type: str) -> BadgeRuleDTO:
        rule = await self.repository.get_by_badge_type(badge_type)
        if rule is None:
            raise BadgeRuleNotFoundError(badge_type)
        return map_badge_rule_domain_to_dto(rule)

    async def list_rules(self, filters: BadgeRuleFilterDTO) -> BadgeRuleListDTO:
        rules, total = await self.repository.list(filters)
        items = [map_badge_rule_domain_to_dto(r) for r in rules]
        pages = (total + filters.per_page - 1) // filters.per_page if total else 0
        return BadgeRuleListDTO(items=items, total=total, page=filters.page, per_page=filters.per_page, pages=pages)

    async def update_rule(self, rule_id: int, data: BadgeRuleUpdateDTO) -> BadgeRuleDTO:
        rule = await self.repository.get_by_id(rule_id)
        if rule is None:
            raise BadgeRuleNotFoundError(rule_id)

        update_data = data.model_dump(exclude_none=True, exclude_unset=True)
        for field_name, field_value in update_data.items():
            setattr(rule, field_name, field_value)

        saved = await self.repository.save(rule)
        return map_badge_rule_domain_to_dto(saved)

    async def delete_rule(self, rule_id: int) -> bool:
        return await self.repository.delete(rule_id)