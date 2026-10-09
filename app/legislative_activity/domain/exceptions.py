"""Legislative Activity domain exceptions."""

from app.shared.kernel.exceptions import DomainError, NotFoundError, ValidationError


class VoteNotFoundError(NotFoundError):
    def __init__(self, vote_id: int):
        super().__init__("Vote", vote_id)


class PropositionNotFoundError(NotFoundError):
    def __init__(self, proposition_id: int):
        super().__init__("Proposition", proposition_id)


class InvalidVoteValueError(ValidationError):
    def __init__(self, value: str):
        super().__init__(f"Invalid vote value: {value}. Must be one of: favor, contra, ausente, abstencao", field="vote_value")


class InvalidPropositionTypeError(ValidationError):
    def __init__(self, prop_type: str):
        super().__init__(f"Invalid proposition type: {prop_type}", field="type")


class LegislativeActivityDomainError(DomainError):
    def __init__(self, message: str, code: str = "LEGISLATIVE_ACTIVITY_ERROR", details: dict | None = None):
        super().__init__(message, code=code, details=details)