# API Reference - Meu Candidato

## Base URL

```
Development: http://localhost:8000/api/v1
Production:  https://api.meucandidato.org/api/v1
```

## Autenticação

Atualmente a API é pública (sem autenticação). Futuramente: Bearer token / API Key.

## Convenções

### Respostas de sucesso

- **Recurso único:** objeto JSON direto
- **Lista:** `{items, total, page, per_page, pages}`

### Respostas de erro

```json
{
  "error": "not_found",
  "message": "Politician not found",
  "resource": "politician",
  "identifier": 123
}
```

### Headers

- `X-Request-ID`: UUID único por requisição
- `Cache-Control`: política de cache por endpoint
- `Content-Type: application/json`

### Paginação

- `page`: página (default: 1, mínimo: 1)
- `per_page`: itens por página (default: 20, máximo: 100)

### Ordenação

- `sort_by`: campo (whitelist por endpoint)
- `order`: `asc` ou `desc` (default: `asc`)

### Filtros

Query params fixos por endpoint (type-safe via Pydantic).

---

## Endpoints

---

### Health Check

```
GET /health
```

**Response 200:**
```json
{
  "status": "ok",
  "environment": "development"
}
```

---

### Políticos

#### Listar Políticos

```
GET /api/v1/politicians
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| page | int | 1 | Página (≥1) |
| per_page | int | 20 | Itens por página (1-100) |
| uf | string | - | Filtrar por UF (ex: SP, RJ) |
| party | string | - | Filtrar por partido (busca parcial, case-insensitive) |

**Response 200:**
```json
{
  "items": [
    {
      "id": 1,
      "name": "João Silva",
      "party": "PT",
      "uf": "SP",
      "number": 1312,
      "cpf": "123.456.789-00",
      "email": "joao@camara.leg.br",
      "office_address": "Anexo IV, Gabinete 123",
      "office_phone": "(61) 3215-1234",
      "biography": "Deputado federal por SP...",
      "social_media": {"twitter": "@joaosilva", "instagram": "@joaosilva"},
      "education": "Direito - USP",
      "photo_url": "https://www.camara.leg.br/internet/deputado/bandep/123456.jpg",
      "badges": []
    }
  ],
  "total": 513,
  "page": 1,
  "per_page": 20,
  "pages": 26
}
```

#### Detalhar Político

```
GET /api/v1/politicians/{politician_id}
```

**Response 200:**
```json
{
  "id": 1,
  "name": "João Silva",
  "party": "PT",
  "uf": "SP",
  "number": 1312,
  "cpf": "123.456.789-00",
  "email": "joao@camara.leg.br",
  "office_address": "Anexo IV, Gabinete 123",
  "office_phone": "(61) 3215-1234",
  "biography": "Deputado federal por SP...",
  "social_media": {"twitter": "@joaosilva", "instagram": "@joaosilva"},
  "education": "Direito - USP",
  "photo_url": "https://www.camara.leg.br/internet/deputado/bandep/123456.jpg",
  "mandates": [
    {
      "house": "camara",
      "role": "deputado",
      "uf": "SP",
      "start_date": "2023-02-01",
      "end_date": null,
      "is_suplente": false
    }
  ],
  "badges": []
}
```

---

### Votações

#### Listar Votos

```
GET /api/v1/votes
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| page | int | 1 | Página (≥1) |
| per_page | int | 20 | Itens por página (1-100) |
| politician_id | int | - | Filtrar por político |
| proposition_id | int | - | Filtrar por proposição |
| vote_value | string | - | Filtrar por valor (favor, contra, abstencao, ausente, obstrucao, art17, desconhecido) |
| session_date_from | date | - | Data inicial (YYYY-MM-DD) |
| session_date_to | date | - | Data final (YYYY-MM-DD) |

**Response 200:**
```json
{
  "items": [
    {
      "id": 1,
      "politician_id": 1,
      "proposition_id": 10,
      "session_date": "2024-03-15",
      "vote_value": "favor",
      "session_number": "123"
    }
  ],
  "total": 45230,
  "page": 1,
  "per_page": 20,
  "pages": 2262
}
```

#### Estatísticas de Votos

```
GET /api/v1/votes/stats
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| politician_id | int | - | Filtrar por político |

**Response 200:**
```json
{
  "favor": 15230,
  "contra": 8920,
  "abstencao": 2100,
  "ausente": 12450,
  "obstrucao": 5430,
  "art17": 120,
  "desconhecido": 340
}
```

#### Detalhar Voto

```
GET /api/v1/votes/{vote_id}
```

**Response 200:**
```json
{
  "id": 1,
  "politician_id": 1,
  "proposition_id": 10,
  "session_date": "2024-03-15",
  "vote_value": "favor",
  "session_number": "123",
  "politician_name": "João Silva",
  "proposition_title": "PL 1234/2024 - Altera a Lei de..."
}
```

---

### Proposições

#### Listar Proposições

```
GET /api/v1/propositions
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| page | int | 1 | Página (≥1) |
| per_page | int | 20 | Itens por página (1-100) |
| politician_id | int | - | Filtrar por autor |
| type | string | - | Filtrar por tipo (PL, PDC, REQ, PEC, etc.) |
| status | string | - | Filtrar por status |
| house | string | - | Filtrar por casa (camara, senado) |
| presentation_date_from | date | - | Data inicial (YYYY-MM-DD) |
| presentation_date_to | date | - | Data final (YYYY-MM-DD) |

**Response 200:**
```json
{
  "items": [
    {
      "id": 10,
      "external_id": "PL-1234-2024",
      "politician_id": 1,
      "type": "PL",
      "title": "Altera a Lei de...",
      "summary": "Esta proposição visa...",
      "status": "Em tramitação",
      "presentation_date": "2024-02-15",
      "house": "camara",
      "url": "https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao=123456"
    }
  ],
  "total": 15420,
  "page": 1,
  "per_page": 20,
  "pages": 771
}
```

#### Detalhar Proposição

```
GET /api/v1/propositions/{proposition_id}
```

**Response 200:**
```json
{
  "id": 10,
  "external_id": "PL-1234-2024",
  "politician_id": 1,
  "type": "PL",
  "title": "Altera a Lei de...",
  "summary": "Esta proposição visa...",
  "status": "Em tramitação",
  "presentation_date": "2024-02-15",
  "house": "camara",
  "url": "https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao=123456",
  "politician_name": "João Silva",
  "votes_count": 423,
  "votes_favor": 234,
  "votes_contra": 156,
  "votes_abstencao": 33
}
```

---

### Despesas

#### Listar Despesas

```
GET /api/v1/expenses
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| page | int | 1 | Página (≥1) |
| per_page | int | 20 | Itens por página (1-100) |
| politician_id | int | - | Filtrar por político |
| expense_type | string | - | Filtrar por tipo (ex: "Passagens aéreas", "Combustíveis") |
| year | int | - | Filtrar por ano |
| month | int | - | Filtrar por mês (1-12) |
| expense_date_from | date | - | Data inicial (YYYY-MM-DD) |
| expense_date_to | date | - | Data final (YYYY-MM-DD) |

**Response 200:**
```json
{
  "items": [
    {
      "id": 1,
      "politician_id": 1,
      "expense_type": "Passagens aéreas",
      "description": "Brasília - São Paulo - Brasília",
      "amount": 125000,
      "expense_date": "2024-03-10",
      "provider": "GOL Linhas Aéreas",
      "document_number": "NF-123456",
      "document_url": "https://www.camara.leg.br/cota-parlamentar/documentos/...",
      "year": 2024,
      "month": 3
    }
  ],
  "total": 89234,
  "page": 1,
  "per_page": 20,
  "pages": 4462
}
```

#### Resumo de Despesas

```
GET /api/v1/expenses/summary
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| politician_id | int | - | Filtrar por político |
| year | int | - | Filtrar por ano |

**Response 200:**
```json
{
  "total_amount": 452300000,
  "total_count": 89234,
  "by_type": [
    {"type": "Passagens aéreas", "amount": 125000000, "count": 12340},
    {"type": "Combustíveis", "amount": 89000000, "count": 34560},
    {"type": "Hospedagem", "amount": 67000000, "count": 15230}
  ],
  "by_month": [
    {"period": "2024-01", "year": 2024, "month": 1, "amount": 45000000, "count": 8230},
    {"period": "2024-02", "year": 2024, "month": 2, "amount": 48000000, "count": 8920},
    {"period": "2024-03", "year": 2024, "month": 3, "amount": 52000000, "count": 9340}
  ]
}
```

#### Detalhar Despesa

```
GET /api/v1/expenses/{expense_id}
```

**Response 200:**
```json
{
  "id": 1,
  "politician_id": 1,
  "expense_type": "Passagens aéreas",
  "description": "Brasília - São Paulo - Brasília",
  "amount": 125000,
  "expense_date": "2024-03-10",
  "provider": "GOL Linhas Aéreas",
  "document_number": "NF-123456",
  "document_url": "https://www.camara.leg.br/cota-parlamentar/documentos/...",
  "year": 2024,
  "month": 3,
  "politician_name": "João Silva"
}
```

---

### Financiamento de Campanha

#### Listar Financiamentos

```
GET /api/v1/campaigns
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| page | int | 1 | Página (≥1) |
| per_page | int | 20 | Itens por página (1-100) |
| politician_id | int | - | Filtrar por político |
| election_year | int | - | Filtrar por ano da eleição |
| election_type | string | - | Filtrar por tipo (federal, estadual, municipal) |
| donor_type | string | - | Filtrar por tipo doador (pessoa_fisica, pessoa_juridica, fundo_partidario, recursos_proprios) |

**Response 200:**
```json
{
  "items": [
    {
      "id": 1,
      "politician_id": 1,
      "election_year": 2022,
      "election_type": "federal",
      "donor_type": "pessoa_juridica",
      "donor_name": "Empresa XYZ LTDA",
      "donor_cpf_cnpj": "12.345.678/0001-90",
      "amount": 5000000,
      "donation_date": "2022-09-15",
      "receipt_url": "https://divulgacandcontas.tse.jus.br/..."
    }
  ],
  "total": 12450,
  "page": 1,
  "per_page": 20,
  "pages": 623
}
```

#### Resumo de Financiamento

```
GET /api/v1/campaigns/summary
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| politician_id | int | - | Filtrar por político |
| election_year | int | - | Filtrar por ano da eleição |

**Response 200:**
```json
{
  "total_amount": 1250000000,
  "total_count": 12450,
  "by_donor_type": [
    {"donor_type": "pessoa_juridica", "amount": 650000000, "count": 5230},
    {"donor_type": "fundo_partidario", "amount": 400000000, "count": 1230},
    {"donor_type": "pessoa_fisica", "amount": 200000000, "count": 6000}
  ],
  "by_election_year": [
    {"election_year": 2022, "amount": 850000000, "count": 8230},
    {"election_year": 2020, "amount": 400000000, "count": 4220}
  ],
  "top_donors": [
    {"donor_name": "Empresa XYZ LTDA", "amount": 50000000, "count": 1, "donor_type": "pessoa_juridica"},
    {"donor_name": "Fundo Partidário PT", "amount": 40000000, "count": 1, "donor_type": "fundo_partidario"}
  ]
}
```

#### Detalhar Financiamento

```
GET /api/v1/campaigns/{campaign_id}
```

**Response 200:**
```json
{
  "id": 1,
  "politician_id": 1,
  "election_year": 2022,
  "election_type": "federal",
  "donor_type": "pessoa_juridica",
  "donor_name": "Empresa XYZ LTDA",
  "donor_cpf_cnpj": "12.345.678/0001-90",
  "amount": 5000000,
  "donation_date": "2022-09-15",
  "receipt_url": "https://divulgacandcontas.tse.jus.br/...",
  "politician_name": "João Silva"
}
```

---

### Ingestão (Monitoramento)

#### Iniciar Job

```
POST /api/v1/ingestion/jobs
```

**Body:**
```json
{
  "data_source": "camara",
  "dataset": "deputados"
}
```

**Response 201:**
```json
{
  "id": 1,
  "data_source": "camara",
  "dataset": "deputados",
  "status": "running",
  "started_at": "2024-03-15T10:30:00",
  "completed_at": null,
  "records_processed": 0,
  "records_inserted": 0,
  "records_updated": 0,
  "records_failed": 0,
  "error_message": null,
  "metadata": {}
}
```

#### Listar Jobs

```
GET /api/v1/ingestion/jobs
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| page | int | 1 | Página (≥1) |
| per_page | int | 20 | Itens por página (1-100) |
| data_source | string | - | Filtrar por fonte (camara, senado, tse) |
| dataset | string | - | Filtrar por dataset |
| status | string | - | Filtrar por status (pending, running, completed, failed, partial) |

#### Resumo de Jobs

```
GET /api/v1/ingestion/jobs/summary
```

**Response 200:**
```json
{
  "total_jobs": 10,
  "successful_jobs": 8,
  "failed_jobs": 1,
  "partial_jobs": 1,
  "total_records_processed": 50000,
  "by_source": {
    "camara": {"jobs": 5, "records": 30000},
    "senado": {"jobs": 3, "records": 15000},
    "tse": {"jobs": 2, "records": 5000}
  }
}
```

#### Último Job

```
GET /api/v1/ingestion/jobs/latest?data_source=camara&dataset=deputados
```

#### Detalhar Job

```
GET /api/v1/ingestion/jobs/{job_id}
```

#### Atualizar Job

```
PATCH /api/v1/ingestion/jobs/{job_id}
```

#### Deletar Job

```
DELETE /api/v1/ingestion/jobs/{job_id}
```

#### Adicionar Registro em Quarentena

```
POST /api/v1/ingestion/quarantine
```

**Body:**
```json
{
  "data_source": "camara",
  "dataset": "deputados",
  "raw_data": {"id": 123, "nome": "João"},
  "errors": ["campo obrigatório ausente"]
}
```

#### Adicionar Lote em Quarentena

```
POST /api/v1/ingestion/quarantine/batch
```

**Body:** array de registros

#### Listar Quarentena

```
GET /api/v1/ingestion/quarantine
```

**Query Parameters:**
| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| page | int | 1 | Página (≥1) |
| per_page | int | 20 | Itens por página (1-100) |
| data_source | string | - | Filtrar por fonte |
| dataset | string | - | Filtrar por dataset |
| resolved | bool | - | Filtrar por resolvido |

#### Detalhar Registro em Quarentena

```
GET /api/v1/ingestion/quarantine/{record_id}
```

#### Resolver Registro em Quarentena

```
PATCH /api/v1/ingestion/quarantine/{record_id}/resolve
```

#### Deletar Registro em Quarentena

```
DELETE /api/v1/ingestion/quarantine/{record_id}
```

---

## Códigos de Erro

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 400 | Parâmetros inválidos |
| 404 | Recurso não encontrado |
| 422 | Erro de validação (Pydantic) |
| 500 | Erro interno do servidor |

**Formato de erro 422:**
```json
{
  "detail": [
    {
      "loc": ["query", "page"],
      "msg": "Input should be greater than or equal to 1",
      "type": "greater_than_equal"
    }
  ]
}
```

---

## Paginação

Todas as listagens seguem o mesmo padrão:
- `page`: página atual (1-indexed)
- `per_page`: itens por página
- `total`: total de registros
- `pages`: total de páginas

## Filtros Comuns

- Datas: formato ISO 8601 (`YYYY-MM-DD`)
- Strings: busca exata ou parcial conforme documentado
- Enums: valores válidos documentados em cada endpoint

---

## Exemplos de Uso

### Buscar deputados de SP do PT
```bash
curl "http://localhost:8000/api/v1/politicians?uf=SP&party=PT"
```

### Votos de um político em 2024
```bash
curl "http://localhost:8000/api/v1/votes?politician_id=1&session_date_from=2024-01-01&session_date_to=2024-12-31"
```

### Despesas de passagens aéreas em março/2024
```bash
curl "http://localhost:8000/api/v1/expenses?expense_type=Passagens%20aéreas&year=2024&month=3"
```

### Top doadores de campanha em 2022
```bash
curl "http://localhost:8000/api/v1/campaigns/summary?election_year=2022"
```

---

## Cache

| Endpoint | Cache-Control |
|----------|---------------|
| `/politicians` | `max-age=3600` |
| `/propositions` | `max-age=3600` |
| `/votes` | `no-cache` |
| `/expenses` | `max-age=3600` |
| `/campaigns` | `max-age=3600` |
| `/ingestion/*` | `no-cache` |

---

## Rate Limiting

**Nenhum** (decisão ADR-004). Headers `X-RateLimit-*` reservados para futuro.

---

## Versionamento

- **URL:** `/api/v1/`
- **Depreciação:** header `Sunset: <data>` 6 meses antes da remoção
- **Política:** v1 estável; breaking changes vão para v2

---

## Documentação Interativa

- **Swagger UI:** `http://localhost:8000/docs`
- **ReDoc:** `http://localhost:8000/redoc`
- **OpenAPI JSON:** `http://localhost:8000/openapi.json`