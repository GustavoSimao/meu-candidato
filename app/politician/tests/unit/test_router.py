"""Tests for politician API router."""

from datetime import date
from unittest.mock import AsyncMock

import pytest
from fastapi import FastAPI
from httpx import ASGITransport, AsyncClient
from sqlalchemy.ext.asyncio import AsyncSession

from app.politician.api.router import router as politician_router
from app.politician.application.dtos import (
    PoliticianListDTO,
)
from app.politician.application.services import PoliticianService
from app.politician.domain.entities import Mandate, Politician
from app.politician.domain.exceptions import DuplicateCPFError, PoliticianNotFoundError
from app.shared.kernel.database import get_session


@pytest.fixture
def mock_session() -> AsyncMock:
    return AsyncMock(spec=AsyncSession)


@pytest.fixture
def app(mock_session: AsyncMock) -> FastAPI:
    application = FastAPI()
    application.include_router(politician_router, prefix="/api/v1")

    from app.politician.api.dependencies import politician_repository, politician_service
    from app.politician.infrastructure.repository import SQLAlchemyPoliticianRepository

    async def override_get_session():
        yield mock_session

    async def override_politician_repository():
        return SQLAlchemyPoliticianRepository(mock_session)

    async def override_politician_service():
        repository = await override_politician_repository()
        return PoliticianService(repository)

    application.dependency_overrides[get_session] = override_get_session
    application.dependency_overrides[politician_repository] = override_politician_repository
    application.dependency_overrides[politician_service] = override_politician_service

    return application


@pytest.fixture
async def client(app: FastAPI) -> AsyncClient:
    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as ac:
        yield ac


class TestPoliticianRouter:
    @pytest.mark.asyncio
    async def test_create_politician_success(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service
        from app.politician.infrastructure.mappers import map_politician_domain_to_detail_dto

        politician = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            mandates=[],
        )

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.create.return_value = map_politician_domain_to_detail_dto(politician)
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.post(
            "/api/v1/politicians",
            json={
                "name": "João Silva",
                "party": "PT",
                "uf": "SP",
                "number": 1313,
                "cpf": "123.456.789-00",
            },
        )

        assert response.status_code == 201
        data = response.json()
        assert data["name"] == "João Silva"
        assert data["id"] == 1

    @pytest.mark.asyncio
    async def test_create_politician_duplicate_cpf(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.create.side_effect = DuplicateCPFError("123.456.789-00")
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.post(
            "/api/v1/politicians",
            json={
                "name": "João Silva",
                "party": "PT",
                "uf": "SP",
                "number": 1313,
                "cpf": "123.456.789-00",
            },
        )

        assert response.status_code == 409

    @pytest.mark.asyncio
    async def test_list_politicians_success(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service
        from app.politician.infrastructure.mappers import map_politician_domain_to_list_item_dto

        politician1 = Politician(id=1, name="João Silva", party="PT", uf="SP", number=1313, mandates=[])
        politician2 = Politician(id=2, name="Maria Santos", party="PL", uf="RJ", number=2222, mandates=[])

        expected_list = PoliticianListDTO(
            items=[
                map_politician_domain_to_list_item_dto(politician1),
                map_politician_domain_to_list_item_dto(politician2),
            ],
            total=2,
            page=1,
            per_page=20,
            pages=1,
        )

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.list.return_value = expected_list
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.get("/api/v1/politicians")

        assert response.status_code == 200
        data = response.json()
        assert data["total"] == 2
        assert len(data["items"]) == 2
        assert data["items"][0]["name"] == "João Silva"
        assert data["items"][1]["name"] == "Maria Santos"

    @pytest.mark.asyncio
    async def test_list_politicians_with_query_params(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service

        expected_list = PoliticianListDTO(items=[], total=0, page=1, per_page=10, pages=0)

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.list.return_value = expected_list
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.get("/api/v1/politicians?page=1&per_page=10&uf=SP&party=PT")

        assert response.status_code == 200
        data = response.json()
        assert data["total"] == 0
        assert data["items"] == []

    @pytest.mark.asyncio
    async def test_get_politician_success(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service
        from app.politician.infrastructure.mappers import map_politician_domain_to_detail_dto

        mandate = Mandate(
            id=1,
            politician_id=1,
            house="camara",
            role="deputado federal",
            uf="SP",
            start_date=date(2023, 2, 1),
            end_date=date(2027, 1, 31),
        )
        politician = Politician(
            id=1,
            name="João Silva",
            party="PT",
            uf="SP",
            number=1313,
            cpf="123.456.789-00",
            email="joao@email.com",
            mandates=[mandate],
        )

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.get.return_value = map_politician_domain_to_detail_dto(politician)
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.get("/api/v1/politicians/1")

        assert response.status_code == 200
        data = response.json()
        assert data["id"] == 1
        assert data["name"] == "João Silva"
        assert len(data["mandates"]) == 1
        assert data["mandates"][0]["house"] == "camara"

    @pytest.mark.asyncio
    async def test_get_politician_not_found(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.get.side_effect = PoliticianNotFoundError(999)
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.get("/api/v1/politicians/999")

        assert response.status_code == 404

    @pytest.mark.asyncio
    async def test_update_politician_success(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service
        from app.politician.infrastructure.mappers import map_politician_domain_to_detail_dto

        politician = Politician(
            id=1,
            name="João Silva Atualizado",
            party="PL",
            uf="SP",
            number=1313,
            mandates=[],
        )

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.update.return_value = map_politician_domain_to_detail_dto(politician)
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.patch(
            "/api/v1/politicians/1",
            json={"name": "João Silva Atualizado", "party": "PL"},
        )

        assert response.status_code == 200
        data = response.json()
        assert data["name"] == "João Silva Atualizado"
        assert data["party"] == "PL"

    @pytest.mark.asyncio
    async def test_delete_politician_success(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.delete.return_value = True
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.delete("/api/v1/politicians/1")

        assert response.status_code == 204

    @pytest.mark.asyncio
    async def test_delete_politician_not_found(self, app: FastAPI, client: AsyncClient):
        from app.politician.api.dependencies import politician_service

        mock_service = AsyncMock(spec=PoliticianService)
        mock_service.delete.return_value = False
        app.dependency_overrides[politician_service] = lambda: mock_service

        response = await client.delete("/api/v1/politicians/999")

        assert response.status_code == 404
