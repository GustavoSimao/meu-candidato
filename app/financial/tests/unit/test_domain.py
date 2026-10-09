"""Tests for financial domain entities and value objects."""

from datetime import date
import pytest

from app.financial.domain.entities import CampaignFinance, Expense
from app.financial.domain.value_objects import Amount, ExpenseType, DonorType, ElectionType


class TestExpenseEntity:
    def test_create_expense(self):
        expense = Expense(
            politician_id=1,
            expense_type="combustivel",
            amount=50000,  # R$ 500,00
            expense_date=date(2023, 6, 15),
            year=2023,
            month=6,
        )
        assert expense.politician_id == 1
        assert expense.amount == 50000

    def test_expense_amount_reais(self):
        expense = Expense(
            politician_id=1,
            expense_type="combustivel",
            amount=50000,
            expense_date=date(2023, 6, 15),
            year=2023,
            month=6,
        )
        assert expense.amount_reais == 500.0


class TestCampaignFinanceEntity:
    def test_create_campaign_finance(self):
        finance = CampaignFinance(
            politician_id=1,
            election_year=2022,
            election_type="federal",
            donor_type="pessoa_fisica",
            amount=100000,
            donation_date=date(2022, 8, 15),
        )
        assert finance.election_year == 2022
        assert finance.amount == 100000

    def test_campaign_finance_amount_reais(self):
        finance = CampaignFinance(
            politician_id=1,
            election_year=2022,
            election_type="federal",
            donor_type="pessoa_fisica",
            amount=100000,
            donation_date=date(2022, 8, 15),
        )
        assert finance.amount_reais == 1000.0

    def test_is_corporate_donation(self):
        finance = CampaignFinance(
            politician_id=1,
            election_year=2022,
            election_type="federal",
            donor_type="pessoa_juridica",
            amount=100000,
            donation_date=date(2022, 8, 15),
        )
        assert finance.is_corporate_donation() is True

    def test_is_party_fund(self):
        finance = CampaignFinance(
            politician_id=1,
            election_year=2022,
            election_type="federal",
            donor_type="fundo_partidario",
            amount=100000,
            donation_date=date(2022, 8, 15),
        )
        assert finance.is_party_fund() is True


class TestAmountValueObject:
    def test_amount_from_reais(self):
        amount = Amount.from_reais(500.0)
        assert amount.centavos == 50000

    def test_amount_reais_property(self):
        amount = Amount(50000)
        assert amount.reais == 500.0

    def test_amount_addition(self):
        a1 = Amount(30000)
        a2 = Amount(20000)
        result = a1 + a2
        assert result.centavos == 50000

    def test_amount_subtraction(self):
        a1 = Amount(50000)
        a2 = Amount(20000)
        result = a1 - a2
        assert result.centavos == 30000

    def test_negative_amount_raises(self):
        with pytest.raises(ValueError):
            Amount(-100)


class TestExpenseTypeEnum:
    def test_all_values(self):
        values = ExpenseType.all_values()
        assert "combustivel" in values
        assert "telefonia" in values
        assert "passagens_aereas" in values


class TestDonorTypeEnum:
    def test_all_values(self):
        values = DonorType.all_values()
        assert "pessoa_fisica" in values
        assert "pessoa_juridica" in values
        assert "fundo_partidario" in values


class TestElectionTypeEnum:
    def test_all_values(self):
        values = ElectionType.all_values()
        assert "federal" in values
        assert "estadual" in values
        assert "municipal" in values