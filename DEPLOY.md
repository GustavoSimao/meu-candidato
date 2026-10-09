# Deploy Guide: Vercel + Fly.io

## Architecture
- **Frontend**: Vercel (Next.js, global CDN)
- **Backend API**: Fly.io (FastAPI, São Paulo region)
- **Worker**: Fly.io (data ingestion, scheduled daily at 03:00)
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
flyctl secrets set ALLOWED_ORIGINS=https://meu-candidato-web.vercel.app,https://www.seudominio.com.br
flyctl secrets set ENVIRONMENT=production

# Deploy
flyctl deploy
```

The Dockerfile runs `alembic upgrade head` before starting uvicorn.
Migrations are also run on app startup via `init_db()`.

## Step 3: Deploy Worker (Data Ingestion)

```bash
# Deploy worker app
flyctl deploy --config fly.worker.toml --image meu-candidato-worker:latest

# Or deploy as a new app:
flyctl launch --config fly.worker.toml --name meu-candidato-worker
```

Set the same secrets (DATABASE_URL, etc.) on the worker app.

## Step 4: Deploy Frontend to Vercel

Option A — Vercel CLI:
```bash
cd web
vercel --prod
```

Option B — Vercel Dashboard (GitHub integration):
1. Push code to GitHub
2. Create new project on Vercel
3. Set root directory to `web`
4. Set environment variable: `NEXT_PUBLIC_API_URL=https://<api-app-name>.fly.dev/api/v1`
5. Deploy

## Step 5: Verify

```bash
# API health
curl https://meu-candidato.fly.dev/health

# Worker (one-off)
flyctl ssh console -C "python -m app.workers.ingestion deputados"
```

## Environment Variables Summary

### Fly.io API (`flyctl secrets set`)
| Variable | Example |
|---|---|
| `DATABASE_URL` | `postgresql+psycopg://user:pass@host:5432/db` |
| `ALLOWED_ORIGINS` | `https://meu-candidato-web.vercel.app` |
| `ENVIRONMENT` | `production` |

### Vercel Frontend (Project Settings → Env Vars)
| Variable | Example |
|---|---|
| `NEXT_PUBLIC_API_URL` | `https://meu-candidato.fly.dev/api/v1` |

## Rollback

- **Vercel**: Dashboard → Previous Deployments → Promote
- **Fly.io API**: `flyctl deploy --image <previous-image>`
- **Database**: Alembic migrations in `alembic/versions/` have `downgrade()` support
