"""Politician domain exceptions - specific to politician bounded context."""

from app.shared.kernel.exceptions import DomainError, NotFoundError, ValidationError


class PoliticianNotFoundError(NotFoundError):
    """Raised when a politician is not found."""

    def __init__(self, politician_id: int):
        super().__init__("Politician", politician_id)


class MandateNotFoundError(NotFoundError):
    """Raised when a mandate is not found."""

    def __init__(self, mandate_id: int):
        super().__init__("Mandate", mandate_id)


class InvalidCPFError(ValidationError):
    """Raised when CPF validation fails."""

    def __init__(self, cpf: str):
        super().__init__(f"Invalid CPF format: {cpf}", field="cpf")


class InvalidUFError(ValidationError):
    """Raised when UF validation fails."""

    def __init__(self, uf: str):
        super().__init__(f"Invalid UF: {uf}", field="uf")


class DuplicateCPFError(DomainError):
    """Raised when trying to create a politician with an existing CPF."""

    def __init__(self, cpf: str):
        super().__init__(
            f"Politician with CPF {cpf} already exists",
            code="DUPLICATE_CPF",
            details={"cpf": cpf}
        )


class PoliticianDomainError(DomainError):
    """Generic politician domain error."""

    def __init__(self, message: str, code: str = "POLITICIAN_ERROR", details: dict | None = None):
        super().__init__(message, code=code, details=details)
