# Ingestão de Dados - Câmara dos Deputados

## Visão Geral

Este documento descreve o processo de ingestão de dados da API aberta da Câmara dos Deputados (dadosabertos.camara.leg.br) para o banco de dados do Meu Candidato.

## Arquitetura

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  API Câmara     │────▶│  Worker Ingestion│────▶│  PostgreSQL     │
│  (REST/JSON)    │     │  (Python/Async)  │     │  (Async/SQLAlch)│
└─────────────────┘     └──────────────────┘     └─────────────────┘
                          │
                          ▼
                    ┌──────────────────┐
                    │  Armazenamento   │
                    │  Raw + Quarentena│
                    └──────────────────┘
```

## Fontes e Endpoints

| Fonte | Tipo | Observações |
|-------|------|-------------|
| `/deputados` (API) | JSON | Lista paginada de deputados |
| `/deputados/{id}` (API) | JSON | Detalhes completos do deputado |
| `/proposicoes` (API) | JSON | Lista paginada de proposições |
| `/proposicoes/{id}/votacoes` (API) | JSON | Votações de uma proposição |
| `/votacoes/{id}/votos` (API) | JSON | Votos individuais por votação |
| `/deputados/{id}/despesas` (API) | JSON | Despesas da cota (pode retornar vazio) |
| `deputados.csv` (dadosabertos) | CSV | Fonte alternativa para mandatos |

### ⚠️ Endpoint `/deputados/{id}/mandatos` - HTTP 405

O endpoint `/deputados/{id}/mandatos` da API v2 retorna **HTTP 405 (Method Not Allowed)** - a API só suporta `OPTIONS` neste recurso.

**Solução**: A ingestão de mandatos usa o CSV em `https://dadosabertos.camara.leg.br/arquivos/deputados/csv/deputados.csv`, que contém as colunas `idLegislaturaInicial` e `idLegislaturaFinal`. As informações de período (data início/fim) são obtidas via API `/legislaturas/{id}`.

## Arquivos Raw e Quarentena

### Raw (`data/raw/camara/{dataset}/{YYYY-MM-DD}/{id}.json`)

- Resposta bruta da API para auditoria e reprocessamento
- Organizado por dataset e data de ingestão
- Cada arquivo contém o JSON retornado pela API

### Quarentena (`data/quarantine/camara/{dataset}/{YYYY-MM-DD}.parquet`)

- Linhas que falharam validação/transformação
- Formato Parquet para análise posterior
- Inclui coluna `errors` com lista de problemas

## Rate Limiting e Resiliência

```python
# Configuração via RateLimitConfig (app/ingestion/domain/value_objects.py)
requests_per_second = 30
burst_limit = 20
```

**Rate limiting**: Implementado no `CamaraClient._get()` com:
- `asyncio.Semaphore` (compartilhado entre fases, tamanho 15)
- `asyncio.Lock` para proteção de estado de rate limiting
- `time.monotonic()` para precisão de timing

**Retry**: `tenacity` com exponential backoff (1s-30s), máximo 3 tentativas para:
- `httpx.RequestError`
- `httpx.HTTPStatusError`
- `RateLimitError`

## Execução

### Via Docker Compose

```yaml
# docker-compose.yml
worker:
  build: .
  command: python -m app.workers.ingestion
  depends_on:
    db:
      condition: service_healthy
```

### Manual

```bash
# Subir banco
docker compose up -d db

# Aplicar migrações
docker compose exec api alembic upgrade head

# Rodar ingestão
docker compose run --rm worker python -m app.workers.ingestion

# Rodar dataset específico
docker compose run --rm worker python -m app.workers.ingestion deputados
docker compose run --rm worker python -m app.workers.ingestion proposicoes
docker compose run --rm worker python -m app.workers.ingestion despesas
docker compose run --rm worker python -m app.workers.ingestion mandatos
```

## Paralelização

A ingestão roda todas as fases **concorrentemente** usando `asyncio.gather` com `return_exceptions=True`:

```python
await asyncio.gather(
    _run_phase(ingest_deputados, IngestionDataset.DEPUTADOS, shared_semaphore),
    _run_phase(ingest_proposicoes, IngestionDataset.PROPOSICOES, shared_semaphore),
    _run_phase(ingest_despesas, IngestionDataset.DESPESAS, shared_semaphore),
    _run_phase(ingest_mandatos, IngestionDataset.MANDATOS, shared_semaphore),
    return_exceptions=True,
)
```

Cada fase:
- Cria sua própria `async_session_maker()` sessão
- Cria seu próprio `IngestionJob` registro
- Usa o `shared_semaphore` (tamanho 15) para rate limiting

**Mandatos** usa um semáforo dedicado (tamanho 20) e cache de legislaturas para evitar requisições duplicadas.

## Monitoramento

### Logs Estruturados (JSON)

```json
{"level": "info", "event": "raw_saved", "path": "data/raw/camara/proposicoes/2026-10-08/0057.json", "source": "camara", "dataset": "proposicoes"}
{"level": "info", "event": "ingestion_started", "timestamp": "2026-10-08T20:55:38.714403Z"}
{"level": "info", "event": "ingestion_all_phases_completed"}
```

### Métricas-chave

| Métrica | Observação |
|---------|------------|
| Worker ativo | `docker compose logs -f worker` |
| Jobs no DB | `SELECT * FROM ingestion_jobs ORDER BY id DESC` |
| Arquivos raw | `ls data/raw/camara/{dataset}/` |

## Tratamento de Erros

| Cenário | Ação |
|---------|------|
| API retorna 429 | RateLimitError, retry via tenacity |
| API retorna 404 (votos) | Warning log, continua |
| API retorna 405 (mandatos) | CSV fallback implementado |
| API retorna vazio (despesas) | `dados: []`, loop termina normalmente |
| Erro em uma fase | `return_exceptions=True` no gather, outras fases continuam |

## Problemas Conhecidos

1. **Despesas**: API `/deputados/{id}/despesas` frequentemente retorna `{dados: []}` para deputados sem despesas no período atual.
2. **Votos antigos**: Algumas votações antigas retornam 404 no endpoint de votos (IDs com hífen inválidos).
3. **Mandatos**: Endpoint API oficial retorna 405. Usa CSV como workaround.
