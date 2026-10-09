"""Engagement domain exceptions."""

from app.shared.kernel.exceptions import DomainError, NotFoundError, ValidationError


class FollowNotFoundError(NotFoundError):
    def __init__(self, user_id: str, politician_id: int):
        super().__init__("Follow", f"user={user_id}, politician={politician_id}")


class BadgeNotFoundError(NotFoundError):
    def __init__(self, badge_id: int):
        super().__init__("Badge", badge_id)


class BadgeRuleNotFoundError(NotFoundError):
    def __init__(self, rule_id: int):
        super().__init__("BadgeRule", rule_id)


class AlreadyFollowingError(DomainError):
    def __init__(self, user_id: str, politician_id: int):
        super().__init__(
            f"User {user_id} is already following politician {politician_id}",
            code="ALREADY_FOLLOWING",
            details={"user_id": user_id, "politician_id": politician_id},
        )


class InvalidBadgeTypeError(ValidationError):
    def __init__(self, badge_type: str):
        super().__init__(f"Invalid badge type: {badge_type}", field="badge_type")


class EngagementDomainError(DomainError):
    def __init__(self, message: str, code: str = "ENGAGEMENT_ERROR", details: dict | None = None):
        super().__init__(message, code=code, details=details)