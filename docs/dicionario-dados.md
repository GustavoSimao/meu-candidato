# Dicionário de Dados - Meu Candidato

> Decidi criar este dicionário porque a documentação de ingestão referencia um `docs/dicionario-dados.md` que não existia. Este é o documento único de referência para o modelo de dados.

## Convenções

- **Valores monetários**: armazenados como inteiros em centavos (ex.: R$ 1.234,56 → `123456`)
- **Datas**: armazenadas como `DATE` (sem timezone)
- **Timestamps**: armazenados em UTC
- **IDs externos**: cada fonte tem seu próprio ID, armazenado em `external_id` ou tabela de mapeamento
- **Linguagem dos erros**: inglês (logs e mensagens de erro)
- **Linguagem da documentação**: português (PT-BR)

---

## Tabelas

### politicians

Identidade do político. Uma linha por CPF (unificação por CPF exato).

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno (surrogate) |
| external_id | Integer | Unique, Index | ID da Câmara/Senado (quando disponível) |
| name | String(255) | Not Null, Index | Nome civil |
| party | String(100) | Not Null, Index | Partido atual (sigla) |
| uf | String(2) | Not Null, Index | UF |
| number | Integer | Not Null | Número eleitoral |
| cpf | String(14) | Unique, Index | CPF formatado (`XXX.XXX.XXX-XX`) |
| email | String(255) | | Email oficial |
| office_address | Text | | Endereço do gabinete |
| office_phone | String(50) | | Telefone do gabinete |
| biography | Text | | Biografia |
| social_media | Text | | JSON com redes sociais |
| education | String(255) | | Formação |
| photo_url | String(500) | | URL da foto oficial |
| created_at | Date | Default: CURRENT_DATE | Criação |
| updated_at | Date | Default: CURRENT_DATE, onupdate | Atualização |

**Índices:**
- `ix_politicians_party_uf` (party, uf)
- `ix_politicians_name_search` (name)

**Chave natural:** `cpf` (quando disponível)

---

### mandates

Mandatos políticos. Um político pode ter múltiplos mandatos (câmara, senado, suplente).

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE, Not Null | Referência ao político |
| house | String(50) | Not Null | `camara` ou `senado` |
| role | String(100) | Not Null | `deputado federal`, `senador`, etc. |
| uf | String(2) | Not Null | UF do mandato |
| start_date | Date | Not Null | Início do mandato |
| end_date | Date | | Fim do mandato (NULL = atual) |
| is_suplente | Boolean | Default: false | Se é suplente |

**Relacionamento:** `Politician.mandates` (1:N, cascade delete)

---

### propositions

Proposições legislativas (PL, PEC, PDC, REQ, etc.).

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| external_id | String(100) | Unique, Index | `{siglaTipo}-{numero}-{ano}` (ex.: `PL-1234-2024`) |
| politician_id | Integer | FK→politicians.id, CASCADE, Not Null | Autor |
| type | String(50) | Not Null | Tipo (PL, PEC, PDC, REQ, etc.) |
| title | Text | Not Null | Ementa |
| summary | Text | | Resumo |
| status | String(50) | | Status da tramitação |
| presentation_date | Date | Not Null | Data de apresentação |
| house | String(50) | Not Null | `camara` ou `senado` |
| url | String(500) | | Link para inteiro teor |

**Índices:**
- `ix_propositions_politician_date` (politician_id, presentation_date)
- `ix_propositions_type_status` (type, status)

**Chave natural:** `external_id`

---

### votes

Votos individuais em votações. Um voto por político/proposição.

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE, Not Null | Votante |
| proposition_id | Integer | FK→propositions.id, CASCADE, Not Null | Proposição |
| session_date | Date | Not Null, Index | Data da sessão |
| vote_value | String(20) | Not Null | Valor do voto (ver enum abaixo) |
| session_number | String(50) | | Número da sessão |

**Índices:**
- `ix_votes_politician_date` (politician_id, session_date)
- `ix_votes_proposition` (proposition_id)

**Unique Constraint:** `(politician_id, proposition_id)` - um voto por deputado/proposição

**Enum `vote_value`:**
| Valor | Descrição |
|-------|-----------|
| favor | Voto favorável (Sim) |
| contra | Voto contrário (Não) |
| abstencao | Abstenção |
| ausente | Ausente à votação |
| obstrucao | Obstrução |
| art17 | Art. 17 (voto de liderança) |
| desconhecido | Valor não mapeado |

---

### expenses

Despesas da cota parlamentar (Câmara).

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE, Not Null | Deputado |
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

---

### campaign_finances

Financiamento de campanha (TSE).

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE, Not Null | Candidato |
| election_year | Integer | Not Null, Index | Ano da eleição |
| election_type | String(50) | Not Null | Tipo da eleição (ver enum) |
| donor_type | String(50) | Not Null | Tipo do doador (ver enum) |
| donor_name | String(255) | | Nome do doador |
| donor_cpf_cnpj | String(18) | | CPF/CNPJ do doador |
| amount | Integer | Not Null | Valor em centavos |
| donation_date | Date | Not Null | Data da doação |
| receipt_url | String(500) | | URL do recibo eleitoral |

**Índices:**
- `ix_campaign_politician_year` (politician_id, election_year)
- `ix_campaign_donor_type` (donor_type)

**Enum `election_type`:**
| Valor | Descrição |
|-------|-----------|
| federal | Presidente, Senador, Deputado Federal |
| estadual | Governador, Vice-Governador, Deputado Estadual/Distrital |
| municipal | Prefeito, Vice-Prefeito, Vereador |

**Enum `donor_type`:**
| Valor | Descrição |
|-------|-----------|
| pessoa_fisica | Recursos de pessoas físicas |
| pessoa_juridica | Recursos de pessoas jurídicas |
| fundo_partidario | Fundo Partidário |
| recursos_proprios | Recursos próprios |
| outros | Outras fontes |

---

### follows

Acompanhamento de políticos por usuários.

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| user_id | String(100) | Not Null, Index | UUID anônimo do cliente |
| politician_id | Integer | FK→politicians.id, CASCADE, Not Null | Político acompanhado |
| created_at | DateTime | Not Null, Default: now | Criação |

**Índices:**
- `ix_follows_user_politician` (user_id, politician_id) - Unique
- `ix_follows_user_created` (user_id, created_at)

---

### badges

Selos de conquistas de políticos.

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| politician_id | Integer | FK→politicians.id, CASCADE, Not Null | Político |
| badge_type | String(50) | Not Null | Tipo do selo |
| earned_at | Date | Not Null, Default: CURRENT_DATE | Data de conquista |
| metadata | JSONB | Default: {} | Metadados do selo |

**Índice:** `ix_badges_politician_type` (politician_id, badge_type)

---

### badge_rules

Regras para conquista de selos.

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| badge_type | String(50) | Not Null, Unique | Tipo do selo |
| name | String(100) | Not Null | Nome do selo |
| description | Text | | Descrição |
| condition | Text | Not Null | Condição (expressão) |
| threshold | Integer | Not Null | Limite |
| is_active | Integer | Default: 1 | Se ativo (0/1) |

---

### ingestion_jobs

Rastreamento de jobs de ingestão.

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| data_source | String(50) | Not Null | `camara`, `senado`, `tse` |
| dataset | String(50) | Not Null | `deputados`, `mandatos`, `proposicoes`, etc. |
| status | String(20) | Not Null, Default: pending | `pending`, `running`, `completed`, `failed`, `partial` |
| started_at | DateTime | | Início |
| completed_at | DateTime | | Término |
| records_processed | Integer | Default: 0 | Total processado |
| records_inserted | Integer | Default: 0 | Inseridos |
| records_updated | Integer | Default: 0 | Atualizados |
| records_failed | Integer | Default: 0 | Falharam |
| error_message | Text | | Mensagem de erro |
| metadata | JSONB | Default: {} | Metadados (checkpoint, heartbeat) |
| created_at | DateTime | Not Null, Default: now | Criação |

**Índices:**
- `ix_ingestion_jobs_source_dataset` (data_source, dataset)
- `ix_ingestion_jobs_status` (status)
- `ix_ingestion_jobs_created` (created_at)

**Partial Unique Index (planejado):** `(data_source, dataset) WHERE status='pending'` - evita jobs duplicados pendentes

---

### quarantine_records

Registros em quarentena (falharam validação).

| Coluna | Tipo | Constraints | Descrição |
|--------|------|-------------|-----------|
| id | Integer | PK, auto | ID interno |
| data_source | String(50) | Not Null | Fonte |
| dataset | String(50) | Not Null | Dataset |
| raw_data | JSONB | Not Null | Dados brutos |
| errors | JSONB | Not Null | Lista de erros |
| received_at | DateTime | Not Null, Default: now | Recebimento |
| resolved | Integer | Default: 0 | Resolvido (0/1) |

**Índices:**
- `ix_quarantine_source_dataset` (data_source, dataset)
- `ix_quarantine_received` (received_at)

---

## Mapeamento de Fontes

### Câmara → politicians

| Campo Câmara | Campo Modelo | Notas |
|--------------|--------------|-------|
| `id` | `external_id` | ID da Câmara |
| `nome` | `name` | Nome civil |
| `siglaPartido` | `party` | Sigla do partido |
| `siglaUf` | `uf` | UF |
| `cpf` | `cpf` | CPF (quando disponível) |
| `urlFoto` | `photo_url` | URL da foto |
| `email` | `email` | Email |

### Câmara → mandates

| Campo Câmara | Campo Modelo | Notas |
|--------------|--------------|-------|
| `id` (deputado) | `politician_id` (via lookup) | FK |
| — | `house` | Sempre `camara` |
| `nome` (cargo) | `role` | `deputado federal` |
| `siglaUf` | `uf` | UF |
| `dataInicio` | `start_date` | Início |
| `dataFim` | `end_date` | Fim (NULL = atual) |
| — | `is_suplente` | Vem do endpoint de mandatos |

### Câmara → propositions

| Campo Câmara | Campo Modelo | Notas |
|--------------|--------------|-------|
| `siglaTipo`-`numero`-`ano` | `external_id` | Formato `PL-1234-2024` |
| `idDeputadoAutor` | `politician_id` (via lookup) | FK |
| `siglaTipo` | `type` | Tipo |
| `ementa` | `title` | Ementa |
| `status` | `status` | Status tramitação |
| `dataApresentacao` | `presentation_date` | Data |
| — | `house` | Sempre `camara` |
| `urlInteiroTeor` | `url` | Link |

### Câmara → votes

| Campo Câmara | Campo Modelo | Notas |
|--------------|--------------|-------|
| `idDeputado` | `politician_id` (via lookup) | FK |
| `idProposicao` | `proposition_id` (via lookup) | FK |
| `dataSessao` | `session_date` | Data |
| `voto` | `vote_value` | Mapeado (ver enum) |
| `numeroSessao` | `session_number` | Número |

**Mapeamento de votos:**
| Voto Câmara | vote_value |
|-------------|------------|
| Sim | favor |
| Não | contra |
| Abstenção | abstencao |
| Ausente | ausente |
| Obstrução | obstrucao |
| Art. 17 | art17 |

### Câmara → expenses

| Campo Câmara | Campo Modelo | Notas |
|--------------|--------------|-------|
| `idDeputado` | `politician_id` (via lookup) | FK |
| `tipoDespesa` | `expense_type` | Tipo |
| `descricao` | `description` | Descrição |
| `valorDocumento` | `amount` | Converter para centavos |
| `dataDocumento` | `expense_date` | Data |
| `nomeFornecedor` | `provider` | Fornecedor |
| `numeroDocumento` | `document_number` | Número |
| `urlDocumento` | `document_url` | URL |
| — | `year`, `month` | Extraídos de `expense_date` |

### Senado → politicians

| Campo Senado | Campo Modelo | Notas |
|--------------|--------------|-------|
| `CodigoParlamentar` | `external_id` | ID do Senado |
| `NomeParlamentar` | `name` | Nome civil |
| `Partido` | `party` | Sigla |
| `Uf` | `uf` | UF |
| `NumeroEleitoral` | `number` | Número |
| `Email` | `email` | Email |
| `UrlFoto` | `photo_url` | URL da foto |

### Senado → mandates

| Campo Senado | Campo Modelo | Notas |
|--------------|--------------|-------|
| `CodigoParlamentar` | `politician_id` (via lookup) | FK |
| — | `house` | Sempre `senado` |
| — | `role` | Sempre `senador` |
| `Uf` | `uf` | UF |
| `DataInicio` | `start_date` | Início |
| `DataFim` | `end_date` | Fim (NULL = atual) |
| `Suplente` | `is_suplente` | Boolean |

### Senado → propositions

| Campo Senado | Campo Modelo | Notas |
|--------------|--------------|-------|
| `IdentificacaoProposicao` | `external_id` | Formato `PEC-12-2023` |
| `Autor.CodigoParlamentar` | `politician_id` (via lookup) | FK |
| `SiglaTipo` | `type` | Tipo |
| `Ementa` | `title` | Ementa |
| `Situacao` | `status` | Status |
| `DataApresentacao` | `presentation_date` | Data |
| — | `house` | Sempre `senado` |
| `UrlInteiroTeor` | `url` | Link |

### TSE → campaign_finances

| Campo TSE | Campo Modelo | Notas |
|-----------|--------------|-------|
| `NR_CPF_CANDIDATO` | `politician_id` (via lookup CPF) | FK |
| — | `election_year` | Extraído do nome do arquivo |
| `DS_CARGO` | `election_type` | Mapeado (ver enum) |
| `DS_FONTE_RECEITA` | `donor_type` | Mapeado (ver enum) |
| `NM_DOADOR` | `donor_name` | Nome do doador |
| `NR_CPF_CNPJ_DOADOR` | `donor_cpf_cnpj` | CPF/CNPJ |
| `VR_RECEITA` | `amount` | Converter para centavos (`1.234,56` → `123456`) |
| `DT_RECEITA` | `donation_date` | Data (`DD/MM/YYYY`) |
| `NR_RECIBO_ELEITORAL` | `receipt_url` | Construir URL TSE |

**Mapeamento de tipo de eleição (DS_CARGO):**
| DS_CARGO | election_type |
|----------|---------------|
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

**Mapeamento de tipo de doador (DS_FONTE_RECEITA):**
| DS_FONTE_RECEITA | donor_type |
|------------------|------------|
| Recursos de pessoas físicas | pessoa_fisica |
| Recursos de pessoas jurídicas | pessoa_juridica |
| Fundo Partidário | fundo_partidario |
| Recursos próprios | recursos_proprios |
| Recursos de outros candidatos/comitês | outros |
| Outras fontes | outros |

---

## Regras de Validação

### politicians (campos obrigatórios)
- `name`: não vazio, máx 255 caracteres
- `party`: não vazio, máx 100 caracteres
- `uf`: deve ser UF válida (ver value object `UF`)
- `number`: inteiro positivo
- `cpf`: CPF válido (ver value object `CPF`) quando presente

### mandates (campos obrigatórios)
- `politician_id`: FK válida
- `house`: `camara` ou `senado`
- `role`: não vazio
- `uf`: UF válida
- `start_date`: data válida
- `end_date`: NULL ou data ≥ `start_date`

### propositions (campos obrigatórios)
- `external_id`: formato `{TIPO}-{NUMERO}-{ANO}`
- `politician_id`: FK válida
- `type`: não vazio
- `title`: não vazio
- `presentation_date`: data válida
- `house`: `camara` ou `senado`

### votes (campos obrigatórios)
- `politician_id`: FK válida
- `proposition_id`: FK válida
- `session_date`: data válida
- `vote_value`: valor do enum

### expenses (campos obrigatórios)
- `politician_id`: FK válida
- `expense_type`: não vazio
- `amount`: inteiro ≥ 0
- `expense_date`: data válida
- `year`: inteiro, `month`: 1-12

### campaign_finances (campos obrigatórios)
- `politician_id`: FK válida
- `election_year`: inteiro (ex.: 2022)
- `election_type`: valor do enum
- `donor_type`: valor do enum
- `amount`: inteiro ≥ 0
- `donation_date`: data válida

---

## Relacionamentos

```
Politician 1 ─────< N Mandate
Politician 1 ─────< N Proposition
Politician 1 ─────< N Expense
Politician 1 ─────< N CampaignFinance
Politician 1 ─────< N Vote
Politician 1 ─────< N Follow
Politician 1 ─────< N Badge
Proposition 1 ─────< N Vote
```

---

## Versionamento

- **Snapshot diário**: tabelas críticas (`politicians`, `votes`, `expenses`) copiadas para `history.*_YYYYMMDD` via `CREATE TABLE AS`
- **Rollback**: `TRUNCATE + INSERT` a partir do snapshot anterior
- **Linha do tempo**: colunas `data_source` e `ingested_at` rastreiam a origem de cada registro
