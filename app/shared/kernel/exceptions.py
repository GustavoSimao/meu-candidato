"""Shared kernel exceptions - base domain exceptions used across all bounded contexts."""

from typing import Any


class DomainError(Exception):
    """Base exception for domain errors."""

    def __init__(self, message: str, code: str | None = None, details: dict[str, Any] | None = None):
        super().__init__(message)
        self.message = message
        self.code = code or self.__class__.__name__
        self.details = details or {}


class NotFoundError(DomainError):
    """Raised when a requested resource is not found."""

    def __init__(self, resource: str, identifier: str | int):
        message = f"{resource} not found: {identifier}"
        super().__init__(message, code="NOT_FOUND", details={"resource": resource, "identifier": identifier})
        self.resource = resource
        self.identifier = identifier


class ValidationError(DomainError):
    """Raised when input validation fails."""

    def __init__(self, message: str, field: str | None = None):
        details = {"field": field} if field else {}
        super().__init__(message, code="VALIDATION_ERROR", details=details)
        self.field = field


class ConflictError(DomainError):
    """Raised when a resource conflict occurs (e.g., duplicate unique field)."""

    def __init__(self, resource: str, field: str, value: str):
        message = f"{resource} with {field}={value} already exists"
        super().__init__(message, code="CONFLICT", details={"resource": resource, "field": field, "value": value})
        self.resource = resource
        self.field = field
        self.value = value


class BusinessRuleError(DomainError):
    """Raised when a business rule is violated."""

    def __init__(self, rule: str, details: str | None = None):
        message = f"Business rule violated: {rule}"
        if details:
            message += f" ({details})"
        super().__init__(message, code="BUSINESS_RULE_VIOLATION", details={"rule": rule, "details": details})
        self.rule = rule
        self.details = details
