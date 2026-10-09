"""Financial domain exceptions."""

from app.shared.kernel.exceptions import DomainError, NotFoundError, ValidationError


class ExpenseNotFoundError(NotFoundError):
    def __init__(self, expense_id: int):
        super().__init__("Expense", expense_id)


class CampaignFinanceNotFoundError(NotFoundError):
    def __init__(self, finance_id: int):
        super().__init__("CampaignFinance", finance_id)


class InvalidAmountError(ValidationError):
    def __init__(self, amount: int):
        super().__init__(f"Invalid amount: {amount} centavos", field="amount")


class InvalidExpenseTypeError(ValidationError):
    def __init__(self, expense_type: str):
        super().__init__(f"Invalid expense type: {expense_type}", field="expense_type")


class InvalidDonorTypeError(ValidationError):
    def __init__(self, donor_type: str):
        super().__init__(f"Invalid donor type: {donor_type}", field="donor_type")


class FinancialDomainError(DomainError):
    def __init__(self, message: str, code: str = "FINANCIAL_ERROR", details: dict | None = None):
        super().__init__(message, code=code, details=details)