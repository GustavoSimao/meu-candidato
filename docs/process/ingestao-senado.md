# Ingestão de Dados - Senado Federal

## Visão Geral

Este documento descreve o processo de ingestão de dados da API aberta do Senado Federal (legis.senado.leg.br) para o banco de dados do Meu Candidato. Espelha a estrutura do documento da Câmara.

## Arquitetura

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  API Senado     │────▶│  Worker Ingestion│────▶│  PostgreSQL     │
│  (XML/JSON)     │     │  (Python/Async)  │     │  (Async/SQLAlch)│
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
| `/senadores` | Lista de senadores atuais e históricos | Diária |
| `/senador/{id}` | Detalhes do senador (gabinete, partido, etc.) | Diária |
| `/senador/{id}/mandatos` | Mandatos do senador | Diária |
| `/proposicoes` | Proposições legislativas (PL, PEC, etc.) | Diária |
| `/proposicao/{id}` | Detalhes da proposição + votações | Diária |
| `/votacoes/{id}/votos` | Votos individuais por votação | Diária |

## Modelo de Dados

Mesmas tabelas da Câmara (modelo unificado):

```sql
politicians          -- Deputados/Senadores (com external_id do Senado)
mandates             -- Mandatos (house='senado')
propositions         -- Proposições (house='senado')
votes                -- Votos (FK politician + proposition)
expenses             -- NÃO SE APLICA (Senado não tem cota igual Câmara)
campaign_finances    -- Financiamento de campanha (TSE)
```

**Diferenças chave:**
- Senadores não têm cota parlamentar com detalhamento público igual à Câmara
- `politicians.external_id` armazena ID do Senado para senadores
- `mandates.house = 'senado'` e `role = 'senador'`

## Processo de Ingestão

### 1. Senadores e Mandatos

```python
# Fluxo:
# 1. Listar todos senadores (paginado)
# 2. Para cada senador: buscar detalhes + mandatos
# 3. Upsert em politicians (chave: external_id do Senado)
# 4. Resolver politician_id interno para mandatos
# 5. Upsert em mandates (house='senado', role='senador')
```

**Chave de upsert:** `external_id` (ID do Senado)

### 2. Proposições do Senado

```python
# Fluxo:
# 1. Listar proposições dos últimos 2 anos (paginado)
# 2. Para cada proposição: buscar detalhes + autor + votações
# 3. Montar external_id: "{siglaTipo}-{numero}-{ano}" (ex: PEC-12-2023)
# 4. Upsert em propositions (chave: external_id, house='senado')
# 5. Coletar votos de cada votação da proposição
```

**Chave de upsert:** `external_id` (formato: `PEC-12-2023`) + `house='senado'`

### 3. Votos (Senado)

```python
# Fluxo:
# 1. Durante coleta de proposições, para cada votação:
#    - Buscar votos da votação
#    - Para cada voto: resolver politician_id via external_id
#    - Guardar proposition_external_id temporariamente
# 2. Após upsert de proposições:
#    - Buscar mapa external_id → id interno
#    - Substituir proposition_external_id por proposition_id
# 3. Upsert em votes (chave: politician_id + proposition_id)
```

**Chave de upsert:** `(politician_id, proposition_id)` - voto único por senador/proposição

## Mapeamento de Campos (Senado → Modelo Unificado)

### Senador → Politician

| Campo Senado | Campo Modelo | Notas |
|--------------|--------------|-------|
| `CodigoParlamentar` | `external_id` | Chave única |
| `NomeParlamentar` | `name` | Nome civil |
| `Partido` | `party` | Sigla do partido |
| `Uf` | `uf` | UF |
| `NumeroEleitoral` | `number` | Número |
| `Email` | `email` | Email oficial |
| `Gabinete` | `office_address` | Endereço gabinete |
| `Telefone` | `office_phone` | Telefone |
| `Biografia` | `biography` | Texto livre |
| `UrlFoto` | `photo_url` | URL foto oficial |

### Mandato → Mandate

| Campo Senado | Campo Modelo | Notas |
|--------------|--------------|-------|
| `CodigoParlamentar` | `politician_id` (via lookup) | FK |
| `Casa` | `house` | Sempre 'senado' |
| `Cargo` | `role` | Sempre 'senador' |
| `Uf` | `uf` | UF do mandato |
| `DataInicio` | `start_date` | Início |
| `DataFim` | `end_date` | Fim (NULL = atual) |
| `Suplente` | `is_suplente` | Boolean |

### Proposição → Proposition

| Campo Senado | Campo Modelo | Notas |
|--------------|--------------|-------|
| `IdentificacaoProposicao` | `external_id` | `PEC-12-2023` |
| `Autor.CodigoParlamentar` | `politician_id` (via lookup) | FK |
| `SiglaTipo` | `type` | PEC, PL, etc. |
| `Ementa` | `title` | Ementa |
| `Descricao` | `summary` | Descrição |
| `Situacao` | `status` | Status tramitação |
| `DataApresentacao` | `presentation_date` | Data |
| `Casa` | `house` | 'senado' |
| `UrlInteiroTeor` | `url` | Link texto integral |

### Votação/Voto → Vote

| Campo Senado | Campo Modelo | Notas |
|--------------|--------------|-------|
| `CodigoSessao` | `session_number` | Número sessão |
| `DataSessao` | `session_date` | Data |
| `Voto` | `vote_value` | Sim/Não/Abstenção → favor/contra/abstencao |
| `Senador.CodigoParlamentar` | `politician_id` (via lookup) | FK |
| `Proposicao.Identificacao` | `proposition_id` (via lookup) | FK |

## Formato da API Senado

A API do Senado retorna **XML** por padrão, com opção de JSON via parâmetro `tipo=json`.

Exemplo resposta senadores:
```xml
<Senadores>
  <Senador>
    <CodigoParlamentar>1234</CodigoParlamentar>
    <NomeParlamentar>João Silva</NomeParlamentar>
    <Partido>PT</Partido>
    <Uf>SP</Uf>
    ...
  </Senador>
</Senadores>
```

**Parsing:** Usar `xmltodict` ou `lxml` para converter XML → dict.

## Rate Limiting e Resiliência

```python
RATE_LIMIT = 20  # req/s (mais conservador que Câmara)
REQUEST_DELAY = 1.0 / RATE_LIMIT

@retry(
    wait=wait_exponential_jitter(initial=2, max=60),
    stop=stop_after_attempt(3),
)
async def _get(self, path: str, params: dict | None = None) -> dict:
    await self._rate_limit()
    # Adicionar header Accept: application/json
    resp = await self.client.get(path, params=params, headers={"Accept": "application/json"})
    resp.raise_for_status()
    # Parse XML se necessário
    return parse_response(resp.content)
```

- **Rate limit:** 20 req/s
- **Retry:** Exponential backoff (2s-60s), máx 3 tentativas
- **Timeout:** 60s total, 15s connect (XML pode ser maior)

## Armazenamento Raw e Quarentena

### Raw (`data/raw/senado/{dataset}/{YYYY-MM-DD}/{page:04d}.json`)

- Resposta parseada (dict) salva como JSON
- Organizado por dataset e data de ingestão

### Quarentena (`data/quarantine/senado/{dataset}/{YYYY-MM-DD}.parquet`)

- Linhas que falharam validação/transformação
- Mesmo formato da Câmara

## Execução

### Via Docker Compose (Produção)

```yaml
worker:
  build: .
  command: python -m app.workers.ingestion_senado
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

# Rodar ingestão Senado
python -m app.workers.ingestion_senado
```

### Agendamento

```python
# Configuração via .env
INGESTION_SENADO_SCHEDULE_CRON=0 4 * * *  # Diário às 04:00 UTC (após Câmara)
INGESTION_BATCH_SIZE=1000
INGESTION_TIMEOUT_SECONDS=600
```

## Monitoramento

### Logs Estruturados (JSON)

```json
{"level": "info", "event": "senadores_fetched", "count": 81}
{"level": "info", "event": "senadores_upserted", "inserted": 2, "updated": 79}
{"level": "info", "event": "senado_ingestion_done", "senadores": 81, "mandatos": 81, "proposicoes": 5420, "votos": 12340}
```

### Métricas-chave

| Métrica | Alerta |
|---------|--------|
| Tempo total > 45min | Investigar lentidão API |
| Votos com proposition_id NULL | Bug no linking |
| Quarentena > 5% das linhas | Validar parsing XML |
| Falha consecutiva 3x | Alertar on-call |

## Tratamento de Erros

| Cenário | Ação |
|---------|------|
| API retorna 429 | Backoff exponencial automático |
| API retorna 5xx | Retry 3x, depois quarantena |
| XML malformado | Linha para quarentena, continuar |
| Campo obrigatório ausente | Linha para quarentena, continuar |
| FK não encontrada (senador) | Log warning, pular linha |
| Duplicate key | Upsert (on_conflict_do_update) |

## Testes

```bash
# Testar conexão API
python -c "
import asyncio
from app.workers.ingestion_senado import SenadoClient
async def test():
    c = SenadoClient()
    data = await c.list_senadores(pagina=1, itens=5)
    print(f'Senadores: {len(data[\"Senadores\"][\"Senador\"])}')
    await c.close()
asyncio.run(test())
"

# Dry-run (sem DB) - logar apenas
python -c "
import asyncio
import structlog
from app.workers.ingestion_senado import SenadoClient, save_raw
async def dry_run():
    structlog.configure(processors=[structlog.processors.JSONRenderer()])
    c = SenadoClient()
    data = await c.list_senadores(pagina=1, itens=10)
    save_raw('senadores', 1, data)
    print('Raw salvo em data/raw/senado/senadores/')
    await c.close()
asyncio.run(dry_run())
"
```

## Problemas Conhecidos

1. **XML namespaces**: API usa namespaces que complicam parsing
2. **Senadores suplentes**: `Suplente` vem no endpoint de mandatos
3. **Proposições antigas**: API pode não retornar votações para proposições > 2 anos
4. **Sem cota parlamentar**: Senado não publica detalhamento de gastos igual Câmara
5. **Partido mudando**: Upsert atualiza `party` automaticamente

## Próximas Melhorias

- [ ] Ingestão incremental (usar `DataAtualizacao` / `DataApresentacaoInicio`)
- [ ] Paralelização por senador (asyncio.gather com semáforo)
- [ ] Cache de `ext_to_int` maps em Redis
- [ ] Validação de schema XML com XSD
- [ ] Ingestão TSE (financiamento de campanha)