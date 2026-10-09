# Ingestão de Dados - TSE (Financiamento de Campanha)

## Visão Geral

Este documento descreve o processo de ingestão de dados de financiamento de campanha do TSE (Tribunal Superior Eleitoral) via DivulgaCandContas.

## Arquitetura

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  TSE CDN        │────▶│  Worker Ingestion│────▶│  PostgreSQL     │
│  (CSV/ZIP)      │     │  (Python/Async)  │     │  (Async/SQLAlch)│
└─────────────────┘     └──────────────────┘     └─────────────────┘
                               │
                               ▼
                        ┌──────────────────┐
                        │  Armazenamento   │
                        │  Raw + Quarentena│
                        └──────────────────┘
```

## Fontes de Dados

| Fonte | Formato | Frequência | Descrição |
|-------|---------|------------|-----------|
| `divulgacandcontas.tse.jus.br` | CSV/ZIP | Por eleição + mensal | Receitas e despesas de campanha |
| `dadosabertos.tse.jus.br` | CSV | Histórico | Dados consolidados de eleições passadas |

**Eleições cobertas:**
- Federal (Presidente, Deputado Federal, Senador) - a cada 4 anos
- Estadual (Governador, Deputado Estadual) - a cada 4 anos (desfasado 2 anos)
- Municipal (Prefeito, Vereador) - a cada 4 anos

## Modelo de Dados

Tabela principal: `campaign_finances`

```sql
campaign_finances
├── id (PK)
├── politician_id (FK → politicians)  -- Resolvido via CPF/nome
├── election_year (2022, 2020, 2018...)
├── election_type (federal, estadual, municipal)
├── donor_type (pessoa_fisica, pessoa_juridica, fundo_partidario, recursos_proprios)
├── donor_name
├── donor_cpf_cnpj
├── amount (centavos)
├── donation_date
├── receipt_url (link para recibo no TSE)
```

## Processo de Ingestão

### 1. Download dos Arquivos

```python
# URLs padrão TSE (exemplo 2022):
# Receitas: https://cdn.tse.jus.br/estatistica/sead/odsele/prestacao_contas/prestacao_contas_2022.zip
# Despesas: https://cdn.tse.jus.br/estatistica/sead/odsele/prestacao_contas/prestacao_contas_2022.zip

# Dentro do ZIP: arquivos CSV por UF
# receitas_candidatos_2022_BRASIL.csv
# despesas_candidatos_2022_BRASIL.csv
```

### 2. Parsing e Transformação

```python
# Colunas principais CSV receitas:
# SQ_CANDIDATO, NR_CPF_CANDIDATO, NM_CANDIDATO, SG_PARTIDO, SG_UF,
# DS_CARGO, NR_TURNO, TP_RECEITA, DS_FONTE_RECEITA, NM_DOADOR,
# NR_CPF_CNPJ_DOADOR, VR_RECEITA, DT_RECEITA, NR_RECIBO_ELEITORAL

# Mapeamento:
# - politician_id: via lookup CPF → politicians.cpf
# - election_year: extraído do nome arquivo ou coluna
# - election_type: federal/estadual/municipal (por DS_CARGO)
# - donor_type: mapear DS_FONTE_RECEITA
# - amount: VR_RECEITA (converter para centavos)
# - donation_date: DT_RECEITA
# - receipt_url: construir URL TSE com NR_RECIBO_ELEITORAL
```

### 3. Mapeamento Tipo de Doador

| DS_FONTE_RECEITA (TSE) | donor_type (modelo) |
|------------------------|---------------------|
| Recursos de pessoas físicas | pessoa_fisica |
| Recursos de pessoas jurídicas | pessoa_juridica |
| Fundo Partidário | fundo_partidario |
| Recursos próprios | recursos_proprios |
| Recursos de outros candidatos/comitês | outros |
| Outras fontes | outros |

### 4. Mapeamento Tipo de Eleição

| DS_CARGO (TSE) | election_type (modelo) |
|----------------|------------------------|
| Presidente da República | federal |
| Senador | federal |
| Deputado Federal | federal |
| Governador | estadual |
| Vice-Governador | estadual |
| Deputado Estadual | estadual |
| Deputado Distrital | estadual |
| Prefeito | municipal |
| Vice-Prefeito | municipal |
| Vereador | municipal |

### 5. Upsert e Deduplicação

**Chave de upsert:** `(politician_id, election_year, donor_type, donor_name, donor_cpf_cnpj, donation_date, amount)`

```python
# Lógica:
# 1. Resolver politician_id via CPF do candidato
# 2. Se não encontrar: tentar match por nome + partido + UF (quarentena se ambíguo)
# 3. Upsert em campaign_finances
# 4. Linhas sem match de político → quarentena
```

## Resolução de Político (CPF → politician_id)

```python
async def resolve_politician(cpf_candidato: str, nome: str, partido: str, uf: str) -> int | None:
    # 1. Tentar match exato por CPF
    politician = await politician_repo.get_by_cpf(cpf_candidato)
    if politician:
        return politician.id

    # 2. Match fuzzy: nome + partido + UF
    candidates = await politician_repo.find_by_name_party_uf(nome, partido, uf)
    if len(candidates) == 1:
        return candidates[0].id

    # 3. Ambíguo ou não encontrado → quarentena
    return None
```

## Rate Limiting e Resiliência

```python
# TSE CDN é mais permissivo, mas respeitar:
RATE_LIMIT = 10  # req/s
REQUEST_DELAY = 1.0 / RATE_LIMIT

@retry(
    wait=wait_exponential_jitter(initial=5, max=120),
    stop=stop_after_attempt(3),
)
async def _download_file(self, url: str) -> bytes:
    await self._rate_limit()
    resp = await self.client.get(url, timeout=300)  # Arquivos grandes
    resp.raise_for_status()
    return resp.content
```

- **Rate limit:** 10 req/s
- **Retry:** Exponential backoff (5s-120s), máx 3 tentativas
- **Timeout:** 300s (arquivos ZIP grandes)

## Armazenamento Raw e Quarentena

### Raw (`data/raw/tse/{election_year}/{dataset}/{filename}`)

- Arquivos ZIP originais + CSVs extraídos
- Organizado por ano de eleição

### Quarentena (`data/quarantine/tse/{election_year}/{YYYY-MM-DD}.parquet`)

- Registros sem match de político (CPF não encontrado)
- Registros com valores inválidos
- Doadores com CPF/CNPJ malformado

## Execução

### Via Docker Compose (Produção)

```yaml
worker:
  build: .
  command: python -m app.workers.ingestion_tse
  depends_on:
    db:
      condition: service_healthy
  environment:
    - TSE_ELECTION_YEAR=2022  # Ou 'all' para histórico
```

### Manual (Desenvolvimento)

```bash
# Subir banco
docker compose up -d db

# Aplicar migrações
python scripts/migrate.py

# Rodar ingestão TSE (ano específico)
TSE_ELECTION_YEAR=2022 python -m app.workers.ingestion_tse

# Ou todas eleições históricas
TSE_ELECTION_YEAR=all python -m app.workers.ingestion_tse
```

### Agendamento

```python
# Configuração via .env
INGESTION_TSE_SCHEDULE_CRON=0 6 1 * *  # Mensal dia 1 às 06:00 UTC
INGESTION_TSE_ELECTION_YEAR=2022  # Ou 'auto' para detectar eleições recentes
INGESTION_BATCH_SIZE=5000
INGESTION_TIMEOUT_SECONDS=600
```

## Monitoramento

### Logs Estruturados (JSON)

```json
{"level": "info", "event": "tse_download_started", "year": 2022, "file": "receitas_candidatos_2022_BRASIL.csv"}
{"level": "info", "event": "tse_records_parsed", "count": 1250000}
{"level": "info", "event": "tse_politicians_matched", "matched": 1180000, "unmatched": 70000}
{"level": "info", "event": "tse_ingestion_done", "election_year": 2022, "receitas": 1250000, "despesas": 980000, "quarentena": 70000}
```

### Métricas-chave

| Métrica | Alerta |
|---------|--------|
| Taxa match político < 90% | Investigar CPFs/nomens |
| Tempo download > 30min | CDN lenta |
| Quarentena > 10% | Validar parsing CSV |
| Falha consecutiva 3x | Alertar on-call |

## Tratamento de Erros

| Cenário | Ação |
|---------|------|
| Arquivo ZIP corrompido | Retry download, depois alerta |
| Encoding CSV errado | Tentar latin-1, cp1252, utf-8 |
| CPF/CNPJ doador inválido | Quarentena, continuar |
| Valor negativo | Quarentena (exceto estorno documentado) |
| Data inválida | Quarentena |
| Político não encontrado | Quarentena com dados para match manual |

## Testes

```bash
# Testar download TSE
python -c "
import asyncio
from app.workers.ingestion_tse import TSEClient
async def test():
    c = TSEClient()
    url = await c.get_receitas_url(2022)
    print(f'URL: {url}')
    await c.close()
asyncio.run(test())
"

# Dry-run parse CSV (sem DB)
python -c "
import asyncio
import structlog
from app.workers.ingestion_tse import parse_receitas_csv, save_raw
async def dry_run():
    structlog.configure(processors=[structlog.processors.JSONRenderer()])
    # Download sample
    import httpx
    async with httpx.AsyncClient() as client:
        resp = await client.get('https://cdn.tse.jus.br/estatistica/sead/odsele/prestacao_contas/prestacao_contas_2022.zip')
        # Extract and parse first 100 lines
        pass
    print('Parsing test OK')
asyncio.run(dry_run())
"
```

## Problemas Conhecidos

1. **Arquivos grandes**: ZIPs de 500MB+ → streaming download + parse chunked
2. **Encoding**: TSE usa latin-1/cp1252 às vezes, não UTF-8
3. **CPF doador**: Pessoa jurídica tem CNPJ (14 dígitos), PF tem CPF (11)
4. **Valores em reais**: TSE usa vírgula decimal (1.234,56) → converter para centavos
5. **Eleições suplementares**: Ano não padrão, election_type detectar por cargo
6. **Prestação de contas retificadoras**: Múltiplos registros mesma eleição → última versão
7. **Candidatos não eleitos**: Também têm dados → incluir (transparência)

## Próximas Melhorias

- [ ] Streaming parse CSV (não carregar tudo na memória)
- [ ] Match fuzzy avançado para políticos (Levenshtein no nome)
- [ ] Ingestão incremental (apenas novos recibos)
- [ ] Validação cruzada: receitas ≃ despesas + saldo
- [ ] Ingestão histórica completa (1994-2022)
- [ ] API unificada TSE (novos endpoints JSON)