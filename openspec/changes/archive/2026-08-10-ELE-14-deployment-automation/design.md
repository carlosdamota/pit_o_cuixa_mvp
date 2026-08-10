# Design: ELE-14 — Deployment Automation

## Technical Approach

Automate CD to GoogieHost (plain FTP, no SSH) via a GitHub Actions workflow on `push` to `main`: lint → `test-sync.php` → build `.env` from Secrets → FTP upload (exclude `data/`) → `curl POST /api/migrate` with `SERVICE_API_TOKEN`. Migration logic is extracted from `scripts/migrate.php` into `MigrationRunner` (no CLI/ANSI deps), reused by both the CLI script and the new `POST /api/migrate` HTTP controller. Concurrency safety = non-blocking `flock` on a lock file ⊃ `_migrations` row insert inside a SQLite transaction. External cron (cron-job.org) hits the existing `/api/update-menu` — already auth-gated by `Auth::authorizeSync()`. Maps directly to DA-001..007 and JA-006.

## Architecture Decisions

| Decision | Options | Tradeoff | Choice |
|---|---|---|---|
| Workflow file name | `deploy.yml` / `deploy-preprod.yml` | Spec + proposal both say `deploy-preprod.yml`; task brief said `deploy.yml` | `deploy-preprod.yml` (specs authoritative) |
| FTP action | raw `lftp` / `SamKirkland/FTP-Deploy-Action` | Maintained action has exclude dirs, atomic-ish, retries | `SamKirkland/FTP-Deploy-Action@v4.3.0` |
| Concurrency lock | DB row / `flock` / semaphores | `flock` non-blocking + transaction = simplest, no extra table | `flock(LOCK_EX|LOCK_NB)` on `data/.migrate.lock` |
| SQL splitting | `explode(';')` / regex on `;` at line-end / PDO `exec` whole-file | `explode(';')` breaks on `;` inside strings/triggers | Regex split on `;` followed by newline + optional whitespace; fall back to whole-file `exec` on single-statement migrations |
| `.env` provisioning | upload committed / build in-workflow / server-side generator | Build-from-secrets keeps file out of repo and matches `.env.example` keys | Build `.env` in-workflow from Secrets, upload via FTP `echo`/`put` step |
| Migrate trigger | workflow runs SQL remotely / HTTP call to `/api/migrate` | No SSH → HTTP is the only path; reuses auth + returns structured JSON | `curl -fsS POST /api/migrate` with Bearer token |

## Data Flow

```
push main ─→ GitHub Actions ─→ checkout ─→ php -l ─→ scripts/test-sync.php
   │                                                        │ (fail → abort, no FTP)
   ├─ build .env from Secrets (fail-fast if any missing)
   ├─ FTP-Deploy-Action (exclude data/)  ─→  GoogieHost webroot
   └─ curl POST {SITE_URL}/api/migrate  (Bearer SERVICE_API_TOKEN)
                         │
                         ▼
   public/index.php → /api/migrate → Auth::authorizeSync()
                         │ 401 / 405 / 409 / 500
                         ▼
   Migrate::handle() → MigrationRunner::run()
                         │ flock(LOCK_EX|LOCK_NB) → 409 if held
                         ▼
   glob db/migrations/*.sql → skip applied → txn per file → _migrations insert
                         │
                         ▼  { success, applied, failed, errors? }

cron-job.org 00:00/12:00 UTC ─→ POST {SITE_URL}/api/update-menu (Bearer) → existing contract
```

## File Changes

| File | Action | Description |
|---|---|---|
| `.github/workflows/deploy-preprod.yml` | Create | CD pipeline: trigger push main + `workflow_dispatch`; lint+test gate; `.env` from secrets; FTP deploy excluding `data/`; curl `/api/migrate` |
| `src/Backend/Services/MigrationRunner.php` | Create | Reusable migration service: `run()`, `getPendingMigrations()`, `isLocked()`, `acquireLock()`, `splitSql()`. No ANSI/CLI deps. Returns `{applied, failed, errors[]}` |
| `src/Backend/Api/Migrate.php` | Create | HTTP controller: `handle()` — POST-only, `Auth::authorizeSync()`, `401/405/409/500` per JA-005 |
| `public/index.php` | Modify | Register `POST /api/migrate` (+ `GET /api/migrate` → 405 with `Allow: POST`), mirroring `/api/update-menu` |
| `scripts/migrate.php` | Modify | Strip logic; delegate to `(new MigrationRunner())->run()`; keep ANSI status printer wrapping the returned array |
| `README.md` | Modify | Deployment section: Secrets checklist, cron-job.org setup, FTP permissions note, "how to add a migration" |
| `.env.example` | Modify | Document that production `.env` is generated from Secrets (no manual edit) |

## Interfaces / Contracts

```php
namespace Pit\Cuixa\Backend\Services;
final class MigrationRunner {
    public function run(): array;        // ['applied'=>int,'failed'=>int,'errors'=>string[]]
    public function isLocked(): bool;
    public function getPendingMigrations(): array;
}
namespace Pit\Cuixa\Backend\Api;
final class Migrate {
    public function handle(): void;      // emits Response::json + status
}
```

`MigrationRunner::run()` opens its own PDO via `Config::dbPath()` (WAL + FK pragmas), creates `_migrations` if absent, acquires `flock` on `data/.migrate.lock`, iterates `db/migrations/*.sql` in filename order, runs each in a transaction, inserts the filename, and handles the existing `duplicate column name` idempotency case via `INSERT OR IGNORE`. Non-blocking lock — second caller hits `isLocked()` → controller returns 409.

## Testing Strategy

| Layer | What | Approach |
|---|---|---|
| Unit | `MigrationRunner::splitSql` / `getPendingMigrations` / idempotent re-run / atomic failure | Extend `scripts/test-sync.php`: throwaway temp DB, drop migration files, assert `applied/failed/errors` |
| Integration | `POST /api/migrate` 200/401/405/409/500/partial | New `scripts/test-migrate-endpoint.php` calling the controller via in-process request shim against temp DB |
| Integration | Concurrency: second `run()` returns `failed→1` while locked | Temp lock file pre-held; assert `isLocked()` + 409 path |
| E2E | Workflow dry-run | `workflow_dispatch` against a staging URL before enabling `push` to `main` |

## Threat Matrix

N/A — no routing security boundary added beyond reusing the existing `Auth::authorizeSync()` gate (already audited). No shell, no subprocess, no VCS/PR automation, no executable classification. FTP is plaintext-by-constraint (GoogieHost), accepted risk in proposal; Secrets never echoed (`::add-mask::` or implicit GitHub masking).

## Migration / Rollout

One-time: populate GoogieHost GitHub Secrets (`FTP_HOST`, `FTP_USER`, `FTP_PASS`, `DB_PATH`, `SERVICE_API_TOKEN`, `SITE_URL`); seed production DB (migrations are idempotent on a populated DB). Rollback: re-run last good workflow (FTP overwrites; recorded migrations skipped). Emergency stop: disable workflow in GitHub Actions UI; Dinahosting manual path unaffected.

## Open Questions

- [ ] Workflow filename: spec says `deploy-preprod.yml`, task brief said `deploy.yml`. Design assumes `deploy-preprod.yml` per specs — confirm.
- [ ] FTP-Deploy-Action `exclude` global excludes `data/` server-side too (won't delete remote DB) — needs verification against action v4.3 behavior on first run.
- [ ] `data/` write perms after deploy: action may reset dir perms; confirm `0770` survives or set via `known-issues`/`perms` input.