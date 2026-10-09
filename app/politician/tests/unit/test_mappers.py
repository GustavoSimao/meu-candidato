"""Tests for politician infrastructure mappers."""

from datetime import date

from app.politician.application.dtos import (
    MandateDTO,
    PoliticianBaseDTO,
    PoliticianDetailDTO,
    PoliticianListItemDTO,
)
from app.politician.domain.entities import Mandate, Politician
from app.politician.infrastructure.mappers import (
    map_mandate_domain_to_dto,
    map_mandate_domain_to_orm,
    map_mandate_orm_to_domain,
    map_politician_create_dto_to_domain,
    map_politician_domain_to_base_dto,
    map_politician_domain_to_detail_dto,
    map_politician_domain_to_list_item_dto,
    map_politician_domain_to_orm,
    map_politician_orm_to_domain,
    map_politician_update_dto_to_domain,
)
from app.politician.infrastructure.models import MandateORM, PoliticianORM


class TestMandateMappers:
    def test_map_mandate_domain_to_dto(self):
        domain = Mandate(
            id=1,
            politician_id=1,
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
            is_suplente=False,
        )

        dto = map_mandate_domain_to_dto(domain)

        assert isinstance(dto, MandateDTO)
        assert dto.house == "camara"
        assert dto.role == "deputado federal"
        assert dto.uf == "SP"
        assert dto.start_date == date(2023, 2, 1)
        assert dto.end_date == date(2027, 1, 31)
        assert dto.is_suplente is False

    def test_map_mandate_domain_to_orm(self):
        domain = Mandate(
            id=1,
            politician_id=1,
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
            is_suplente=False,
        )

        orm = map_mandate_domain_to_orm(domain)

        assert isinstance(orm, MandateORM)
        assert orm.id == 1
        assert orm.politician_id == 1
        assert orm.house == "camara"
        assert orm.role == "deputado federal"
        assert orm.uf == "SP"
        assert orm.start_date == date(2023, 2, 1)
        assert orm.end_date == date(2027, 1, 31)
        assert orm.is_suplente is False

    def test_map_mandate_orm_to_domain(self):
        orm = MandateORM(
            id=1,
            politician_id=1,
            house="senado",
            role="senador",
            uf="RJ",
            start_date=date(2019, 2, 1),
            end_date=None,
            is_suplente=True,
        )

        domain = map_mandate_orm_to_domain(orm)

        assert isinstance(domain, Mandate)
        assert domain.id == 1
        assert domain.house == "senado"
        assert domain.end_date is None
        assert domain.is_suplente is True


class TestPoliticianMappers:
    def test_map_politician_domain_to_base_dto(self):
        domain = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            email="joao@email.com",
            office_address="Câmara dos Deputados, Gabinete 123",
            office_phone="(61) 3216-1234",
            biography="Biografia do político",
            social_media={"twitter": "@joaosilva"},
            education="Mestrado em Direito",
            photo_url="https://example.com/photo.jpg",
            mandates=[],
        )

        dto = map_politician_domain_to_base_dto(domain)

        assert isinstance(dto, PoliticianBaseDTO)
        assert dto.name == "João Silva"
        assert dto.party == "PT"
        assert dto.uf == "SP"
        assert dto.number == 1313
        assert dto.cpf == "123.456.789-00"
        assert dto.email == "joao@email.com"

    def test_map_politician_domain_to_list_item_dto(self):
        domain = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            email="joao@email.com",
            mandates=[],
        )

        dto = map_politician_domain_to_list_item_dto(domain)

        assert isinstance(dto, PoliticianListItemDTO)
        assert dto.id == 1
        assert dto.name == "João Silva"
        assert dto.badges == []

    def test_map_politician_domain_to_list_item_dto_with_badges(self):
        domain = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            mandates=[],
        )

        dto = map_politician_domain_to_list_item_dto(domain, badges=["ficha_limpa", "presenca_alta"])

        assert dto.badges == ["ficha_limpa", "presenca_alta"]

    def test_map_politician_domain_to_detail_dto(self):
        mandate = Mandate(
            id=1,
            politician_id=1,
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
            is_suplente=False,
        )
        domain = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            email="joao@email.com",
            office_address="Câmara dos Deputados",
            office_phone="(61) 3216-1234",
            biography="Biografia do político",
            social_media={"twitter": "@joaosilva"},
            education="Mestrado em Direito",
            photo_url="https://example.com/photo.jpg",
            mandates=[mandate],
        )

        dto = map_politician_domain_to_detail_dto(domain)

        assert isinstance(dto, PoliticianDetailDTO)
        assert dto.id == 1
        assert dto.name == "João Silva"
        assert len(dto.mandates) == 1
        assert dto.mandates[0].house == "camara"
        assert dto.badges == []

    def test_map_politician_domain_to_detail_dto_with_multiple_mandates(self):
        mandate1 = Mandate(
            id=1,
            politician_id=1,
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2019, 2, 1),
            end_date=date(2023, 1, 31),
        )
        mandate2 = Mandate(
            id=2,
            politician_id=1,
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
        )
        domain = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            mandates=[mandate1, mandate2],
        )

        dto = map_politician_domain_to_detail_dto(domain)

        assert len(dto.mandates) == 2
        assert dto.mandates[0].start_date == date(2019, 2, 1)
        assert dto.mandates[1].start_date == date(2023, 2, 1)

    def test_map_politician_domain_to_orm(self):
        domain = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            mandates=[],
        )

        orm = map_politician_domain_to_orm(domain)

        assert isinstance(orm, PoliticianORM)
        assert orm.id == 1
        assert orm.name == "João Silva"
        assert orm.party == "PT"
        assert orm.uf == "SP"
        assert orm.number == 1313
        assert orm.cpf == "123.456.789-00"

    def test_map_politician_orm_to_domain(self):
        orm = PoliticianORM(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            email="joao@email.com",
            mandates=[],
        )

        domain = map_politician_orm_to_domain(orm)

        assert isinstance(domain, Politician)
        assert domain.id == 1
        assert domain.name == "João Silva"
        assert domain.party == "PT"
        assert domain.uf == "SP"

    def test_map_politician_create_dto_to_domain(self):
        dto = PoliticianBaseDTO(
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            email="joao@email.com",
        )

        data = map_politician_create_dto_to_domain(dto)

        assert data["name"] == "João Silva"
        assert data["party"] == "PT"
        assert data["cpf"] == "123.456.789-00"

    def test_map_politician_update_dto_to_domain(self):
        from app.politician.application.dtos import PoliticianUpdateDTO

        dto = PoliticianUpdateDTO(
            name="João Silva Atualizado",
            party="PL",
        )

        data = map_politician_update_dto_to_domain(dto)

        assert data["name"] == "João Silva Atualizado"
        assert data["party"] == "PL"
        assert "cpf" not in data  # not set in update
