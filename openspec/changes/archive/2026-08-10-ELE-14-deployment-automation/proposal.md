# Proposal: ELE-14 — Deployment Automation (GitHub Actions → GoogieHost)

## Intent

Deploys are manual (Git pull/SFTP + SSH migrations) on Dinahosting. This change automates deploys to GoogieHost (preproduction) via GitHub Actions + FTP, runs post-deploy SQL migrations over HTTP (GoogieHost has no SSH), and schedules menu sync via external cron (cron-job.org) instead of local cron. Dinahosting (client production) stays manual for now.

## Scope

### In Scope
- CD workflow `.github/workflows/deploy-preprod.yml`: push to `main` / manual dispatch → FTP upload → post-deploy migrate → notify
- `POST /api/migrate` endpoint (Bearer SERVICE_API_TOKEN) applying pending SQL migrations idempotently
- Migration logic extracted from `scripts/migrate.php` into a reusable, non-CLI service
- .env provisioned on GoogieHost from GitHub Secrets (DB_PATH, SERVICE_API_TOKEN, SITE_URL, …)
- Ops docs: GitHub Secrets setup, cron-job.org job pointing at `/api/update-menu`, FTP permissions note

### Out of Scope
- Dinahosting (client production) deployment — standby, workflow stays reusable
- SSH/shell access on GoogieHost (unavailable); local cron setup
- Migrating existing DB content — preprod starts fresh (setup + migrate)

## Capabilities

### New Capabilities
- `deployment-automation`: CD pipeline (FTP deploy, .env from secrets, post-deploy migrate trigger, preprod target), external cron contract hitting `/api/update-menu`

### Modified Capabilities
- `json-api`: add `POST /api/migrate` — Bearer token auth (fail-closed), POST-only (405 on GET), idempotent `_migrations` tracking, error format per JA-005

## Approach

1. Extract `scripts/migrate.php` logic into `src/Backend/Services/MigrationRunner.php` (no ANSI/CLI deps); CLI script delegates to it.
2. Register `POST /api/migrate` in `public/index.php` following the `/api/update-menu` auth pattern.
3. CD workflow: checkout → `php -l` + `scripts/test-sync.php` → SamKirkland/FTP-Deploy-Action to GoogieHost → curl `POST {SITE_URL}/api/migrate` with token → fail job on non-2xx.
4. Build `.env` in-workflow from GitHub Secrets (never committed); document one-time seed fallback.
5. cron-job.org → `POST https://{preprod}/api/update-menu` with Bearer token, twice daily (same contract `scripts/cron-sync.php` already uses).

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `.github/workflows/deploy-preprod.yml` | New | CD pipeline (FTP + migrate + notify) |
| `public/index.php` | Modified | Register `POST /api/migrate` |
| `src/Backend/Services/MigrationRunner.php` | New | Reusable migration runner |
| `src/Backend/Api/Migrate.php` | New | HTTP controller for migrations |
| `scripts/migrate.php` | Modified | Delegates to MigrationRunner |
| `openspec/specs/json-api/spec.md` | Modified | New `/api/migrate` requirement |
| `.env.example` / docs | Modified | Document secrets + cron-job.org |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| FTP auth exposure | Med | Prefer FTPS; scoped credentials; secrets-only, never in repo |
| Secrets leak via .env | Med | GitHub Secrets + workflow masks; `.env` gitignored; fail-closed token checks |
| Concurrent/incomplete migrations | Low | Single-flight + `_migrations` idempotency; WAL pragma; re-runnable |
| FTP breaks permissions (`data/`, `uploads/`) | Low | Exclude `data/` from deploy; set perms in FTP action; document uploads backup |

## Rollback Plan

- Re-run last known-good workflow; workflow is idempotent (migrations tracked, FTP overwrite).
- Migrations: `_migrations` rows make re-runs safe; manual SFTP restore of `data/` if needed.
- Stop deploys instantly by disabling the workflow in GitHub UI. Dinahosting manual path untouched as fallback.

## Dependencies

- GoogieHost FTP credentials (host/user/pass) as GitHub Secrets; cron-job.org account; GitHub Secrets configured before first run.

## Success Criteria

- [ ] Push to `main` auto-deploys to GoogieHost and runs pending migrations
- [ ] `/api/migrate` applies pending SQL with valid token; 401 without; 405 on GET
- [ ] cron-job.org triggers `/api/update-menu` twice daily; `data/cron-sync.log` shows OK
- [ ] `.env` never appears in repo; all secrets live in GitHub Secrets
