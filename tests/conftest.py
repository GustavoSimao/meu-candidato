"""Test fixtures for the project."""

import asyncio
from typing import AsyncGenerator

import pytest
import pytest_asyncio
from sqlalchemy.ext.asyncio import AsyncSession, async_sessionmaker, create_async_engine
from sqlalchemy.orm import DeclarativeBase

# Import all models to register them
from app.politician.infrastructure.models import PoliticianORM, MandateORM
from app.legislative_activity.infrastructure.models import PropositionORM, VoteORM
from app.financial.infrastructure.models import ExpenseORM, CampaignFinanceORM
from app.engagement.infrastructure.models import FollowORM, BadgeORM, BadgeRuleORM
from app.ingestion.infrastructure.models import IngestionJobORM, QuarantineRecordORM
from app.shared.kernel.database import Base


@pytest.fixture(scope="session")
def event_loop():
    """Create event loop for async tests."""
    loop = asyncio.get_event_loop_policy().new_event_loop()
    yield loop
    loop.close()


@pytest.fixture(scope="session")
async def test_engine():
    """Create test database engine using SQLite in memory."""
    engine = create_async_engine(
        "sqlite+aiosqlite:///:memory:",
        echo=False,
    )
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)
    yield engine
    await engine.dispose()


@pytest.fixture
async def test_session(test_engine) -> AsyncGenerator[AsyncSession, None]:
    """Create test database session."""
    async_session = async_sessionmaker(
        test_engine,
        class_=AsyncSession,
        expire_on_commit=False,
    )
    async with async_session() as session:
        yield session
        await session.rollback()


# Repository fixtures
@pytest.fixture
def politician_repository(test_session):
    from app.politician.infrastructure.repository import SQLAlchemyPoliticianRepository
    return SQLAlchemyPoliticianRepository(test_session)


@pytest.fixture
def vote_repository(test_session):
    from app.legislative_activity.infrastructure.repository import SQLAlchemyVoteRepository
    return SQLAlchemyVoteRepository(test_session)


@pytest.fixture
def proposition_repository(test_session):
    from app.legislative_activity.infrastructure.repository import SQLAlchemyPropositionRepository
    return SQLAlchemyPropositionRepository(test_session)


@pytest.fixture
def expense_repository(test_session):
    from app.financial.infrastructure.repository import SQLAlchemyExpenseRepository
    return SQLAlchemyExpenseRepository(test_session)


@pytest.fixture
def campaign_finance_repository(test_session):
    from app.financial.infrastructure.repository import SQLAlchemyCampaignFinanceRepository
    return SQLAlchemyCampaignFinanceRepository(test_session)


@pytest.fixture
def follow_repository(test_session):
    from app.engagement.infrastructure.repository import SQLAlchemyFollowRepository
    return SQLAlchemyFollowRepository(test_session)


@pytest.fixture
def badge_repository(test_session):
    from app.engagement.infrastructure.repository import SQLAlchemyBadgeRepository
    return SQLAlchemyBadgeRepository(test_session)


@pytest.fixture
def badge_rule_repository(test_session):
    from app.engagement.infrastructure.repository import SQLAlchemyBadgeRuleRepository
    return SQLAlchemyBadgeRuleRepository(test_session)


@pytest.fixture
def ingestion_job_repository(test_session):
    from app.ingestion.infrastructure.repository import SQLAlchemyIngestionJobRepository
    return SQLAlchemyIngestionJobRepository(test_session)


@pytest.fixture
def quarantine_repository(test_session):
    from app.ingestion.infrastructure.repository import SQLAlchemyQuarantineRepository
    return SQLAlchemyQuarantineRepository(test_session)


# Service fixtures
@pytest.fixture
def politician_service(politician_repository):
    from app.politician.application.services import PoliticianService
    return PoliticianService(politician_repository)


@pytest.fixture
def vote_service(vote_repository):
    from app.legislative_activity.application.services import VoteService
    return VoteService(vote_repository)


@pytest.fixture
def proposition_service(proposition_repository):
    from app.legislative_activity.application.services import PropositionService
    return PropositionService(proposition_repository)


@pytest.fixture
def expense_service(expense_repository):
    from app.financial.application.services import ExpenseService
    return ExpenseService(expense_repository)


@pytest.fixture
def campaign_finance_service(campaign_finance_repository):
    from app.financial.application.services import CampaignFinanceService
    return CampaignFinanceService(campaign_finance_repository)


@pytest.fixture
def follow_service(follow_repository):
    from app.engagement.application.services import FollowService
    return FollowService(follow_repository)


@pytest.fixture
def badge_service(badge_repository, badge_rule_repository):
    from app.engagement.application.services import BadgeService
    return BadgeService(badge_repository, badge_rule_repository)


@pytest.fixture
def badge_rule_service(badge_rule_repository):
    from app.engagement.application.services import BadgeRuleService
    return BadgeRuleService(badge_rule_repository)


@pytest.fixture
def ingestion_job_service(ingestion_job_repository):
    from app.ingestion.application.services import IngestionJobService
    return IngestionJobService(ingestion_job_repository)


@pytest.fixture
def quarantine_service(quarantine_repository):
    from app.ingestion.application.services import QuarantineService
    return QuarantineService(quarantine_repository)


# Filter fixtures
@pytest.fixture
def politician_filter():
    from app.politician.application.filters import PoliticianFilterDTO
    return PoliticianFilterDTO(page=1, per_page=20)


@pytest.fixture
def vote_filter():
    from app.legislative_activity.application.filters import VoteFilterDTO
    return VoteFilterDTO(page=1, per_page=20)


@pytest.fixture
def proposition_filter():
    from app.legislative_activity.application.filters import PropositionFilterDTO
    return PropositionFilterDTO(page=1, per_page=20)


@pytest.fixture
def expense_filter():
    from app.financial.application.filters import ExpenseFilterDTO
    return ExpenseFilterDTO(page=1, per_page=20)


@pytest.fixture
def campaign_finance_filter():
    from app.financial.application.filters import CampaignFinanceFilterDTO
    return CampaignFinanceFilterDTO(page=1, per_page=20)


@pytest.fixture
def follow_filter():
    from app.engagement.application.filters import FollowFilterDTO
    return FollowFilterDTO(page=1, per_page=20)


@pytest.fixture
def badge_filter():
    from app.engagement.application.filters import BadgeFilterDTO
    return BadgeFilterDTO(page=1, per_page=20)


@pytest.fixture
def badge_rule_filter():
    from app.engagement.application.filters import BadgeRuleFilterDTO
    return BadgeRuleFilterDTO(page=1, per_page=20)


@pytest.fixture
def ingestion_job_filter():
    from app.ingestion.application.filters import IngestionJobFilterDTO
    return IngestionJobFilterDTO(page=1, per_page=20)


@pytest.fixture
def quarantine_filter():
    from app.ingestion.application.filters import QuarantineRecordFilterDTO
    return QuarantineRecordFilterDTO(page=1, per_page=20)