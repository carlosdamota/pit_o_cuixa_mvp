# Deployment Automation Specification

## Purpose

Automates CD to GoogieHost (preproduction) via GitHub Actions + FTP, post-deploy SQL migrations over HTTP (no SSH), and external cron for menu sync. Dinahosting stays manual.

## Requirements

### DA-001: CD Workflow Pipeline

The system SHALL provide `.github/workflows/deploy-preprod.yml` triggered on push to `main` and `workflow_dispatch`.

#### Scenario: Auto-deploy on push

- GIVEN a commit pushed to `main`
- WHEN the workflow triggers
- THEN steps SHALL execute: checkout → `php -l` → `scripts/test-sync.php` → FTP deploy → post-deploy migrate
- AND workflow SHALL fail if any step returns non-zero

#### Scenario: CI gate blocks deploy

- GIVEN `php -l` or tests fail
- THEN the workflow SHALL abort before FTP upload — no files transferred

### DA-002: FTP Deployment

The system SHALL deploy via `SamKirkland/FTP-Deploy-Action` using GitHub Secrets (`FTP_HOST`, `FTP_USER`, `FTP_PASS`).

#### Scenario: Successful upload

- GIVEN valid FTP credentials
- THEN all tracked files SHALL be uploaded; `data/` directory SHALL be excluded

#### Scenario: FTP failure

- GIVEN invalid credentials or unreachable server
- THEN the workflow SHALL fail with clear error; no partial upload accepted

### DA-003: .env from GitHub Secrets

The system SHALL generate `.env` on target from GitHub Secrets (`DB_PATH`, `SERVICE_API_TOKEN`, `SITE_URL`). `.env` MUST NOT be committed.

#### Scenario: Generated from secrets

- GIVEN all required secrets configured
- THEN `.env` SHALL be written to server root during deploy

#### Scenario: Missing secret

- GIVEN a required secret is unset
- THEN workflow SHALL fail before deployment — no deploy with incomplete `.env`

### DA-004: MigrationRunner Service

`src/Backend/Services/MigrationRunner.php` encapsulates migration logic, reusable by CLI and HTTP. No ANSI/CLI dependencies.

#### Scenario: Applies pending migrations

- GIVEN 2 unapplied SQL files in `db/migrations/`
- WHEN `MigrationRunner::run()` is called
- THEN 2 migrations applied in filename order; each recorded in `_migrations`; returns `{applied: 2, failed: 0}`

#### Scenario: Idempotent re-run

- GIVEN all migrations already recorded
- WHEN `run()` is called
- THEN 0 applied, 0 failed

#### Scenario: Atomic failure

- GIVEN a migration contains invalid SQL
- THEN that migration is rolled back; subsequent migrations still attempted; failed file NOT recorded in `_migrations`

#### Scenario: Duplicate column

- GIVEN a migration adds an existing column
- THEN migration marked applied (INSERT OR IGNORE); no error reported

#### Scenario: CLI delegates

- GIVEN `scripts/migrate.php` is executed
- THEN it SHALL delegate entirely to `MigrationRunner::run()`

### DA-005: External Cron (cron-job.org)

cron-job.org POSTs to `/api/update-menu` with Bearer token at 00:00/12:00 UTC.

#### Scenario: Scheduled sync

- GIVEN cron-job.org configured with valid token
- WHEN scheduled time arrives
- THEN server processes sync per existing `/api/update-menu` contract

#### Scenario: Invalid token

- GIVEN request without valid Bearer token
- THEN server rejects with HTTP 401 (fail-closed AUTH-2)

### DA-006: Concurrency Lock

File-based `flock` prevents concurrent migration execution.

#### Scenario: Concurrent requests

- GIVEN a migration is running (lock held)
- WHEN a second `run()` call arrives
- THEN second call SHALL fail immediately (non-blocking); only one batch executes

#### Scenario: Lock released

- GIVEN migration completes
- THEN lock released; subsequent runs proceed normally

### DA-007: Rollback Strategy

#### Scenario: Re-run known-good deploy

- GIVEN a deploy introduced regression
- WHEN workflow re-triggered (revert push or manual dispatch)
- THEN FTP overwrites files; recorded migrations NOT re-applied (idempotent)

#### Scenario: Emergency stop

- GIVEN active deploy causing issues
- WHEN workflow disabled in GitHub UI
- THEN no further auto-deploys; Dinahosting manual path remains available

## Constraints

- Plain FTP (no FTPS) — accepted risk
- GoogieHost has no SSH; post-deploy ops via HTTP only
- `data/` excluded from FTP (preserves runtime DB + uploads)
- All migrations MUST be idempotent (safe on pre-populated DBs)
