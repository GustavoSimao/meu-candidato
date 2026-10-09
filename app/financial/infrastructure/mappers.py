"""Financial infrastructure mappers - conversion between Domain, ORM, and DTO."""

from datetime import date
from typing import Any

from app.financial.domain.entities import CampaignFinance, Expense
from app.financial.domain.value_objects import ExpenseType
from app.financial.infrastructure.models import CampaignFinanceORM, ExpenseORM
from app.financial.application.dtos import (
    CampaignFinanceDTO,
    CampaignFinanceDetailDTO,
    ExpenseDTO,
    ExpenseDetailDTO,
)


EXPENSE_TYPE_TO_PORTUGUESE = {
    ExpenseType.COMBUSTIVEL: "Combustíveis",
    ExpenseType.MANUTENCAO: "Manutenção",
    ExpenseType.TELEFONIA: "Telefonia",
    ExpenseType.MATERIAL_ESCRITORIO: "Material de escritório",
    ExpenseType.PASSAGENS_AEREAS: "Passagens aéreas",
    ExpenseType.LOCACAO_VEICULOS: "Locação de veículos",
    ExpenseType.SERVICOS_TERCEIROS: "Serviços de terceiros",
    ExpenseType.ALIMENTACAO: "Alimentação",
    ExpenseType.HOSPEDAGEM: "Hospedagem",
    ExpenseType.OUTROS: "Outros",
}

PORTUGUESE_TO_EXPENSE_TYPE = {v.lower(): k.value for k, v in EXPENSE_TYPE_TO_PORTUGUESE.items()}


def expense_type_to_portuguese(expense_type: str) -> str:
    """Convert expense_type string to Portuguese label for API."""
    try:
        et = ExpenseType(expense_type)
        return EXPENSE_TYPE_TO_PORTUGUESE.get(et, expense_type)
    except ValueError:
        return expense_type


def expense_type_from_portuguese(portuguese: str) -> str:
    """Convert Portuguese label to internal expense_type value."""
    return PORTUGUESE_TO_EXPENSE_TYPE.get(portuguese.lower(), portuguese)


def map_expense_orm_to_domain(orm: ExpenseORM) -> Expense:
    return Expense(
        id=orm.id,
        politician_id=orm.politician_id,
        expense_type=orm.expense_type,
        amount=orm.amount,
        expense_date=orm.expense_date,
        year=orm.year,
        month=orm.month,
        description=orm.description,
        provider=orm.provider,
        document_number=orm.document_number,
        document_url=orm.document_url,
    )


def map_expense_domain_to_orm(domain: Expense) -> ExpenseORM:
    return ExpenseORM(
        id=domain.id,
        politician_id=domain.politician_id,
        expense_type=domain.expense_type,
        amount=domain.amount,
        expense_date=domain.expense_date,
        year=domain.year,
        month=domain.month,
        description=domain.description,
        provider=domain.provider,
        document_number=domain.document_number,
        document_url=domain.document_url,
    )


def map_expense_domain_to_dto(domain: Expense) -> ExpenseDTO:
    return ExpenseDTO(
        id=domain.id,
        politician_id=domain.politician_id,
        expense_type=expense_type_to_portuguese(domain.expense_type),
        amount=domain.amount,
        expense_date=domain.expense_date,
        year=domain.year,
        month=domain.month,
        description=domain.description,
        provider=domain.provider,
        document_number=domain.document_number,
        document_url=domain.document_url,
    )


def map_expense_domain_to_detail_dto(domain: Expense) -> ExpenseDetailDTO:
    return ExpenseDetailDTO(
        id=domain.id,
        politician_id=domain.politician_id,
        expense_type=expense_type_to_portuguese(domain.expense_type),
        amount=domain.amount,
        expense_date=domain.expense_date,
        year=domain.year,
        month=domain.month,
        description=domain.description,
        provider=domain.provider,
        document_number=domain.document_number,
        document_url=domain.document_url,
    )


def map_campaign_finance_orm_to_domain(orm: CampaignFinanceORM) -> CampaignFinance:
    return CampaignFinance(
        id=orm.id,
        politician_id=orm.politician_id,
        election_year=orm.election_year,
        election_type=orm.election_type,
        donor_type=orm.donor_type,
        donor_name=orm.donor_name,
        donor_cpf_cnpj=orm.donor_cpf_cnpj,
        amount=orm.amount,
        donation_date=orm.donation_date,
        receipt_url=orm.receipt_url,
    )


def map_campaign_finance_domain_to_orm(domain: CampaignFinance) -> CampaignFinanceORM:
    return CampaignFinanceORM(
        id=domain.id,
        politician_id=domain.politician_id,
        election_year=domain.election_year,
        election_type=domain.election_type,
        donor_type=domain.donor_type,
        donor_name=domain.donor_name,
        donor_cpf_cnpj=domain.donor_cpf_cnpj,
        amount=domain.amount,
        donation_date=domain.donation_date,
        receipt_url=domain.receipt_url,
    )


def map_campaign_finance_domain_to_dto(domain: CampaignFinance) -> CampaignFinanceDTO:
    return CampaignFinanceDTO(
        id=domain.id,
        politician_id=domain.politician_id,
        election_year=domain.election_year,
        election_type=domain.election_type,
        donor_type=domain.donor_type,
        donor_name=domain.donor_name,
        donor_cpf_cnpj=domain.donor_cpf_cnpj,
        amount=domain.amount,
        donation_date=domain.donation_date,
        receipt_url=domain.receipt_url,
    )


def map_campaign_finance_domain_to_detail_dto(domain: CampaignFinance) -> CampaignFinanceDetailDTO:
    return CampaignFinanceDetailDTO(
        id=domain.id,
        politician_id=domain.politician_id,
        election_year=domain.election_year,
        election_type=domain.election_type,
        donor_type=domain.donor_type,
        donor_name=domain.donor_name,
        donor_cpf_cnpj=domain.donor_cpf_cnpj,
        amount=domain.amount,
        donation_date=domain.donation_date,
        receipt_url=domain.receipt_url,
    )


def map_expense_create_dto_to_domain(data: ExpenseDTO) -> dict[str, Any]:
    dump = data.model_dump(exclude_none=True, exclude={"id"})
    if "expense_type" in dump:
        dump["expense_type"] = expense_type_from_portuguese(dump["expense_type"])
    return dump


def map_campaign_finance_create_dto_to_domain(data: CampaignFinanceDTO) -> dict[str, Any]:
    return data.model_dump(exclude_none=True, exclude={"id"})