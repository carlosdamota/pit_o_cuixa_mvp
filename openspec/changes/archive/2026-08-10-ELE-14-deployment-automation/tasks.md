# Tasks: ELE-14 — Deployment Automation

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~750 (additions + deletions) |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 backend → PR 2 workflow → PR 3 docs |
| Delivery strategy | auto-chain |
| Chain strategy | pending |
| 400-line budget risk: High |

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Backend: MigrationRunner + `/api/migrate` + CLI delegation + tests | PR 1 (base = feature/tracker) | `php scripts/test-sync.php` + `php scripts/test-migrate-endpoint.php` | Local: temp DB via Config override; real cmd `php scripts/migrate.php` against DB copy | Revert `src/Backend/Services/MigrationRunner.php`, `src/Backend/Api/Migrate.php`, `public/index.php`, `scripts/migrate.php` |
| 2 | CI/CD: `deploy-preprod.yml` (lint gate, .env, FTP, migrate trigger) | PR 2 (base = PR 1 branch) | `actionlint .github/workflows/deploy-preprod.yml`; `workflow_dispatch` dry-run | Real: dispatch workflow vs staging URL before enabling push-to-main | Disable/delete workflow file |
| 3 | Docs: README deployment + `.env.example` | PR 3 (base = PR 2 branch) | Read-through + `git diff --stat` sanity | N/A — docs only, no runtime | Revert `README.md`, `.env.example` |

## Phase 1: Backend Service + Endpoint

- [x] 1.1 `src/Backend/Services/MigrationRunner.php`: PDO via `Config::dbPath()` (WAL+FK), `_migrations` DDL. [DA-004] ~50 lines, no deps
- [x] 1.2 `splitSql()`: regex split `;`+newline; whole-file fallback. [DA-004] ~25
- [x] 1.3 `run()`: skip applied → txn/file → `_migrations` insert; `duplicate column` → `INSERT OR IGNORE`. [DA-004] ~80, deps 1.1-1.2
- [x] 1.4 `acquireLock()`/`isLocked()`: non-blocking `flock` on `data/.migrate.lock`. [DA-006] ~30, deps 1.3
- [x] 1.5 RED unit tests in `scripts/test-sync.php`: apply 2 pending, idempotent 0/0, atomic failure (failed not recorded), duplicate column, concurrency (pre-held lock). [DA-004/DA-006] ~120, deps 1.1-1.4
- [x] 1.6 `src/Backend/Api/Migrate.php` `handle()`: POST-only, `Auth::authorizeSync()`, 401/405/409/500 per JA-005, `{success,applied,failed,errors?}`. [JA-006] ~70, deps 1.4
- [x] 1.7 Register routes in `public/index.php` mirroring `/api/update-menu`: POST + GET→405 `Allow: POST`. [JA-006] ~15, deps 1.6
- [x] 1.8 New `scripts/test-migrate-endpoint.php`: 200/401/405/409/500/partial via in-process shim, temp DB. [JA-006] ~140, deps 1.6-1.7
- [x] 1.9 Rewrite `scripts/migrate.php` to delegate to `MigrationRunner::run()`; keep ANSI printer. [DA-004] ~60, deps 1.3

## Phase 2: CI/CD Workflow

- [x] 2.1 `deploy-preprod.yml`: triggers push `main` + `workflow_dispatch`; CI gate `php -l` + `scripts/test-sync.php`, abort before FTP on non-zero. [DA-001] ~30, deps 1.9
- [x] 2.2 `.env` build step from Secrets (`DB_PATH`, `SERVICE_API_TOKEN`, `SITE_URL`), fail-fast on missing. [DA-003] ~20, deps 2.1
- [x] 2.3 FTP deploy `SamKirkland/FTP-Deploy-Action@v4.3.0` from Secrets, exclude `data/` + `.env`. [DA-002] ~20, deps 2.2
- [x] 2.4 Post-deploy `curl -fsS POST {SITE_URL}/api/migrate` with Bearer token; fail job on non-2xx. [DA-001] ~15, deps 2.3

## Phase 3: Documentation

- [x] 3.1 `README.md` Deployment section: Secrets checklist, cron-job.org → `/api/update-menu` 00:00/12:00 UTC, FTP perms note, "how to add a migration", emergency stop (disable workflow). [DA-005/DA-007] ~50, deps 2.4
- [x] 3.2 `.env.example`: note prod `.env` generated from Secrets. [DA-003] ~8, deps 2.2