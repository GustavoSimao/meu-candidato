# Deploy Plan: Meu Candidato — Vercel Frontend + Fly.io Backend

## Status

All code fixes are **committed and pushed** (commit `c70ba66`). The deployment failure was **operational only** — `DATABASE_URL` was never set as a Fly.io secret. **No more code edits needed.** This plan covers only deployment commands.

## Root Cause of Deployment Failure

| | |
|---|---|
| **Symptom** | Container starts, crashes immediately, health check fails |
| **Root cause** | `DATABASE_URL` secret NOT set on Fly.io app `meu-candidato` |
| **What happened** | User was on the Fly.io Postgres "Connect" tab. Clicked "Track Connection" (only links DB for monitoring). Did NOT click "Set secret and deploy" (which injects `DATABASE_URL` into the app) |
| **Result** | `get_database_url()` in `config.py` raises `RuntimeError("DATABASE_URL environment variable is required")` at container startup → uvicorn never starts |

---

## Step-by-Step Deployment (Execute in This Exact Order)

### Step 0: Open Terminal in Project Directory

All commands below are run from the project root: `C:\projetos\meu-candidato`

```powershell
cd C:\projetos\meu-candidato
```

### Step 1: Authenticate with Fly.io CLI

```powershell
flyctl auth login
```

- Opens browser → login → returns to terminal
- If browser doesn't open, run `flyctl auth login --browser` or use token: `flyctl auth token <seu-token>`

### Step 2: Get the PostgreSQL Connection String

You need the **pooled** URL (pgBouncer endpoint). Two ways to get it:

**Option A — Via CLI:**
```powershell
flyctl postgres connect --app meu-candidato-db
# Exit with \q
```

**Option B — Via Dashboard:**
1. Go to https://fly.io/dashboard
2. Click on your org "Personal"
3. Click "Managed Postgres"
4. Click on database "my-database"
5. Click tab "Connect"
6. Find "Connection URL" under "Connect using PgBouncer"
7. Click "Copy to clipboard"

The URL format is:
```
postgresql+psycopg://fly-user:SENHA@pgbouncer.ID.flypg.net/fly-db
```

**Important:** The `postgresql://` prefix must be changed to `postgresql+psycopg://` (psycopg3 driver).

### Step 3: Set Secrets on the API App (THE MISSING STEP)

Replace `SENHA` and the cluster ID with your actual values:

```powershell
flyctl secrets set `
  DATABASE_URL="postgresql+psycopg://fly-user:SENHA@pgbouncer.n83v7rgjzggr5gxk.flypg.net/fly-db" `
  ALLOWED_ORIGINS="https://meu-candidato-web.vercel.app" `
  ENVIRONMENT="production" `
  --app meu-candidato
```

Expected output:
```
Secrets set for app meu-candidato
```

This triggers an automatic restart of the app with the new secrets.

### Step 4: Deploy (or Redeploy) the API

```powershell
flyctl deploy --app meu-candidato
```

What happens:
1. Fly.io builds the Docker image from `Dockerfile`
2. CMD runs: `alembic upgrade head && uvicorn app.main:app --host 0.0.0.0 --port 8000`
3. Alembic creates tables in PostgreSQL
4. Uvicorn starts the FastAPI server
5. Health check at `/health` runs (with 120s grace period)

**Expected result:** App shows "Deployed" on the Fly.io dashboard.

### Step 5: Verify API is Working

```powershell
# Check health endpoint
curl https://meu-candidato.fly.dev/health
```

Expected response:
```json
{"status":"ok","environment":"production"}
```

If it fails:
```powershell
# Check logs (replace --app if app name differs)
flyctl logs --app meu-candidato
```

### Step 6: Deploy the Worker (Data Ingestion)

```powershell
# Create worker app (first time only)
flyctl launch --config fly.worker.toml --name meu-candidato-worker --now
```

If the app was already created:
```powershell
# Set secrets on worker
flyctl secrets set `
  DATABASE_URL="postgresql+psycopg://fly-user:SENHA@pgbouncer.n83v7rgjzggr5gxk.flypg.net/fly-db" `
  ENVIRONMENT="production" `
  --app meu-candidato-worker

# Deploy worker
flyctl deploy --config fly.worker.toml --app meu-candidato-worker
```

### Step 7: Run Ingestion (one-off)

```powershell
# Run all ingestion phases
flyctl ssh console --app meu-candidato-worker -C "python -m app.workers.ingestion"
```

Or run a single dataset:
```powershell
flyctl ssh console --app meu-candidato-worker -C "python -m app.workers.ingestion deputados"
```

### Step 8: Deploy Frontend to Vercel

**Option A — Vercel CLI:**
```powershell
cd web
vercel --prod
```

**Option B — GitHub Integration (recommended):**
1. Push code to GitHub (already done)
2. Go to https://vercel.com/dashboard
3. Click "New Project" → import `GustavoSimao/meu-candidato`
4. Set "Root Directory" to `web`
5. In "Environment Variables", add:
   - Key: `NEXT_PUBLIC_API_URL`
   - Value: `https://meu-candidato.fly.dev/api/v1`
   - Environment: Production, Preview, Development
6. Click "Deploy"

### Step 9: Verify End-to-End

1. Open `https://<seu-app>.vercel.app` in browser
2. Should see the "Meu Candidato" homepage
3. Click "Políticos" → should list politicians (may be empty until ingestion runs)
4. Click a politician → should show detail page with tabs
5. Check browser DevTools → Network tab → API requests return 200

---

## Environment Variables Reference

### Fly.io API App Secrets
| Secret | Value |
|--------|-------|
| `DATABASE_URL` | `postgresql+psycopg://fly-user:pass@pgbouncer.<cluster>.flyphg.net/fly-db` |
| `ALLOWED_ORIGINS` | `https://<seu-app>.vercel.app` |
| `ENVIRONMENT` | `production` |

### Vercel Environment Variable
| Variable | Value |
|----------|-------|
| `NEXT_PUBLIC_API_URL` | `https://meu-candidato.fly.dev/api/v1` |

---

## Troubleshooting

### "FLY: error: no access token available"
```powershell
flyctl auth login
```

### "container: signal: killed" (OOM)
- Fly.io free tier has limited memory (256MB)
- Upgrade to `shared-cpu-2x` or `shared-cpu-4x` in fly.toml

### Health check fails after deploy
```powershell
flyctl logs --app meu-candidato
# Look for "DATABASE_URL" errors or migration errors
```

### CORS error in browser
- Verify `ALLOWED_ORIGINS` includes exact Vercel domain
```powershell
flyctl secrets list --app meu-candidato
```

### Alembic migration error
```powershell
# Check migration status
flyctl ssh console --app meu-candidato -C "alembic current"

# Force re-run migrations
flyctl ssh console --app meu-candidato -C "alembic upgrade head"
```

### 502 Bad Gateway on Vercel
- Frontend deployed but can't reach API
- Check `NEXT_PUBLIC_API_URL` is set in Vercel env vars
- Check API health: `curl https://meu-candidato.fly.dev/health`

---

## Key Files Modified/Affected

| File | Status | In commit |
|------|--------|-----------|
| `app/main.py` | CORS middleware added | ✅ `2e2b9cc` |
| `app/shared/kernel/database.py` | init_db → alembic migrations | ✅ `2e2b9cc` |
| `app/shared/kernel/config.py` | allowed_origins setting | ✅ `2e2b9cc` |
| `Dockerfile` | CMD: alembic upgrade head + uvicorn | ✅ `2e2b9cc` |
| `Dockerfile.worker` | Worker image with sleep infinity | ✅ `c70ba66` |
| `fly.toml` | grace_period = 120s | ✅ `2e2b9cc` |
| `fly.worker.toml` | Worker process config | ✅ `c70ba66` |
| `web/next.config.ts` | output: standalone, removed rewrites | ✅ `2e2b9cc` |
| `web/fly.toml` | port 8000 | ✅ `2e2b9cc` |
| `web/.vercelignore` | File exclusions | ✅ `2e2b9cc` |
| `.env.example` | ALLOWED_ORIGINS | ✅ `2e2b9cc` |
| `DEPLOY.md` | Full deploy guide | ✅ `c70ba66` |
