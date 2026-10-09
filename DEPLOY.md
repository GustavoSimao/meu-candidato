# Deploy Guide: Vercel + Fly.io

## Architecture
- **Frontend**: Vercel (Next.js, global CDN)
- **Backend API**: Fly.io (FastAPI, São Paulo region)
- **Worker**: Fly.io (data ingestion, scheduled daily at 03:00 via GitHub Actions)
- **Database**: Fly.io PostgreSQL

## Prerequisites
- [Fly.io CLI](https://fly.io/docs/hands-on/install-flyctl/) installed
- [Vercel CLI](https://vercel.com/docs/cli) installed (or GitHub integration)
- `flyctl auth login`
- `vercel login`

## Step 1: Create Fly.io PostgreSQL Database

```bash
flyctl postgres create \
  --name meu-candidato-db \
  --region gru \
  --volume-size 5 \
  --initial-cluster-size 1
```

Note the connection string from the output (or use `flyctl postgres connect`).

## Step 2: Deploy Backend API

```bash
# Set secrets (replace <DB_URL> with the connection string from Step 1)
flyctl secrets set DATABASE_URL=<DB_URL>
flyctl secrets set ALLOWED_ORIGINS=https://meu-candidato-web.vercel.app
flyctl secrets set ENVIRONMENT=production

# Deploy
flyctl deploy
```

The Dockerfile CMD runs `alembic upgrade head && uvicorn app.main:app ...`.
Migrations also run on app startup via `init_db()` in the lifespan.
The `grace_period = "120s"` in fly.toml gives time for migrations to complete.

## Step 3: Deploy Worker (Data Ingestion)

```bash
# Launch worker app (uses Dockerfile.worker)
flyctl launch --config fly.worker.toml --name meu-candidato-worker --now

# Set the same secrets on the worker app
flyctl secrets set DATABASE_URL=<DB_URL> --app meu-candidato-worker
flyctl secrets set ENVIRONMENT=production --app meu-candidato-worker
```

The worker runs ingestion on startup, then keeps the container alive with `sleep infinity`.

### Scheduling Ingestion (GitHub Actions cron)

Create `.github/workflows/ingestion.yml`:
```yaml
name: Ingestion Worker
on:
  schedule:
    - cron: "0 3 * * *"  # Daily at 03:00 UTC
  workflow_dispatch:
jobs:
  ingest:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: superfly/flyctl-actions@v1
        with:
          args: "ssh console --app meu-candidato-worker -C 'python -m app.workers.ingestion'"
        env:
          FLY_API_TOKEN: ${{ secrets.FLY_API_TOKEN }}
```

Alternatively, run ingestion manually:
```bash
flyctl ssh console --app meu-candidato-worker -C "python -m app.workers.ingestion deputados"
```

## Step 4: Deploy Frontend to Vercel

Option A — Vercel CLI:
```bash
cd web
vercel --prod
```

Set environment variable: `NEXT_PUBLIC_API_URL=https://meu-candidato.fly.dev/api/v1`

Option B — Vercel Dashboard (GitHub integration):
1. Push code to GitHub
2. Create new project on Vercel
3. Set root directory to `web`
4. Set environment variable: `NEXT_PUBLIC_API_URL=https://meu-candidato.fly.dev/api/v1`
5. Deploy

## Step 5: Verify

```bash
# API health
curl https://meu-candidato.fly.dev/health
# Expected: {"status":"ok","environment":"production"}

# API data (may be empty until ingestion runs)
curl https://meu-candidato.fly.dev/api/v1/politicians
```

## Troubleshooting

### Deployment Failed: "DATABASE_URL is required"
- The PostgreSQL cluster was attached in "tracking only" mode → secret NOT injected
- Fix: `flyctl secrets set DATABASE_URL=<connection-string>` then `flyctl deploy`

### CORS Error on Frontend
- `ALLOWED_ORIGINS` secret not set or doesn't include your Vercel domain
- Fix: `flyctl secrets set ALLOWED_ORIGINS=https://your-app.vercel.app --app meu-candidato`

### Alembic Migration Failed
- Check: `flyctl logs --app meu-candidato` for migration errors
- Manual retry: `flyctl ssh console --app meu-candidato -C "alembic upgrade head"`

## Environment Variables Summary

### Fly.io API (`flyctl secrets set`)
| Variable | Value |
|---|---|
| `DATABASE_URL` | `postgresql+psycopg://fly-user:pass@pgbouncer.<cluster>.flyphg.net/fly-db` |
| `ALLOWED_ORIGINS` | `https://meu-candidato-web.vercel.app` |
| `ENVIRONMENT` | `production` |

### Vercel Frontend (Project Settings → Env Vars)
| Variable | Value |
|---|---|
| `NEXT_PUBLIC_API_URL` | `https://meu-candidato.fly.dev/api/v1` |

## Rollback
- **Vercel**: Dashboard → Previous Deployments → Promote
- **Fly.io API**: `flyctl deploy --image <previous-image>`
- **Database**: Alembic migrations in `alembic/versions/` have `downgrade()` support
