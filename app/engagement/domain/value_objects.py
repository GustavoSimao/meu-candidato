"""Engagement domain value objects."""

from dataclasses import dataclass


@dataclass(frozen=True)
class UserId:
    """User ID value object."""

    value: str

    def __post_init__(self):
        if not self.value or not self.value.strip():
            raise ValueError("User ID cannot be empty")
        object.__setattr__(self, "value", self.value.strip())

    def __str__(self) -> str:
        return self.value


@dataclass(frozen=True)
class PoliticianId:
    """Politician ID value object."""

    value: int

    def __post_init__(self):
        if self.value <= 0:
            raise ValueError("Politician ID must be positive")

    def __int__(self) -> int:
        return self.value