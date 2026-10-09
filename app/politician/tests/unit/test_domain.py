"""Tests for politician domain entities and value objects."""

from datetime import date

import pytest

from app.politician.domain.entities import Mandate, Politician
from app.politician.domain.value_objects import CPF, UF, Badge, Party


class TestMandateEntity:
    def test_create_mandate(self):
        mandate = Mandate(
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
            is_suplente=False,
        )
        assert mandate.house == "camara"
        assert mandate.role == "deputado federal"
        assert mandate.uf == "SP"

    def test_mandate_is_current_active(self):
        mandate = Mandate(
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
        )
        assert mandate.is_current(date(2024, 1, 1)) is True

    def test_mandate_is_current_expired(self):
        mandate = Mandate(
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2019, 2, 1),
            end_date=date(2023, 1, 31),
        )
        assert mandate.is_current(date(2024, 1, 1)) is False

    def test_mandate_is_current_no_end_date(self):
        mandate = Mandate(
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=None,
        )
        assert mandate.is_current(date(2024, 1, 1)) is True


class TestPoliticianEntity:
    def test_create_politician(self):
        politician = Politician(
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
        )
        assert politician.name == "João Silva"
        assert politician.party == "PT"
        assert politician.uf == "SP"
        assert politician.number == 1313

    def test_politician_current_mandate(self):
        mandate = Mandate(
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
        )
        politician = Politician(
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            mandates=[mandate],
        )
        assert politician.current_mandate is not None
        assert politician.current_mandate.house == "camara"

    def test_politician_no_current_mandate(self):
        mandate = Mandate(
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2019, 2, 1),
            end_date=date(2023, 1, 31),
        )
        politician = Politician(
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            mandates=[mandate],
        )
        assert politician.current_mandate is None


class TestCPFValueObject:
    def test_valid_cpf_formatted(self):
        cpf = CPF("123.456.789-09")
        assert str(cpf) == "123.456.789-09"

    def test_valid_cpf_unformatted(self):
        cpf = CPF("12345678909")
        assert str(cpf) == "123.456.789-09"

    def test_invalid_cpf_raises(self):
        with pytest.raises(ValueError):
            CPF("123.456.789-00")

    def test_invalid_cpf_all_same_digits(self):
        with pytest.raises(ValueError):
            CPF("111.111.111-11")


class TestUFValueObject:
    def test_valid_uf(self):
        uf = UF("sp")
        assert str(uf) == "SP"

    def test_invalid_uf_raises(self):
        with pytest.raises(ValueError):
            UF("XX")


class TestPartyValueObject:
    def test_valid_party(self):
        party = Party("  pt  ")
        assert str(party) == "PT"

    def test_empty_party_raises(self):
        with pytest.raises(ValueError):
            Party("")


class TestBadgeValueObject:
    def test_badge_creation(self):
        badge = Badge("ficha_limpa")
        assert str(badge) == "ficha_limpa"

    def test_badge_all_types(self):
        types = Badge.all_types()
        assert "ficha_limpa" in types
        assert "presenca_alta" in types
        assert "legislador_ativo" in types
