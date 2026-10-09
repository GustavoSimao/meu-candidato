# Meu Candidato API

API para agregação de dados de políticos brasileiros (Câmara, Senado, TSE).

## Stack

- **FastAPI** + Uvicorn (async web framework)
- **SQLAlchemy 2.0** + Alembic (async ORM + migrations)
- **Pydantic v2** + Pydantic Settings
- **PostgreSQL** + psycopg
- **Pandas/Polars** para ingestão de dados
- **Docker** + Docker Compose
- **Ruff** (lint + format)
- **MyPy** (type checking)
- **structlog** (structured logging)
- **httpx** (async HTTP client para APIs externas)
- **tenacity** (retry com backoff exponencial)

## Arquitetura

O projeto usa **Hexagonal Architecture** (também conhecida como Clean Architecture / Ports and Adapters). Cada bounded context segue o mesmo padrão de camadas:

```
app/
├── main.py                 # Entry point da API (FastAPI)
├── workers/                # Background jobs (ingestão de dados)
│   ├── camara_client.py    # Cliente async para API da Câmara
│   ├── ingestion.py        # Orquestrador de ingestão (paralela)
│   ├── ingest_*.py         # Fases de ingestão (deputados, proposicoes, etc.)
│   ├── mappers.py          # Mappers para transformação de dados
│   ├── storage.py          # Salvamento de arquivos raw/quarantine
│   ├── upsert.py           # Lógica de insert/update no banco
│   └── validators.py       # Validação de dados
├── shared/
│   └── kernel/             # Componentes compartilhados
│       ├── config.py       # Configurações (Pydantic Settings)
│       ├── database.py     # Setup SQLAlchemy async
│       ├── base_mapper.py  # Mapper base para transformação
│       ├── base_repository.py   # Repository pattern base
│       └── base_service.py      # Service layer base
├── politician/             # Bounded context: políticos
├── legislative_activity/   # Bounded context: atividade legislativa
├── financial/              # Bounded context: finanças (despesas, campanha)
├── engagement/             # Bounded context: engajamento (follows, badges)
└── ingestion/              # Bounded context: controle de ingestão
```

### Padrão de Camadas (por bounded context)

Cada módulo (`politician/`, `engagement/`, etc.) segue a mesma estrutura:

```
context/
├── api/                    # Camada de apresentação
│   ├── router.py           # Endpoints FastAPI
│   └── dependencies.py     # Dependency injection
├── application/            # Camada de aplicação
│   ├── dtos.py             # Data Transfer Objects
│   ├── filters.py          # Filtros para queries
│   ├── ports.py            # Interfaces (Repository ports)
│   └── services.py         # Business logic
├── domain/                 # Camada de domínio (núcleo)
│   ├── entities.py         # Entidades de domínio
│   ├── exceptions.py       # Exceções de domínio
│   └── value_objects.py    # Value objects
├── infrastructure/         # Camada de infraestrutura
│   ├── mappers.py          # Mappers (domain ↔ ORM)
│   ├── models.py           # SQLAlchemy ORM models
│   └── repository.py       # Implementações de repositórios
└── tests/                  # Testes unitários
    └── unit/
```

Esta arquitetura separa claramente:
- **Domain**: regras de negócio puras, sem dependências externas
- **Application**: orquestração de casos de uso
- **Infrastructure**: detalhes técnicos (banco, APIs, arquivos)
- **API**: exposição HTTP

## Desenvolvimento

### Usando Docker (recomendado)

```bash
# Subir todos os serviços (API + DB + Worker)
docker compose up -d

# Build com ferramentas de desenvolvimento (ruff, pytest, mypy)
docker compose up -d --build

# Ver status
docker compose ps
```

### Comandos úteis

```bash
# Lint
docker compose exec api ruff check app/

# Formatar código
docker compose exec api ruff format app/

# Verificar tipos
docker compose exec api mypy app/

# Rodar testes
docker compose exec api pytest tests/ -v
```

Para mais detalhes, veja `AGENTS.md`.

## Banco de dados

```bash
# Verificar status das migrações
docker compose exec api alembic current

# Aplicar migrações
docker compose exec api alembic upgrade head

# Ver tabelas
docker compose exec -T db psql -U meucandidato -d meucandidato -c "\dt"
```

## Ingestão de dados

### Executar ingestão completa (paralela)

```bash
docker compose up -d worker
```

Ou rodar manualmente:

```bash
docker compose run --rm worker python -m app.workers.ingestion
```

### Fases de ingestão

| Dataset      | Fonte                | Observações                                         |
|--------------|----------------------|----------------------------------------------------|
| deputados    | API v2 `/deputados`  | Paginação paralela, 156 páginas                    |
| proposicoes  | API v2 `/proposicoes`| 156 páginas + votacoes + votos em paralelo          |
| despesas     | API v2 `/despesas`   | Por deputado, paralelo                              |
| mandatos     | CSV dadosabertos     | `/deputados/{id}/mandatos` retorna 405, usa CSV     |

**Nota sobre mandatos**: O endpoint `/deputados/{id}/mandatos` da API retorna HTTP 405. A ingestão usa o CSV `dadosabertos.camara.leg.br/arquivos/deputados/csv/deputados.csv` como alternativa, extraíndo `idLegislaturaInicial`/`idLegislaturaFinal` e resolvendo períodos via API `/legislaturas/{id}`.

## Variáveis de ambiente

Copie `.env.example` para `.env` e ajuste:

```bash
cp .env.example .env
```

Veja `.env.example` para todas as variáveis disponíveis.

## Endpoints principais

> Documentação completa em `docs/api/reference.md`

| Método | Endpoint                    | Descrição          |
|--------|-----------------------------|--------------------|
| GET    | `/health`                   | Health check       |
| GET    | `/api/v1/politicians`       | Lista políticos    |
| GET    | `/api/v1/politicians/{id}`  | Detalhe político   |
| GET    | `/api/v1/votes`             | Votações           |
| GET    | `/api/v1/propositions`      | Proposições        |
| GET    | `/api/v1/expenses`          | Despesas           |
| GET    | `/api/v1/campaigns`         | Financiamento      |

## Documentação

- **`AGENTS.md`** — Comandos rápidos de desenvolvimento
- **`docs/process/deployment.md`** — Guia completo de deploy
- **`docs/process/ingestao-camara.md`** — Detalhes da ingestão da Câmara
- **`docs/architecture/database.md`** — Schema do banco
- **`docs/architecture/adr/`** — Architecture Decision Records
- **`docs/api/reference.md`** — Referência da API
- **`docs/design/`** — Design docs e user flows
