# Referência de Configuração - Meu Candidato

> Decidi centralizar todas as variáveis de ambiente neste documento porque a configuração estava espalhada entre `.env.example`, `config.py` e os docs de processo.

## Configuração

A configuração usa **Pydantic Settings** (`app/shared/kernel/config.py`). Carrega de `.env` e variáveis de ambiente.

```python
class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", case_sensitive=False)
```

---

## Variáveis de Ambiente

### Aplicação

| Variável | Tipo | Default | Descrição |
|----------|------|---------|-----------|
| `DATABASE_URL` | str | **obrigatório** | URL do PostgreSQL (async) |
| `ENVIRONMENT` | str | `development` | `development`, `staging`, `production` |
| `API_HOST` | str | `0.0.0.0` | Host da API |
| `API_PORT` | int | `8000` | Porta da API |

### Fontes de Dados

| Variável | Tipo | Default | Descrição |
|----------|------|---------|-----------|
| `CAMARA_BASE_URL` | str | `https://dadosabertos.camara.leg.br` | API da Câmara |
| `SENADO_BASE_URL` | str | `https://legis.senado.leg.br` | API do Senado |
| `TSE_BASE_URL` | str | `https://cdn.tse.jus.br` | CDN do TSE |

### Ingestão

| Variável | Tipo | Default | Descrição |
|----------|------|---------|-----------|
| `INGESTION_SCHEDULE_CRON` | str | `0 3 * * *` | Cron (BRT) - diário às 03:00 |
| `INGESTION_BATCH_SIZE` | int | `1000` | Registros por transação (usar 100) |
| `INGESTION_TIMEOUT_SECONDS` | int | `300` | Timeout total do job |
| `INGESTION_JOB_TIMEOUT_SECONDS` | int | `1800` | Timeout antes de considerar job preso (30 min) |
| `QUARANTINE_THRESHOLD_PCT` | int | `5` | % de quarentena que dispara alerta |
| `TSE_ELECTION_YEAR` | str | `auto` | Ano da eleição TSE (`auto`, `all`, ou ano) |

### Worker

| Variável | Tipo | Default | Descrição |
|----------|------|---------|-----------|
| `WORKER_COUNT` | int | `1` | Número de workers |
| `WORKER_POLL_INTERVAL` | int | `30` | Intervalo (s) para verificar jobs pendentes |
| `WORKER_HEARTBEAT_INTERVAL` | int | `60` | Intervalo (s) do heartbeat |

### Timezone

| Variável | Tipo | Default | Descrição |
|----------|------|---------|-----------|
| `TZ` | str | `America/Sao_Paulo` | Timezone do servidor (para cron) |

---

## Arquivo `.env`

Copie `.env.example` para `.env` e ajuste:

```bash
cp .env.example .env
```

**Exemplo `.env`:**
```env
# Aplicação
DATABASE_URL=postgresql+asyncpg://meucandidato:senha@localhost:5432/meucandidato
ENVIRONMENT=development
API_HOST=0.0.0.0
API_PORT=8000

# Fontes
CAMARA_BASE_URL=https://dadosabertos.camara.leg.br
SENADO_BASE_URL=https://legis.senado.leg.br
TSE_BASE_URL=https://cdn.tse.jus.br

# Ingestão
INGESTION_SCHEDULE_CRON=0 3 * * *
INGESTION_BATCH_SIZE=100
INGESTION_TIMEOUT_SECONDS=300
INGESTION_JOB_TIMEOUT_SECONDS=1800
QUARANTINE_THRESHOLD_PCT=5
TSE_ELECTION_YEAR=auto

# Worker
WORKER_COUNT=1
WORKER_POLL_INTERVAL=30
WORKER_HEARTBEAT_INTERVAL=60

# Timezone
TZ=America/Sao_Paulo
```

---

## Segurança

- **Nunca commite `.env`** - está em `.gitignore`
- **`.env.example`** contém apenas placeholders
- **Segredos em produção**: use variáveis de ambiente reais, não arquivo `.env`
- **`DATABASE_URL`** contém senha - proteja o arquivo

---

## Validação

O Pydantic Settings valida os tipos automaticamente. Valores inválidos causam erro na inicialização.

```python
# Exemplo de erro
pydantic_core._pydantic_core.ValidationError: 1 validation error for Settings
API_PORT
  Input should be a valid integer [type=int_type]
```

---

## Acesso no Código

```python
from app.shared.kernel.config import settings

# Acessar configuração
db_url = settings.database_url
env = settings.environment
batch_size = settings.ingestion_batch_size
```

---

## Convenções

- **Nomes**: `snake_case` no código, `SCREAMING_SNAKE_CASE` no env
- **Defaults**: sempre defina um default seguro
- **Obrigatórios**: apenas `DATABASE_URL` é obrigatório
- **Documentação**: atualize este documento ao adicionar variáveis
