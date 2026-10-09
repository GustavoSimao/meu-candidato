# Deployment Plan: Meu Candidato (Vercel Frontend + Fly.io Backend)

## Project Summary

| Layer | Tech |
|-------|------|
| Backend | FastAPI + Uvicorn, SQLAlchemy 2.0 async, Alembic, PostgreSQL, Python 3.11 |
| Frontend | Next.js 16.4 (App Router, Turbopack), React 19.3, TypeScript, Tailwind v4 |
| Workers | Python async ingestion for Câmara/SENADO/TSE APIs |
| Container | Docker (single Dockerfile for api+worker, separate Dockerfile in web/) |
| Orchestration | docker-compose.yml + docker-compose.override.yml |

## Existing Deployment Artifacts

| File | Purpose | Status |
|------|---------|--------|
| `fly.toml` (root) | Fly.io app "meu-candidato" — API | Partially configured, missing DATABASE_URL |
| `fly.toml` (web/) | Fly.io app "meu-candidato-web" — Frontend | Has port mismatch bug (8080 vs 8000) |
| `Dockerfile` (root) | Production API image | No migration step in CMD |
| `Dockerfile.dev` (root) | Dev API image (with ruff, mypy, pytest) | OK for local dev |
| `web/Dockerfile` | Production frontend image | **Broken** – expects `.next/standalone/` but next.config lacks `output: 'standalone'` |
| `web/Dockerfile.dev` | Dev frontend image | OK |
| No `vercel.json` | Vercel config | Missing |
| No `.github/workflows` | CI/CD | Missing |

## Inconsistências Encontradas (Project Inconsistencies)

### 1. `init_db()` uses `create_all()` instead of Alembic migrations
**File:** `app/shared/kernel/database.py:37-39`
- `lifespan` in `app/main.py:22-26` calls `init_db()` on startup
- `init_db()` calls `Base.metadata.create_all()` — creates tables directly from SQLAlchemy models
- Alembic migrations in `alembic/versions/` (`001_initial_schema.py`, `002_add_mandate_unique_constraint.py`) are **never executed**
- Production Dockerfile CMD (`Dockerfile:33`) runs `uvicorn` directly — no `alembic upgrade head`
- **Impact:** Migration files become dead code; schema drift risk; no rollback capability in production

### 2. `alembic.ini` hardcodes local database URL
**File:** `alembic.ini:5`
- `sqlalchemy.url = postgresql+psycopg://meucandidato:meucandidato@localhost:5432/meucandidato`
- Ignores the `DATABASE_URL` environment variable that the rest of the app uses via `get_database_url()`
- **Impact:** `alembic upgrade head` will fail in any non-local deployment

### 3. `web/Dockerfile` expects `.next/standalone/` without `output: 'standalone'`
**Files:** `web/Dockerfile:23` vs `web/next.config.ts:3-13`
- Dockerfile copies `/app/.next/standalone/` which Next.js only generates when `output: 'standalone'` is set
- `next.config.ts` has no `output` field
- **Impact:** Docker build fails or produces a broken image missing `server.js`

### 4. `web/fly.toml` references wrong API port (8080 vs 8000)
**File:** `web/fly.toml:8`
- `NEXT_PUBLIC_API_URL = "http://meu-candidato-api.internal:8080/api/v1"`
- Root `fly.toml` exposes `internal_port = 8000`
- **Impact:** Frontend cannot reach API in Fly.io deployment

### 5. `next.config.ts` rewrites are redundant and build-time bound
**File:** `web/next.config.ts:4-11`
- Rewrites read `NEXT_PUBLIC_API_URL` at **build time** and proxy `/api/v1/:path*` → `${apiUrl}/:path*`
- But `api.ts:26` already fetches from absolute `NEXT_PUBLIC_API_URL` — client-side code never hits `/api/v1/*`
- **Impact:** Rewrites are dead code; on Vercel, build-time env vars must be set in project settings, adding complexity

### 6. No CORS middleware on the FastAPI app
**File:** `app/main.py:29-34` (no CORS middleware added)
- When frontend is on Vercel (different origin) and backend on Fly.io, all client-side API requests will be blocked by CORS
- In Docker Compose, both share `api:8000` host so CORS isn't triggered
- **Impact:** **Blocks the entire Vercel + Fly.io split deployment**

### 7. `docker-compose.yml` hardcodes `ENVIRONMENT=development`
**File:** `docker-compose.yml:8,37`
- Both `api` and `worker` services use `ENVIRONMENT=development`
- SQL echo is enabled in development (`database.py:17`), which is noisy for production

### 8. No persistent database volume configuration for production Fly.io
- Root `fly.toml` has no `[mounts]` section
- PostgreSQL runs as a Docker container in compose; Fly.io would need either a managed Postgres add-on or Fly.io volumes
- Worker writes to `./data/raw` and `./data/quarantine` which are ephemeral in Fly.io without volume mounts

---

## Deployment Architecture Options

### Option A (Recommended): Frontend → Vercel | Backend + DB → Fly.io
**Split-deployment leveraging best-in-class platforms for each tier**

- **Frontend**: Vercel (native Next.js support, global CDN, edge caching)
- **Backend API**: Fly.io (Python/Docker, PostgreSQL volumes in `gru` region)
- **Worker**: Fly.io (same Docker image, runs `python -m app.workers.ingestion`)
- **Database**: Fly.io dedicated PostgreSQL instance or Fly.io volume-mounted Postgres

**Pros:**
- Vercel is purpose-built for Next.js — automatic optimization, ISR, image optimization
- Fly.io is purpose-built for Python/Docker — easy Dockerfile deployment
- Independent scaling, independent deploys
- Good regional proximity (both offer `gru` region for Brazil)

**Cons:**
- Two platforms to manage
- Requires CORS fix and absolute API URL env var on Vercel
- Data ingestion worker needs Fly.io scheduling (cron or always-on)

### Option B: Everything on Fly.io (two separate apps)
- API: `fly.toml` (root) → "meu-candidato"
- Frontend: `fly.toml` (web/) → "meu-candidato-web"
- Shared Postgres on Fly.io

**Pros:**
- Single platform
- Internal Fly.io networking (`*.fly.dev` or `.internal`)
- No CORS needed (same root domain if using custom domains)

**Cons:**
- User reported errors with Fly.io already
- Fly.io doesn't optimize Next.js as well as Vercel
- Need to fix port mismatch and `output: 'standalone'` in next.config
- More expensive than Vercel for frontend (Vercel has generous free tier)

### Option C: Frontend → Vercel | Backend → Render
- Render supports Python + PostgreSQL as a managed service
- Simpler than Fly.io for database management

**Pros:**
- Render is more beginner-friendly than Fly.io
- Managed PostgreSQL on Render

**Cons:**
- Render doesn't have a `gru` (São Paulo) region — adds latency for Brazilian users
- Another platform to learn

### Option D: Full Docker Compose on a single VPS (Railway alternative)
- Keep existing docker-compose.yml
- Deploy to Render, Linode, or AWS ECS with the compose file

**Pros:**
- Uses existing setup with minimal changes
- Full control

**Cons:**
- User disliked Railway; VPS requires maintenance
- No managed PostgreSQL
- SSL/ACM setup manual

---

## Recommended Approach: Option A (Vercel + Fly.io)

### Pre-deployment Fixes Required

#### Fix 1: Add CORS middleware to FastAPI (`app/main.py`)
Add `CORSMiddleware` to allow the Vercel frontend domain to make API requests. Read allowed origins from an environment variable.

**Config needed:**
- `ALLOWED_ORIGINS` env var (comma-separated list of frontend URLs)
- Set to Vercel domain(s) in Fly.io secrets

#### Fix 2: Fix `web/Dockerfile` (add `output: 'standalone'` to next.config)
Add `output: 'standalone'` to `next.config.ts`, or remove the standalone copy logic from the Dockerfile and use a different build strategy.

**Note:** On Vercel, the `vercel.json` or project settings handle the build, so the Dockerfile is only needed for Fly.io deployment of the frontend.

#### Fix 3: Fix `init_db()` to use Alembic migrations
Replace `create_all()` in `init_db()` with a migration runner, or add an `alembic upgrade head` step to the Dockerfile CMD.

**Two sub-options:**
- **3a:** Modify `Dockerfile` CMD to run `alembic upgrade head` before `uvicorn`
- **3b:** Replace `init_db()` with an async Alembic command

#### Fix 4: Fix `alembic.ini` to read from environment
Update `alembic.ini` or `alembic/env.py` to use `get_database_url()` (which reads from `DATABASE_URL`) instead of hardcoded localhost URL.

#### Fix 5: Fix `web/fly.toml` port reference
Change `8080` → `8000` in `web/fly.toml:8`.

#### Fix 6: Simplify `next.config.ts` rewrites
Remove the rewrites function (redundant with absolute URLs in api.ts). The frontend should fetch directly from `NEXT_PUBLIC_API_URL`.

### Implementation Steps

#### Phase 1: Code Fixes
1. **Add CORS middleware** to `app/main.py` — read origins from `ALLOWED_ORIGINS` env var
2. **Fix `init_db()`** — either run alembic migrations in `init_db()` or update Dockerfile CMD
3. **Fix `alembic.ini`** — use environment variable for sqlalchemy.url
4. **Fix `web/Dockerfile`** — add `output: 'standalone'` to `next.config.ts` or restructure
5. **Fix `next.config.ts`** — remove redundant rewrites; make `NEXT_PUBLIC_API_URL` the single source of truth
6. **Fix `web/fly.toml`** — correct port from 8080 to 8000
7. **Update `.env.example`** — add `ALLOWED_ORIGINS`

#### Phase 2: Vercel Deployment (Frontend)
1. Create `web/vercel.json` (if needed) with build output configuration
2. Set environment variable `NEXT_PUBLIC_API_URL` in Vercel project to point to Fly.io API URL (e.g., `https://meu-candidato.fly.dev/api/v1`)
3. Configure `web/.vercelignore` to exclude `data/`, `__pycache__/`, etc.
4. Deploy via Vercel CLI or GitHub integration
5. Verify build succeeds with correct env vars

#### Phase 3: Fly.io Deployment (Backend + DB)
1. **Create managed PostgreSQL** (or volume-mounted Postgres)
   - `flyctl postgres create --name meu-candidato-db --region gru`
   - Capture connection string
2. **Set Fly.io secrets** for API app:
   - `DATABASE_URL=<postgres-connection-string>`
   - `ALLOWED_ORIGINS=https://meu-candidato-web.vercel.app,https://www.meu-candidato.com.br`
   - `ENVIRONMENT=production`
3. **Deploy API**: `flyctl deploy`
4. **Deploy Worker** (separate Fly.io app):
   - Create `fly.toml` for worker or use a separate app
   - Command: `python -m app.workers.ingestion`
   - Mount volume for `data/raw` and `data/quarantine`
   - Set schedule via Fly.io cron or run as always-on
5. **Run migrations**: `flyctl ssh console` → `alembic upgrade head`

#### Phase 4: DNS and SSL
1. Configure custom domain (if any) in Fly.io for API
2. Vercel handles SSL automatically for frontend domain
3. Fly.io provides automatic SSL for `*.fly.dev`

### Environment Variables Reference

**Backend (Fly.io secrets):**
```
DATABASE_URL=postgresql+psycopg://...@...
ENVIRONMENT=production
ALLOWED_ORIGINS=https://meu-candidato-web.vercel.app
```

**Frontend (Vercel env vars):**
```
NEXT_PUBLIC_API_URL=https://meucandidato-api.fly.dev/api/v1
```

### Rollback Path
- Vercel: instant rollback via dashboard (previous deployment)
- Fly.io: `flyctl deploy --image <previous-image>` or revert secrets
- Database: Alembic migrations have downgrade support
- Risk: data ingestion worker may need restart after DB URL change

### Validation Steps
1. `curl https://meucandidato-api.fly.dev/health` → `{"status":"ok","environment":"production"}`
2. `curl https://meucandidato-web.vercel.app/api/health` → returns health data
   - Actually, frontend doesn't proxy to API; verify browser fetches reach API
3. Frontend loads, politician list renders, detail page loads data
4. Worker runs one ingestion phase successfully
5. Alembic migrations applied: `psql -c "\dt"` shows all tables

### Files to Modify
| File | Change |
|------|--------|
| `app/main.py` | Add CORS middleware |
| `app/shared/kernel/database.py` | Replace `init_db()` with migration logic or document alembic step |
| `alembic.ini` | Use env var for sqlalchemy.url |
| `web/next.config.ts` | Remove rewrites, add `output: 'standalone'` for Docker |
| `web/Dockerfile` | (may be simplified after next.config fix) |
| `web/fly.toml` | Fix port 8080 → 8000 |
| `.env.example` | Add `ALLOWED_ORIGINS` |

### New Files to Create
| File | Purpose |
|------|---------|
| `.kilo/plans/1791574840076-deploy-vercel-flyio.md` | This plan |
| `web/.vercelignore` | Exclude data/ and other files from Vercel build |
| (optional) `Dockerfile.worker` | Separate worker image without uvicorn |
| (optional) `.github/workflows/deploy.yml` | CI/CD for automated deploys |
