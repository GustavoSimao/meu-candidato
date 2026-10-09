# Deploy Plan: Meu Candidato — Vercel Frontend + Fly.io Backend

## Status

All code fixes are **applied**. Deployment failed because `DATABASE_URL` was never set as a Fly.io secret (user was on the DB connect screen but did not click "Set secret and deploy").

## Root Cause of Deployment Failure

| Symptom | App crashes on startup, health check fails |
|---------|-------------------------------------------|-------------|------------------------------------------------------------------------------|
| Cause | `DATABASE_URL` secret NOT injected into Fly.io app `meu-candidato` |
| Detail | PostgreSQL was attached via "Track Connection" only → no secret → `get_database_url()` raises `RuntimeError` at container startup → container crash-loop |
| Fix | Set `DATABASE_URL` secret via `flyctl secrets set`, then redeploy |

## Code Changes Already Applied (No More Code Edits Needed)

| File | Change |
|------|--------|
| `app/main.py` | Added `CORSMiddleware` (reads `ALLOWED_ORIGINS` from env) |
| `app/shared/kernel/config.py` | Added `allowed_origins: str = ""` setting |
| `app/shared/kernel/database.py` | `init_db()` runs `alembic upgrade head` (migrates on startup) |
| `Dockerfile` | CMD: `alembic upgrade head && uvicorn app.main:app ...` |
| `fly.toml` | Added `grace_period = "120s"` to health check |
| `web/next.config.ts` | Added `output: "standalone"`; removed redundant rewrites |
| `web/fly.toml` | Fixed port `8080` → `8000` |

## Files Created

| File | Purpose |
|------|---------|
| `DEPLOY.md` | Full deployment guide |
| `web/.vercelignore` | Excludes data/build artifacts from Vercel build |
| `Dockerfile.worker` | Worker image (CMD: `alembic upgrade head && python -m app.workers.ingestion`) |
| `fly.worker.toml` | Fly.io cron app for daily ingestion worker |

## Fix Fly.io Deployment — Exact Commands

### Step 1: Set Secrets on the API App

```bash
# Use the POOLED connection URL from Fly.io Postgres dashboard
# (pgbouncer endpoint, e.g. pgbouncer.n83v7rgjzggr5gxk.flympg.net)
flyctl secrets set \
  DATABASE_URL="postgresql+psycopg://fly-user:SEU_PASSWORD@pgbouncer.n83v7rgjzggr5gxk.flypg.net/fly-db" \
  ALLOWED_ORIGINS=https://seu-app.vercel.app \
  ENVIRONMENT=production \
  --app meu-candidato
```

### Step 2: Redeploy

```bash
flyctl deploy --app meu-candidato
```

The Dockerfile CMD runs: `alembic upgrade head && uvicorn app.main:app --host 0.0.0.0 --port 8000`

### Step 3: Verify

```bash
curl https://meu-candidato.fly.dev/health
# Expected: {"status":"ok","environment":"production"}

# Check logs if failing
flyctl logs --app meu-candidato
```

### Step 4: Deploy Worker (cron ingestion)

```bash
flyctl secrets set \
  DATABASE_URL="postgresql+psycopg://fly-user:SEU_PASSWORD@pgbouncer.n83v7rgjzggr5gxk.flypg.net/fly-db" \
  ENVIRONMENT=production \
  --app meu-candidato-worker

flyctl deploy --config fly.worker.toml --app meu-candidato-worker
```

### Step 5: Deploy Frontend to Vercel

```bash
cd web
# Set in Vercel dashboard → Settings → Environment Variables:
# NEXT_PUBLIC_API_URL = https://meu-candidato.fly.dev/api/v1
vercel --prod
```

## Environment Variables

### Fly.io (set via `flyctl secrets set`)
| Secret | Value |
|--------|-------|
| `DATABASE_URL` | `postgresql+psycopg://...@pgbouncer.<cluster-id>.flyphg.net/fly-db` |
| `ALLOWED_ORIGINS` | `https://meu-candidato-web.vercel.app` |
| `ENVIRONMENT` | `production` |

### Vercel (set in project env vars)
| Variable | Value |
|----------|-------|
| `NEXT_PUBLIC_API_URL` | `https://meu-candidato.fly.dev/api/v1` |

## Validation Checklist

- [ ] `curl https://meu-candidato.fly.dev/health` → `{"status":"ok","environment":"production"}`
- [ ] `curl https://meu-candidato.fly.dev/api/v1/politicians` → 200 response (may be empty until ingestion runs)
- [ ] Frontend loads on Vercel with `NEXT_PUBLIC_API_URL` configured
- [ ] Frontend fetches data from API (check browser Network tab for CORS headers)
- [ ] Worker runs: `flyctl ssh console --app meu-candidato-worker -C "python -m app.workers.ingestion deputados"`
- [ ] Tables exist: `flyctl pg connect --app meu-candidato-db -c "\dt"` shows 5+ tables

## Known Limitation

The `init_db()` in `database.py` runs `command.upgrade()` synchronously in the async lifespan. This blocks the event loop briefly during startup but is acceptable as a one-time migration. The Dockerfile CMD also runs migrations first, so lifespan migrations are a no-op if CMD succeeds.
