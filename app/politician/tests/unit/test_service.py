"""Tests for politician application service."""

from datetime import date
from unittest.mock import AsyncMock

import pytest

from app.politician.application.dtos import (
    PoliticianCreateDTO,
    PoliticianDetailDTO,
    PoliticianListDTO,
    PoliticianUpdateDTO,
)
from app.politician.application.filters import PoliticianFilterDTO
from app.politician.application.ports import PoliticianRepository
from app.politician.application.services import PoliticianService
from app.politician.domain.entities import Mandate, Politician
from app.politician.domain.exceptions import DuplicateCPFError, PoliticianNotFoundError


class TestPoliticianService:
    @pytest.fixture
    def mock_repository(self) -> AsyncMock:
        return AsyncMock(spec=PoliticianRepository)

    @pytest.fixture
    def service(self, mock_repository: AsyncMock) -> PoliticianService:
        return PoliticianService(mock_repository)

    @pytest.fixture
    def sample_politician(self) -> Politician:
        return Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            email="joao@email.com",
            mandates=[],
        )

    @pytest.fixture
    def sample_politician_with_mandate(self) -> Politician:
        mandate = Mandate(
            id=1,
            politician_id=1,
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
        )
        return Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            email="joao@email.com",
            mandates=[mandate],
        )

    @pytest.mark.asyncio
    async def test_create_politician_success(self, service: PoliticianService, mock_repository: AsyncMock):
        mock_repository.exists_by_cpf.return_value = False
        mock_repository.save.return_value = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            mandates=[],
        )

        data = PoliticianCreateDTO(
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
        )
        result = await service.create(data)

        assert isinstance(result, PoliticianDetailDTO)
        assert result.name == "João Silva"
        assert result.id == 1
        mock_repository.exists_by_cpf.assert_called_once_with("123.456.789-00")
        mock_repository.save.assert_called_once()

    @pytest.mark.asyncio
    async def test_create_politician_duplicate_cpf(self, service: PoliticianService, mock_repository: AsyncMock):
        mock_repository.exists_by_cpf.return_value = True

        data = PoliticianCreateDTO(
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
        )

        with pytest.raises(DuplicateCPFError):
            await service.create(data)

    @pytest.mark.asyncio
    async def test_get_politician_success(self, service: PoliticianService, mock_repository: AsyncMock, sample_politician_with_mandate: Politician):
        mock_repository.get_by_id.return_value = sample_politician_with_mandate

        result = await service.get(1)

        assert isinstance(result, PoliticianDetailDTO)
        assert result.id == 1
        assert result.name == "João Silva"
        assert len(result.mandates) == 1

    @pytest.mark.asyncio
    async def test_get_politician_not_found(self, service: PoliticianService, mock_repository: AsyncMock):
        mock_repository.get_by_id.return_value = None

        with pytest.raises(PoliticianNotFoundError) as exc_info:
            await service.get(999)

        assert exc_info.value.identifier == 999

    @pytest.mark.asyncio
    async def test_list_politicians(self, service: PoliticianService, mock_repository: AsyncMock, sample_politician: Politician):
        mock_repository.list.return_value = ([sample_politician, Politician(id=2, name="Maria", party="PL", uf="RJ", number=2222, mandates=[])], 2)

        filters = PoliticianFilterDTO(page=1, per_page=20)
        result = await service.list(filters)

        assert isinstance(result, PoliticianListDTO)
        assert result.total == 2
        assert len(result.items) == 2
        assert result.page == 1
        assert result.per_page == 20
        assert result.pages == 1

    @pytest.mark.asyncio
    async def test_list_politicians_empty(self, service: PoliticianService, mock_repository: AsyncMock):
        mock_repository.list.return_value = ([], 0)

        filters = PoliticianFilterDTO(page=1, per_page=20)
        result = await service.list(filters)

        assert result.total == 0
        assert result.items == []
        assert result.pages == 0

    @pytest.mark.asyncio
    async def test_list_politicians_with_uf_filter(self, service: PoliticianService, mock_repository: AsyncMock, sample_politician: Politician):
        mock_repository.list.return_value = ([sample_politician], 1)

        filters = PoliticianFilterDTO(page=1, per_page=20, uf="SP")
        result = await service.list(filters)

        assert result.total == 1
        assert len(result.items) == 1

    @pytest.mark.asyncio
    async def test_update_politician_success(self, service: PoliticianService, mock_repository: AsyncMock, sample_politician: Politician):
        mock_repository.get_by_id.return_value = sample_politician
        mock_repository.save.return_value = Politician(
            id=1,
            name="João Silva Atualizado",
            party="PL",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            mandates=[],
        )

        data = PoliticianUpdateDTO(name="João Silva Atualizado", party="PL")
        result = await service.update(1, data)

        assert result.name == "João Silva Atualizado"
        assert result.party == "PL"

    @pytest.mark.asyncio
    async def test_update_politician_not_found(self, service: PoliticianService, mock_repository: AsyncMock):
        mock_repository.get_by_id.return_value = None

        data = PoliticianUpdateDTO(name="João Silva Atualizado")
        with pytest.raises(PoliticianNotFoundError):
            await service.update(999, data)

    @pytest.mark.asyncio
    async def test_delete_politician_success(self, service: PoliticianService, mock_repository: AsyncMock):
        mock_repository.delete.return_value = True

        result = await service.delete(1)

        assert result is True

    @pytest.mark.asyncio
    async def test_delete_politician_not_found(self, service: PoliticianService, mock_repository: AsyncMock):
        mock_repository.delete.return_value = False

        result = await service.delete(999)

        assert result is False
