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

## Fontes de Dados

| Endpoint | Descrição | Frequência |
|----------|-----------|------------|
| `/deputados` | Lista de deputados atuais e históricos | Diária |
| `/deputados/{id}` | Detalhes do deputado (gabinete, partido, etc.) | Diária |
| `/deputados/{id}/mandatos` | Mandatos do deputado | Diária |
| `/proposicoes` | Proposições legislativas (PL, PDC, REQ, etc.) | Diária |
| `/proposicoes/{id}` | Detalhes da proposição + votações | Diária |
| `/votacoes/{id}/votos` | Votos individuais por votação | Diária |
| `/deputados/{id}/despesas` | Despesas da cota parlamentar | Diária |

## Modelo de Dados

### Tabelas Principais

```sql
politicians          -- Deputados/Senadores
mandates             -- Mandatos (câmara, senado, suplente)
propositions         -- Proposições legislativas
votes                -- Votos individuais (FK politician + proposition)
expenses             -- Despesas da cota parlamentar
campaign_finances    -- Financiamento de campanha (TSE)
```

### Relacionamentos

```
Politician 1 ─────< N Mandate
Politician 1 ─────< N Proposition
Politician 1 ─────< N Expense
Politician 1 ─────< N CampaignFinance
Politician 1 ─────< N Vote
Proposition 1 ─────< N Vote
```

## Processo de Ingestão

### 1. Deputados e Mandatos

```python
# Fluxo:
# 1. Listar todos deputados (paginado)
# 2. Para cada deputado: buscar detalhes + mandatos
# 3. Upsert em politicians (chave: external_id)
# 4. Resolver politician_id interno para mandatos
# 4. Upsert em mandates
```

**Chave de upsert:** `external_id` (ID da Câmara)

### 2. Proposições

```python
# Fluxo:
# 1. Listar proposições dos últimos 2 anos (paginado)
# 2. Para cada proposição: buscar detalhes + autor + votações
# 3. Montar external_id: "{siglaTipo}-{numero}-{ano}"
# 4. Upsert em propositions (chave: external_id)
# 5. Coletar votos de cada votação da proposição
# 5.1 Votos guardam proposition_external_id temporariamente
```

**Chave de upsert:** `external_id` (formato: `PL-1234-2024`)

### 3. Votos (Corrigido)

```python
# Fluxo CORRIGIDO:
# 1. Durante coleta de proposições, para cada votação:
#    - Buscar votos da votação
#    - Para cada voto: resolver politician_id via external_id
#    - Guardar proposition_external_id (não ID interno)
# 2. Após upsert de proposições:
#    - Buscar mapa external_id → id interno
#    - Substituir proposition_external_id por proposition_id
# 3. Upsert em votes (chave: politician_id + proposition_id)
```

**Chave de upsert:** `(politician_id, proposition_id)` - voto único por deputado/proposição

### 4. Despesas

```python
# Fluxo:
# 1. Para cada deputado atual (últimos 2 anos):
#    - Listar despesas por ano (paginado)
#    - Converter valor para centavos (int)
#    - Extrair ano/mês da data
# 2. Upsert em expenses
```

**Chave de upsert:** `(politician_id, expense_type, expense_date, document_number)`

## Armazenamento Raw e Quarentena

### Raw (`data/raw/camara/{dataset}/{YYYY-MM-DD}/{page:04d}.json`)

- Resposta bruta da API para auditoria e reprocessamento
- Organizado por dataset e data de ingestão

### Quarentena (`data/quarantine/camara/{dataset}/{YYYY-MM-DD}.parquet`)

- Linhas que falharam validação/transformação
- Formato Parquet para análise posterior
- Inclui coluna `errors` com lista de problemas

## Rate Limiting e Resiliência

```python
RATE_LIMIT = 30  # req/s burst
REQUEST_DELAY = 1.0 / RATE_LIMIT

@retry(
    wait=wait_exponential_jitter(initial=1, max=30),
    stop=stop_after_attempt(3),
)
async def _get(self, path: str, params: dict | None = None) -> dict:
    await self._rate_limit()
    resp = await self.client.get(path, params=params)
    resp.raise_for_status()
    return resp.json()
```

- **Rate limit:** 30 req/s com delay calculado
- **Retry:** Exponential backoff (1s-30s), máx 3 tentativas
- **Timeout:** 30s total, 10s connect

## Execução

### Via Docker Compose (Produção)

```yaml
worker:
  build: .
  command: python -m app.workers.ingestion
  depends_on:
    db:
      condition: service_healthy
```

### Manual (Desenvolvimento)

```bash
# Subir banco
docker compose up -d db

# Aplicar migrações
python scripts/migrate.py

# Rodar ingestão
python -m app.workers.ingestion
```

### Agendamento

```python
# Configuração via .env
INGESTION_SCHEDULE_CRON=0 3 * * *  # Diário às 03:00 UTC
INGESTION_BATCH_SIZE=1000
INGESTION_TIMEOUT_SECONDS=300
```

## Monitoramento

### Logs Estruturados (JSON)

```json
{"level": "info", "event": "deputados_fetched", "count": 513}
{"level": "info", "event": "politicians_upserted", "inserted": 12, "updated": 501}
{"level": "info", "event": "camara_ingestion_done", "deputados": 513, "mandatos": 513, "proposicoes": 15420, "votos": 45230, "despesas": 89234}
```

### Métricas-chave

| Métrica | Alerta |
|---------|--------|
| Tempo total > 30min | Investigar lentidão API |
| Votos com proposition_id NULL | Bug no linking |
| Quarentena > 5% das linhas | Validar parsing |
| Falha consecutiva 3x | Alertar on-call |

## Tratamento de Erros

| Cenário | Ação |
|---------|------|
| API retorna 429 | Backoff exponencial automático |
| API retorna 5xx | Retry 3x, depois quarantena |
| Campo obrigatório ausente | Linha para quarentena, continuar |
| FK não encontrada (deputado) | Log warning, pular linha |
| Duplicate key | Upsert (on_conflict_do_update) |

## Testes

```bash
# Testar conexão API
python -c "
import asyncio
from app.workers.ingestion_camara import CamaraClient
async def test():
    c = CamaraClient()
    data = await c.list_deputados(pagina=1, itens=5)
    print(f'Deputados: {len(data[\"dados\"])}')
    await c.close()
asyncio.run(test())
"

# Dry-run (sem DB) - logar apenas
python -c "
import asyncio
import structlog
from app.workers.ingestion_camara import CamaraClient, save_raw
async def dry_run():
    structlog.configure(processors=[structlog.processors.JSONRenderer()])
    c = CamaraClient()
    data = await c.list_deputados(pagina=1, itens=10)
    save_raw('deputados', 1, data)
    print('Raw salvo em data/raw/camara/deputados/')
    await c.close()
asyncio.run(dry_run())
"
```

## Problemas Conhecidos

1. **Votos de proposições antigas**: API pode não retornar votações para proposições > 2 anos
2. **Deputados suplentes**: `is_suplente` vem do endpoint de mandatos, não de deputados
3. **Despesas sem documento**: Alguns registros não têm `urlDocumento` ou `numDocumento`
4. **Partido mudando**: Upsert atualiza `party` automaticamente via `on_conflict_do_update`

## Próximas Melhorias

- [ ] Ingestão incremental (usar `dataInicio` / `dataApresentacaoInicio`)
- [ ] Paralelização por deputado (asyncio.gather com semáforo)
- [ ] Cache de `ext_to_int` maps em Redis
- [ ] Ingestão Senado (legis.senado.leg.br)
- [ ] Ingestão TSE (financiamento de campanha)