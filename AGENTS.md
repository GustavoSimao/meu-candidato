# AGENTS.md - Development Commands & Project Guide

## Stack

- **FastAPI** + Uvicorn (async)
- **SQLAlchemy 2.0** + Alembic (async migrations)
- **Pydantic v2** + Pydantic Settings
- **PostgreSQL** + psycopg
- **Ruff** (lint/format), **MyPy** (types)
- **Pandas/Polars** (data ingestion)
- **structlog** (structured JSON logging)
- **httpx** + **tenacity** (async HTTP with retry)

## Architecture

Hexagonal/Clean Architecture. Each bounded context under `app/` has:
- `api/` - FastAPI routers + dependencies
- `application/` - DTOs, filters, ports (interfaces), services
- `domain/` - entities, value_objects, exceptions (pure Python, no deps)
- `infrastructure/` - SQLAlchemy models, repository implementations, mappers
- `tests/unit/` - unit tests

Workers live in `app/workers/`. Ingestion is orchestrated by `app/workers/ingestion.py`.

## Lint & Type Check

```bash
# Run ruff linter
docker compose exec api ruff check app/

# Format code with ruff
docker compose exec api ruff format app/

# Type check with mypy
docker compose exec api mypy app/
```

## Testing

```bash
# Run all tests
docker compose exec api pytest tests/ -v

# Run tests with coverage
docker compose exec api pytest tests/ --cov=app --cov-report=term-missing

# Run tests for a specific module
docker compose exec api pytest tests/politician/ -v
```

Tests live in two locations:
- `tests/` - Integration tests (root level) — run by `pytest tests/`
- `app/<context>/tests/unit/` - Unit tests per module

## Worker (Data Ingestion)

```bash
# Start ingestion worker (runs all phases in parallel)
docker compose up -d worker

# Run single dataset (manual)
docker compose run --rm worker python -m app.workers.ingestion deputados
docker compose run --rm worker python -m app.workers.ingestion proposicoes
docker compose run --rm worker python -m app.workers.ingestion despesas
docker compose run --rm worker python -m app.workers.ingestion mandatos

# View logs
docker compose logs -f worker

# Stop worker
docker compose stop worker
```

### Ingestion Phases (parallel via asyncio.gather)

| Phase       | Source        | Parallelization Strategy                          |
|-------------|---------------|--------------------------------------------------|
| deputados   | API v2        | Pages 2..N fetched via `asyncio.gather`          |
| proposicoes | API v2        | Pages in parallel; votacoes+votos concurrent per prop |
| despesas    | API v2        | Per-deputy via `asyncio.gather`                  |
| mandatos    | CSV download  | Per-deputy parallel fetches (CSV fallback for 405) |

**Shared semaphore**: 15 concurrent requests (under Câmara API's 30 req/s limit).
**Mandatos** uses a dedicated semaphore (20) since it uses a different client.

## Database

```bash
# Check migration status
docker compose exec api alembic current

# Apply migrations
docker compose exec api alembic upgrade head

# View tables
docker compose exec -T db psql -U meucandidato -d meucandidato -c "\dt"
```

## Build & Deploy

```bash
# Build containers (production - Dockerfile)
docker compose build

# Build containers (development - Dockerfile.dev with linting/testing tools)
docker compose up -d --build

# Start all services
docker compose up -d

# Check status
docker compose ps

# Run with reload (development)
docker compose up -d --build
```

Production uses `Dockerfile` (lean, no dev tools).
Development uses `Dockerfile.dev` (includes ruff, pytest, mypy) via `docker-compose.override.yml`.

## Environment

Copy `.env.example` and configure:

```bash
cp .env.example .env
```

Key variables: `DATABASE_URL`, `ENVIRONMENT`, `CAMARA_BASE_URL`, `TSE_ELECTION_YEAR`.
