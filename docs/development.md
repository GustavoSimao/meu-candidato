# Guia do Desenvolvedor - Meu Candidato

> Decidi criar este guia porque o README tem apenas comandos básicos, sem explicar a estrutura ou o fluxo de trabalho.

## Pré-requisitos

- **Python 3.11+** (projeto exige `>=3.11`)
- **Docker** + **Docker Compose** (banco e serviços)
- **Git**

## Setup

### 1. Clone e configure

```bash
git clone <repo-url>
cd meu-candidato
cp .env.example .env
# Edite .env com suas configurações
```

### 2. Suba o banco

```bash
docker compose up -d db
```

### 3. Aplique migrações

```bash
python scripts/migrate.py
```

### 4. Instale dependências

```bash
pip install -e ".[dev]"
```

### 5. Rode os testes

```bash
pytest
```

### 6. Rode a API

```bash
uvicorn app.main:app --reload
```

Acesse: http://localhost:8000/docs (Swagger UI)

---

## Estrutura do Projeto

```
app/
├── main.py                    # FastAPI app, lifespan, exception handlers
├── shared/kernel/             # Kernel compartilhado
│   ├── config.py              # Settings (Pydantic)
│   ├── database.py            # SQLAlchemy async engine, session
│   ├── base_repository.py     # Repositório base
│   ├── base_service.py        # Serviço base
│   ├── base_mapper.py         # Mapper base
│   ├── exceptions.py          # Exceções do kernel
│   └── mappers.py             # Mappers genéricos
├── politician/                # Módulo: políticos
│   ├── domain/                # Entidades, value objects, exceções
│   ├── application/           # DTOs, filtros, ports, serviços
│   ├── infrastructure/        # ORM models, mappers, repositórios
│   └── api/                   # Router, dependências
├── legislative_activity/      # Módulo: atividade legislativa
├── financial/                 # Módulo: financeiro
├── engagement/                # Módulo: engajamento
├── ingestion/                 # Módulo: ingestão (jobs, quarentena)
└── workers/                   # Workers de ingestão
    ├── ingestion.py           # Orquestrador principal
    ├── camara_client.py       # Cliente HTTP da Câmara
    ├── ingest_deputados.py    # Fluxo: deputados + mandatos
    ├── ingest_proposicoes.py  # Fluxo: proposições + votos
    ├── ingest_despesas.py     # Fluxo: despesas
    └── storage.py             # Raw + quarantine storage
```

### Arquitetura Limpa

Cada módulo segue a mesma estrutura:

```
modulo/
├── domain/            # Entidades e value objects (puro Python, sem framework)
├── application/       # Casos de uso, DTOs, ports (interfaces)
├── infrastructure/    # ORM, repositórios, mappers (SQLAlchemy)
└── api/               # Rotas FastAPI, dependências
```

**Fluxo de dados:** `API (DTO)` → `Application Service` → `Domain Entity` → `Repository (ORM)` → `Database`

---

## Comandos

### Desenvolvimento

```bash
# Subir banco
docker compose up -d db

# Aplicar migrações
python scripts/migrate.py

# Rodar API
uvicorn app.main:app --reload

# Rodar worker de ingestão
python -m app.workers.ingestion

# Rodar ingestão de um dataset específico
python -m app.workers.ingestion deputados
```

### Testes

```bash
# Todos os testes
pytest

# Testes de um módulo
pytest app/politician/tests/

# Com cobertura
pytest --cov=app

# Testes específicos
pytest app/politician/tests/unit/test_domain.py -v
```

### Lint e Typecheck

```bash
# Lint
ruff check .

# Format
ruff format .

# Typecheck
mypy app/
```

### Migrações

```bash
# Criar migration (autogenerate)
alembic revision --autogenerate -m "descricao"

# Aplicar
alembic upgrade head

# Reverter última
alembic downgrade -1

# Ver histórico
alembic history
```

---

## Estilo de Código

### Ferramentas

- **ruff**: lint + format (line-length=100, target=py311)
- **mypy**: typecheck
- **pre-commit**: hooks antes do commit

### Convenções

- **Imports**: isort (via ruff `I`)
- **Strings**: aspas duplas (`"`)
- **Indentação**: 4 espaços
- **Tipos**: type hints em funções públicas
- **Docstrings**: Google style (opcional)

### Pre-commit

```bash
pip install pre-commit
pre-commit install
```

Hooks: `ruff`, `mypy` (ver `.pre-commit-config.yaml`)

---

## Testes

### Estrutura

```
modulo/tests/
├── unit/              # Testes unitários (domínio, serviços)
└── integration/       # Testes de integração (API, DB)
```

### Estratégia

- **Unit**: domínio puro, sem DB, sem HTTP
- **Integration**: Testcontainers (PostgreSQL real) + `httpx.MockTransport` (HTTP mockado)
- **Fixtures**: dados de exemplo em `tests/fixtures/`

### Exemplo

```python
import pytest
from app.politician.domain.entities import Politician

def test_politician_current_mandate():
    politician = Politician(
        name="João", party="PT", uf="SP", number=12345,
        mandates=[Mandate(house="camara", role="deputado federal", uf="SP", start_date=date(2023, 1, 1))]
    )
    assert politician.current_mandate is not None
    assert politician.current_mandate.is_current()
```

---

## Fluxo de Trabalho

### Branching (Trunk-based)

- `main` sempre deployável
- Feature branches vivem < 2 dias
- PR requer review + CI verde

### Commits

- Mensagens claras, imperativas
- Um commit = uma mudança lógica

### CI/CD (GitHub Actions)

Pipeline: `ruff check` → `mypy` → `pytest` → `docker build`

---

## Debug

### Logs

Logs estruturados em JSON (via `structlog`):

```json
{"level": "info", "event": "deputados_fetched", "count": 513, "timestamp": "..."}
```

### Health Check

```bash
curl http://localhost:8000/health
# {"status": "ok", "environment": "development"}
```

### OpenAPI

```bash
curl http://localhost:8000/openapi.json > openapi.json
```

---

## Problemas Comuns

Ver `troubleshooting.md` para soluções.

### Windows: Event Loop

O projeto usa `WindowsSelectorEventLoopPolicy` para psycopg async (ver `pyproject.toml` e `alembic/env.py`).

### Porta em uso

```bash
# Encontrar processo na porta 8000
netstat -ano | findstr :8000
# Matar processo
taskkill /PID <pid> /F
```
