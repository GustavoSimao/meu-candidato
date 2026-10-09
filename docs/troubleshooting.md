# Solução de Problemas - Meu Candidato

> Decidi criar este documento porque erros comuns não tinham um lugar centralizado.

## Erros Comuns

### Banco de dados

#### `connection refused` / `could not connect`

**Causa:** Banco não está rodando.

```bash
# Verificar se o container está up
docker compose ps

# Subir o banco
docker compose up -d db

# Ver logs
docker compose logs db
```

#### `password authentication failed`

**Causa:** Senha incorreta no `DATABASE_URL`.

```bash
# Verificar .env
cat .env | grep DATABASE_URL

# Verificar credenciais no docker-compose.yml
docker compose exec db psql -U meucandidato -d meucandidato -c "SELECT 1"
```

#### `relation "politicians" does not exist`

**Causa:** Migrações não aplicadas.

```bash
python scripts/migrate.py
# ou
alembic upgrade head
```

#### `asyncpg.exceptions.InterfaceError: cannot perform operation: another operation is in progress`

**Causa:** Múltiplas operações concorrentes na mesma conexão.

**Solução:** Use `async with` para sessões, não compartilhe conexões entre tasks.

---

### Ingestão

#### `Rate limit exceeded` (429)

**Causa:** API da Câmara/Senado rate limit.

**Solução:** O cliente já tem retry com backoff exponencial. Se persistir, reduza `RATE_LIMIT` no client.

#### `quarentena > 5%`

**Causa:** Muitos registros falharam validação.

**Diagnóstico:**
```bash
# Ver registros em quarentena
curl "http://localhost:8000/api/v1/ingestion/quarantine?data_source=camara"
```

**Solução:** Verifique os erros nos registros, ajuste os mapeamentos.

#### `job preso` (timeout)

**Causa:** Job rodando há mais de 30 min.

**Diagnóstico:**
```bash
# Ver jobs
curl "http://localhost:8000/api/v1/ingestion/jobs?status=running"
```

**Solução:** Verifique logs, cancele via API se necessário.

#### `politician_id NULL` em votes

**Causa:** Bug no linking `external_id` → `id` interno.

**Solução:** Verifique se o deputado foi ingerido antes dos votos. O fluxo é: deputados → proposições → votos.

---

### API

#### `404 Not Found`

**Causa:** Endpoint não existe ou recurso não encontrado.

```bash
# Verificar endpoints disponíveis
curl http://localhost:8000/openapi.json | jq '.paths | keys'
```

#### `422 Unprocessable Entity`

**Causa:** Dados de entrada inválidos.

**Solução:** Verifique o corpo da requisição contra o schema OpenAPI.

#### `500 Internal Server Error`

**Causa:** Erro não tratado no servidor.

**Diagnóstico:** Verifique os logs da API (stdout JSON).

---

### Docker

#### `port is already allocated`

**Causa:** Porta 8000 (ou 5432) em uso.

```bash
# Encontrar processo na porta
netstat -ano | findstr :8000
# Matar
taskkill /PID <pid> /F
```

#### `container is not running`

**Causa:** Container parou.

```bash
docker compose ps
docker compose logs <service>
docker compose up -d <service>
```

---

### Windows

#### `asyncio.WindowsProactorEventLoopPolicy` error

**Causa:** psycopg async não suporta Proactor no Windows.

**Solução:** O projeto já configura `WindowsSelectorEventLoopPolicy` em `pyproject.toml` e `alembic/env.py`. Se o erro persistir, verifique se está usando Python 3.11+.

#### `PermissionError` ao escrever em `data/`

**Causa:** Permissões de arquivo.

**Solução:** Rode como administrador ou ajuste permissões da pasta `data/`.

---

## FAQ

### Como rodar ingestão manualmente?

```bash
# Todos os datasets
python -m app.workers.ingestion

# Dataset específico
python -m app.workers.ingestion deputados
python -m app.workers.ingestion proposicoes
python -m app.workers.ingestion despesas
```

### Como ver o status da ingestão?

```bash
# Jobs recentes
curl "http://localhost:8000/api/v1/ingestion/jobs"

# Resumo
curl "http://localhost:8000/api/v1/ingestion/jobs/summary"

# Último job de um dataset
curl "http://localhost:8000/api/v1/ingestion/jobs/latest?data_source=camara&dataset=deputados"
```

### Como resolver registros em quarentena?

```bash
# Listar
curl "http://localhost:8000/api/v1/ingestion/quarantine"

# Marcar como resolvido
curl -X PATCH "http://localhost:8000/api/v1/ingestion/quarantine/1/resolve"
```

### Como fazer backup?

```bash
pg_dump -h localhost -U meucandidato -d meucandidato > backup_$(date +%Y%m%d).sql
```

### Como restaurar?

```bash
psql -h localhost -U meucandidato -d meucandidato < backup_20240101.sql
```

### Como resetar o banco?

```bash
# CUIDADO: apaga todos os dados
docker compose down -v
docker compose up -d db
python scripts/migrate.py
```

---

## Debug Avançado

### Ver SQL gerado

```python
# Ativar echo no engine (development)
# app/shared/kernel/database.py
engine = create_async_engine(settings.database_url, echo=True)
```

### Ver queries lentas

```sql
-- Habilitar pg_stat_statements no postgresql.conf
SELECT query, calls, mean_exec_time
FROM pg_stat_statements
ORDER BY mean_exec_time DESC
LIMIT 20;
```

### Ver tamanho das tabelas

```sql
SELECT schemaname, tablename, pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename))
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

### Ver conexões ativas

```sql
SELECT pid, usename, application_name, state, query_start, query
FROM pg_stat_activity
WHERE datname = 'meucandidato';
```

---

## Contato

Se o problema persistir:
1. Verifique os logs (JSON estruturado)
2. Abra uma issue com: erro, logs, passos para reproduzir
