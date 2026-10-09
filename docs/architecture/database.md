# Database Architecture - Meu Candidato

## Visão Geral

PostgreSQL 16 + SQLAlchemy 2.0 (async) + Alembic para migrações.

## Configuração

```python
# app/core/database.py
engine = create_async_engine(
    settings.database_url,
    echo=settings.environment == "development",
    pool_pre_ping=True,
    pool_size=10,
    max_overflow=20,
)

async_session_maker = async_sessionmaker(
    engine,
    class_=AsyncSession,
    expire_on_commit=False,
)
```

- **Pool:** 10 conexões base + 20 overflow
- **Pre-ping:** Valida conexões antes de usar
- **Expire on commit:** False para evitar refresh desnecessário

## Modelos

### Tabela: politicians

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, Auto | ID interno |
| external_id | Integer | Unique, Index | ID da Câmara/Senado |
| name | String(255) | Not Null, Index | Nome civil |
| party | String(100) | Not Null, Index | Partido atual |
| uf | String(2) | Not Null, Index | UF |
| number | Integer | Not Null | Número eleitoral |
| cpf | String(14) | Unique, Index | CPF |
| email | String(255) | | Email oficial |
| office_address | Text | | Endereço do gabinete |
| office_phone | String(50) | | Telefone do gabinete |
| biography | Text | | Biografia |
| social_media | Text | | JSON com redes sociais |
| education | String(255) | | Formação |
| photo_url | String(500) | | URL da foto |
| created_at | Date | Default: today | Criação |
| updated_at | Date | Default: today, onupdate | Atualização |

**Índices compostos:**
- `ix_politicians_party_uf` (party, uf)
- `ix_politicians_name_search` (name)

---

### Tabela: mandates

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, Auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE | Referência |
| house | String(50) | Not Null | camara / senado |
| role | String(100) | Not Null | deputado / senador |
| uf | String(2) | Not Null | UF |
| start_date | Date | Not Null | Início do mandato |
| end_date | Date | | Fim do mandato (NULL = atual) |
| is_suplente | Boolean | Default: False | Se suplente |

**Relacionamento:** `Politician.mandates` (1:N, cascade delete)

---

### Tabela: propositions

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, Auto | ID interno |
| external_id | String(100) | Unique, Index | `{siglaTipo}-{numero}-{ano}` |
| politician_id | Integer | FK→politicians.id, CASCADE | Autor |
| type | String(50) | Not Null | PL, PDC, REQ, PEC, etc. |
| title | Text | Not Null | Ementa |
| summary | Text | | Resumo |
| status | String(50) | | Status tramitação |
| presentation_date | Date | Not Null | Data apresentação |
| house | String(50) | Not Null | camara / senado |
| url | String(500) | | Link inteiro teor |

**Índices:**
- `ix_propositions_politician_date` (politician_id, presentation_date)
- `ix_propositions_type_status` (type, status)
- `ix_propositions_external_id` (external_id) - Unique

**Relacionamentos:**
- `Politician.propositions` (1:N)
- `Proposition.votes` (1:N)

---

### Tabela: votes

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, Auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE | Votante |
| proposition_id | Integer | FK→propositions.id, CASCADE | Proposição |
| session_date | Date | Not Null, Index | Data da sessão |
| vote_value | String(20) | Not Null | favor, contra, abstenção, ausente, obstrucao, art17, desconhecido |
| session_number | String(50) | | Número da sessão |

**Índices:**
- `ix_votes_politician_date` (politician_id, session_date)
- `ix_votes_proposition` (proposition_id)

**Unique Constraint:** `(politician_id, proposition_id)` - um voto por deputado/proposição

**Relacionamentos:**
- `Politician.votes` (1:N)
- `Proposition.votes` (1:N)

---

### Tabela: expenses

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, Auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE | Deputado |
| expense_type | String(100) | Not Null, Index | Tipo da despesa |
| description | Text | | Descrição |
| amount | Integer | Not Null | Valor em centavos |
| expense_date | Date | Not Null, Index | Data do documento |
| provider | String(255) | | Fornecedor |
| document_number | String(100) | | Número do documento |
| document_url | String(500) | | URL do documento |
| year | Integer | Not Null, Index | Ano |
| month | Integer | Not Null | Mês (1-12) |

**Índices:**
- `ix_expenses_politician_year_month` (politician_id, year, month)
- `ix_expenses_type_date` (expense_type, expense_date)

**Unique Constraint:** `(politician_id, expense_type, expense_date, document_number)`

**Relacionamento:** `Politician.expenses` (1:N)

---

### Tabela: campaign_finances

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, Auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE | Candidato |
| election_year | Integer | Not Null, Index | Ano da eleição |
| election_type | String(50) | Not Null | federal, estadual, municipal |
| donor_type | String(50) | Not Null | pessoa_fisica, pessoa_juridica, fundo_partidario, recursos_proprios |
| donor_name | String(255) | | Nome do doador |
| donor_cpf_cnpj | String(18) | | CPF/CNPJ do doador |
| amount | Integer | Not Null | Valor em centavos |
| donation_date | Date | Not Null | Data da doação |
| receipt_url | String(500) | | URL do recibo |

**Índices:**
- `ix_campaign_politician_year` (politician_id, election_year)
- `ix_campaign_donor_type` (donor_type)

**Relacionamento:** `Politician.campaign_finances` (1:N)

---

## Diagrama ER

```
┌─────────────┐       ┌─────────────┐       ┌────────────────┐
│ politicians │       │  mandates   │       │ propositions   │
├─────────────┤       ├─────────────┤       ├────────────────┤
│ id (PK)     │◄──────│ politician_id│       │ id (PK)        │
│ external_id │       │ house        │       │ external_id (UQ)│
│ name        │       │ role         │       │ politician_id  │
│ party       │       │ uf           │       │ type           │
│ uf          │       │ start_date   │       │ title          │
│ ...         │       │ end_date     │       │ ...            │
└──────┬──────┘       │ is_suplente  │       └───────┬────────┘
       │              └─────────────┘               │
       │ 1:N                                        │ 1:N
       ▼                                            ▼
┌─────────────┐       ┌─────────────┐       ┌────────────────┐
│   expenses  │       │   votes     │       │ campaign_finances│
├─────────────┤       ├─────────────┤       ├────────────────┤
│ politician_id│◄──────│ politician_id│       │ politician_id  │
│ expense_type │       │ proposition_id│       │ election_year  │
│ amount      │       │ session_date │       │ donor_type     │
│ expense_date│       │ vote_value   │       │ amount         │
│ year, month │       └──────┬──────┘       │ donation_date  │
└─────────────┘              │              └────────────────┘
                             │
                        proposition_id
                             │
                        ┌────┴────┐
                        │propositions│
                        └───────────┘
```

---

## Migrações (Alembic)

### Estrutura
```
alembic/
├── env.py              # Configuração async
├── script.py.mako      # Template
├── versions/
│   └── 001_initial.py  # Migration inicial
└── alembic.ini         # Config
```

### env.py - Pontos-chave

```python
# Windows: SelectorEventLoop para psycopg async
if sys.platform == "win32":
    asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())

async def run_async_migrations():
    connectable = async_engine_from_config(...)
    async with connectable.connect() as connection:
        await connection.run_sync(do_run_migrations)
```

### Comandos

```bash
# Criar migration
alembic revision --autogenerate -m "descricao"

# Aplicar
alembic upgrade head

# Reverter
alembic downgrade -1

# Histórico
alembic history

# Ver SQL sem aplicar
alembic upgrade head --sql
```

---

## Queries Comuns

### Políticos com mandatos atuais
```sql
SELECT p.*, m.house, m.role, m.uf, m.start_date
FROM politicians p
JOIN mandates m ON m.politician_id = p.id
WHERE m.end_date IS NULL
ORDER BY p.name;
```

### Votos de uma proposição com nomes
```sql
SELECT v.vote_value, v.session_date, p.name as politician_name, p.party, p.uf
FROM votes v
JOIN politicians p ON p.id = v.politician_id
WHERE v.proposition_id = 123
ORDER BY p.party, p.name;
```

### Total de despesas por deputado/ano
```sql
SELECT p.name, p.party, p.uf, e.year, SUM(e.amount) as total
FROM expenses e
JOIN politicians p ON p.id = e.politician_id
GROUP BY p.id, p.name, p.party, p.uf, e.year
ORDER BY total DESC;
```

### Top doadores de campanha
```sql
SELECT cf.donor_name, cf.donor_type, SUM(cf.amount) as total, COUNT(*) as count
FROM campaign_finances cf
GROUP BY cf.donor_name, cf.donor_type
ORDER BY total DESC
LIMIT 10;
```

---

## Performance

### Índices Recomendados (já criados)
- FKs indexadas automaticamente pelo PostgreSQL
- Compostos para queries de listagem paginada
- Únicos para upserts (ON CONFLICT)

### Connection Pool Tuning
```python
# Para cargas altas
pool_size=20
max_overflow=30

# Para serverless
pool_size=5
max_overflow=10
pool_recycle=300  # Reciclar conexões idle
```

### Paginação Eficiente
```python
# Keyset pagination (para datasets grandes)
query = query.where(Model.id > last_seen_id).limit(per_page)
# vs OFFSET (atual, OK para < 100k rows)
query = query.offset((page - 1) * per_page).limit(per_page)
```

---

## Backup e Restore

```bash
# Backup
pg_dump -h localhost -U meucandidato -d meucandidato > backup.sql

# Backup apenas dados (sem schema)
pg_dump -h localhost -U meucandidato -d meucandidato --data-only > data.sql

# Restore
psql -h localhost -U meucandidato -d meucandidato < backup.sql
```

---

## Monitoramento

### Queries Lentas
```sql
-- pg_stat_statements (habilitar no postgresql.conf)
SELECT query, calls, mean_exec_time, total_exec_time
FROM pg_stat_statements
ORDER BY mean_exec_time DESC
LIMIT 20;
```

### Tamanho das Tabelas
```sql
SELECT schemaname, tablename, pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename))
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```