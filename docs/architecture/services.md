# Services Layer - Meu Candidato

## Visão Geral

A camada de services encapsula a lógica de negócio e acesso a dados, separando-a dos controllers (API routes). Cada service recebe uma `AsyncSession` do SQLAlchemy e expõe métodos assíncronos.

## Padrão de Service

```python
class EntityService:
    def __init__(self, session: AsyncSession):
        self.session = session

    async def list(self, filters...) -> EntityList:
        # 1. Build query with filters
        # 2. Count total
        # 3. Apply pagination + ordering
        # 4. Execute + map to schemas
        # 5. Return paginated response

    async def get(self, id: int) -> EntityDetail:
        # 1. Query with relationships loaded
        # 2. Raise if not found
        # 3. Map to detail schema

    async def get_stats(self, filters...) -> dict:
        # Aggregated statistics
```

---

## PoliticianService

**Arquivo:** `app/politician/application/services.py`

### Métodos

#### `list(page, per_page, uf, party) -> PoliticianList`
- Filtros: `uf` (exato), `party` (ILIKE parcial)
- Ordenação: `name ASC`
- Eager loads: `mandates`

#### `get(politician_id) -> PoliticianDetail`
- Eager loads: `mandates`
- Levanta `ValueError` se não encontrado

---

## VoteService

**Arquivo:** `app/legislative_activity/application/services.py`

### Métodos

#### `list(page, per_page, politician_id, proposition_id, vote_value, session_date_from, session_date_to) -> VoteList`
- Filtros combináveis
- Ordenação: `session_date DESC`
- Eager loads: `politician`, `proposition`

#### `get(vote_id) -> VoteDetail`
- Eager loads: `politician`, `proposition`
- Inclui nomes relacionados (`politician_name`, `proposition_title`)

#### `get_stats(politician_id) -> dict`
Retorna contagem por `vote_value`:
```python
{
    "favor": int,
    "contra": int,
    "abstencao": int,
    "ausente": int,
    "obstrucao": int,
    "art17": int,
    "desconhecido": int
}
```

---

## PropositionService

**Arquivo:** `app/legislative_activity/application/services.py`

### Métodos

#### `list(page, per_page, politician_id, type, status, house, presentation_date_from, presentation_date_to) -> PropositionList`
- Filtros combináveis
- Ordenação: `presentation_date DESC`
- Eager loads: `politician`, `votes`

#### `get(proposition_id) -> PropositionDetail`
- Eager loads: `politician`, `votes`
- Calcula agregações de votos:
  - `votes_count`: total
  - `votes_favor`: count onde `vote_value == "favor"`
  - `votes_contra`: count onde `vote_value == "contra"`
  - `votes_abstencao`: count onde `vote_value == "abstencao"`

---

## ExpenseService

**Arquivo:** `app/financial/application/services.py`

### Métodos

#### `list(page, per_page, politician_id, expense_type, year, month, expense_date_from, expense_date_to) -> ExpenseList`
- Filtros combináveis
- Ordenação: `expense_date DESC`
- Eager loads: `politician`

#### `get(expense_id) -> ExpenseDetail`
- Eager loads: `politician`
- Inclui `politician_name`

#### `get_summary(politician_id, year) -> ExpenseSummary`
Agregações em Python (não SQL) para flexibilidade:
- `total_amount`: soma de `amount` (centavos)
- `total_count`: contagem
- `by_type`: dict `{type: {"amount": int, "count": int}}` ordenado por amount DESC
- `by_month`: dict `{"YYYY-MM": {"amount": int, "count": int, "year": int, "month": int}}` ordenado cronologicamente

---

## CampaignFinanceService

**Arquivo:** `app/financial/application/services.py`

### Métodos

#### `list(page, per_page, politician_id, election_year, election_type, donor_type) -> CampaignFinanceList`
- Filtros combináveis
- Ordenação: `donation_date DESC`
- Eager loads: `politician`

#### `get(finance_id) -> CampaignFinanceDetail`
- Eager loads: `politician`
- Inclui `politician_name`

#### `get_summary(politician_id, election_year) -> CampaignFinanceSummary`
Agregações em Python:
- `total_amount`: soma de `amount` (centavos)
- `total_count`: contagem
- `by_donor_type`: dict `{donor_type: {"amount": int, "count": int}}` ordenado por amount DESC
- `by_election_year`: dict `{year: {"amount": int, "count": int}}` ordenado por ano
- `top_donors`: top 10 doadores por valor total, formato:
  ```python
  {"donor_name": str, "amount": int, "count": int, "donor_type": str}
  ```

---

## Convenções

### Retorno de Erros
- Exceções de domínio customizadas (`NotFoundError`, `ValidationError`, `ConflictError`, `BusinessRuleError`) - capturadas pelo FastAPI e retornam status apropriado (404, 422, 409, 400)
- Exceções de BD propagam para error handler global (500)

### Paginação
```python
offset = (page - 1) * per_page
pages = (total + per_page - 1) // per_page if total else 0
```

### Mapeamento Model → Schema
```python
items = [
    SchemaListItem(
        id=m.id,
        field1=m.field1,
        # ...
    )
    for m in models
]
```

### Filtros Opcionais
```python
if filter_param:
    query = query.where(Model.column == filter_param)
# ou para strings:
query = query.where(Model.column.ilike(f"%{filter_param}%"))
```

### Eager Loading
```python
query = select(Model).options(
    selectinload(Model.relationship1),
    selectinload(Model.relationship2),
)
```

---

## Testes

```python
@pytest.mark.asyncio
async def test_politician_service_list(db_session):
    service = PoliticianService(db_session)
    result = await service.list(page=1, per_page=10)
    assert isinstance(result, PoliticianList)
    assert result.page == 1
    assert result.per_page == 10
```