# Guia de Deploy - Meu Candidato

> Decidi documentar o deploy porque o README só tem `docker-compose up`, sem o processo completo.

## Arquitetura de Deploy

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   GitHub        │────▶│  GitHub Actions │────▶│  Docker         │
│   (repo)        │     │  (CI/CD)        │     │  Registry       │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                          │
                                                          ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Server        │◀────│  docker pull    │◀────│  Image          │
│   (Docker       │     │  + deploy       │     │  (tagged)       │
│    Compose)     │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
        │
        ├── api (FastAPI, porta 8000)
        ├── worker (ingestão, cron)
        └── db (PostgreSQL, porta 5432)
```

---

## Pré-requisitos

- **Servidor** com Docker + Docker Compose
- **Registry** de Docker (GitHub Container Registry, Docker Hub, etc.)
- **Domínio** (opcional, para HTTPS)

---

## 1. Configuração do Servidor

### Instalar Docker

```bash
# Ubuntu/Debian
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
```

### Configurar `.env` de produção

```bash
# No servidor
cd /opt/meu-candidato
cp .env.example .env
```

**`.env` produção:**
```env
DATABASE_URL=postgresql+asyncpg://meucandidato:SENHA_FORTE@db:5432/meucandidato
ENVIRONMENT=production
API_HOST=0.0.0.0
API_PORT=8000
TZ=America/Sao_Paulo
INGESTION_SCHEDULE_CRON=0 3 * * *
INGESTION_BATCH_SIZE=100
INGESTION_JOB_TIMEOUT_SECONDS=1800
QUARANTINE_THRESHOLD_PCT=5
```

---

## 2. Docker Compose (Produção)

**`docker-compose.yml`:**
```yaml
services:
  db:
    image: postgres:16
    environment:
      POSTGRES_USER: meucandidato
      POSTGRES_PASSWORD: ${DB_PASSWORD}
      POSTGRES_DB: meucandidato
    volumes:
      - pgdata:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U meucandidato"]
      interval: 10s
      timeout: 5s
      retries: 5

  api:
    image: ghcr.io/${OWNER}/meu-candidato:${TAG}
    ports:
      - "8000:8000"
    depends_on:
      db:
        condition: service_healthy
    environment:
      - DATABASE_URL=postgresql+asyncpg://meucandidato:${DB_PASSWORD}@db:5432/meucandidato
      - ENVIRONMENT=production
    restart: unless-stopped

  worker:
    image: ghcr.io/${OWNER}/meu-candidato:${TAG}
    command: python -m app.workers.ingestion
    depends_on:
      db:
        condition: service_healthy
    environment:
      - DATABASE_URL=postgresql+asyncpg://meucandidato:${DB_PASSWORD}@db:5432/meucandidato
      - TZ=America/Sao_Paulo
    restart: unless-stopped
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: "1.0"

volumes:
  pgdata:
```

---

## 3. Pipeline CI/CD (GitHub Actions)

**`.github/workflows/ci.yml`:**
```yaml
name: CI
on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_USER: meucandidato
          POSTGRES_PASSWORD: senha
          POSTGRES_DB: meucandidato
        ports:
          - 5432:5432
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-python@v5
        with:
          python-version: "3.11"
      - run: pip install -e ".[dev]"
      - run: ruff check .
      - run: mypy app/
      - run: pytest
        env:
          DATABASE_URL: postgresql+asyncpg://meucandidato:senha@localhost:5432/meucandidato

  build:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4
      - uses: docker/setup-buildx-action@v3
      - uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      - uses: docker/build-push-action@v5
        with:
          context: .
          push: true
          tags: |
            ghcr.io/${{ github.repository_owner }}/meu-candidato:latest
            ghcr.io/${{ github.repository_owner }}/meu-candidato:${{ github.sha }}
```

---

## 4. Deploy

### Deploy manual

```bash
# No servidor
cd /opt/meu-candidato

# 1. Aplicar migrações (pre-deploy)
docker compose exec api alembic upgrade head

# 2. Puxar imagem nova
docker compose pull api
docker compose pull worker

# 3. Recriar containers
docker compose up -d api worker

# 4. Verificar
docker compose ps
curl http://localhost:8000/health
```

### Deploy automático (opcional)

Adicione um job no GitHub Actions que SSH no servidor e roda os comandos acima.

---

## 5. Migrations

**Ordem:** migrações **antes** do novo código (pre-deploy).

```bash
# 1. Aplicar migrações
docker compose exec api alembic upgrade head

# 2. Deploy do novo código
docker compose up -d api worker
```

**Migrações breaking:** use expand-contract:
1. Adicione coluna nova (nullable)
2. Deploy código que escreve na nova
3. Migre dados
4. Deploy código que lê da nova
5. Remova coluna velha

---

## 6. Rollback

**Rollback = redeploy da imagem anterior.**

```bash
# Ver imagens disponíveis
docker images ghcr.io/owner/meu-candidato

# Rollback para tag anterior
docker compose up -d api worker --no-deps
# ou especificar tag
TAG=previous docker compose up -d
```

**Rollback de migração:**
```bash
docker compose exec api alembic downgrade -1
```

---

## 7. Backup

**Backup diário (cron):**

```bash
# /etc/cron.daily/meu-candidato-backup
#!/bin/bash
docker compose exec -T db pg_dump -U meucandidato meucandidato > /backups/meucandidato_$(date +%Y%m%d).sql
# Manter 30 dias
find /backups -name "meucandidato_*.sql" -mtime +30 -delete
```

**Restore:**
```bash
docker compose exec -T db psql -U meucandidato meucandidato < /backups/meucandidato_20240101.sql
```

---

## 8. Monitoramento

### Health checks

```bash
# Liveness
curl http://localhost:8000/health

# Readiness
curl http://localhost:8000/ready
```

### UptimeRobot

Configure monitor em `http://<server>:8000/health` (intervalo: 1 min).

### Logs

```bash
# API logs
docker compose logs -f api

# Worker logs
docker compose logs -f worker

# Todos
docker compose logs -f
```

Logs são JSON estruturado (stdout).

### Métricas (Prometheus)

```bash
curl http://localhost:8000/metrics
```

---

## 9. Escalonamento

### Vertical (mais recursos)

Ajuste `deploy.resources.limits` no `docker-compose.yml`.

### Horizontal (mais instâncias)

```yaml
api:
  # ...
  deploy:
    replicas: 2
```

**Nota:** worker deve permanecer singleton (evita jobs duplicados).

---

## 10. Segurança

- **`.env`** nunca no git
- **Senhas fortes** no `DATABASE_URL`
- **Firewall:** apenas portas 80/443 (e 22 SSH)
- **HTTPS:** reverse proxy (Nginx/Caddy) na frente
- **Atualizações:** `docker compose pull` regularmente

---

## Checklist de Deploy

- [ ] `.env` de produção configurado
- [ ] Migrações aplicadas (pre-deploy)
- [ ] Imagem nova puxada
- [ ] Containers recriados
- [ ] `/health` retorna 200
- [ ] `/ready` retorna 200
- [ ] Logs sem erros
- [ ] Backup funcionando
- [ ] Monitoramento configurado
