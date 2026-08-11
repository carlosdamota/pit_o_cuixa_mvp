# Delta for JSON API

## ADDED Requirements

### JA-006: Migration Endpoint

The system MUST expose `POST /api/migrate` to apply pending SQL migrations over HTTP. This endpoint SHALL use the same `Auth::authorizeSync()` pattern as `/api/update-menu` (Bearer SERVICE_API_TOKEN or admin session token, fail-closed).

#### Scenario: Apply pending migrations with valid service token

- GIVEN `SERVICE_API_TOKEN` is configured and 2 pending migrations exist
- WHEN a client sends `POST /api/migrate` with `Authorization: Bearer {SERVICE_API_TOKEN}`
- THEN the response SHALL be HTTP 200 with `{ "success": true, "applied": 2, "failed": 0 }`
- AND the `_migrations` table SHALL record both applied migrations

#### Scenario: No pending migrations

- GIVEN all migrations are already applied
- WHEN a client sends `POST /api/migrate` with valid token
- THEN the response SHALL be HTTP 200 with `{ "success": true, "applied": 0, "failed": 0 }`

#### Scenario: Reject request without valid token

- GIVEN no valid Bearer token is provided
- WHEN a client sends `POST /api/migrate`
- THEN the response SHALL be HTTP 401 with `{ "error": true, "message": "Unauthorized", "code": 401 }`

#### Scenario: Reject GET method

- GIVEN the endpoint is POST-only
- WHEN a client sends `GET /api/migrate`
- THEN the response SHALL be HTTP 405 with `Allow: POST` header
- AND the response body SHALL follow the JA-005 error format

#### Scenario: Migration failure returns partial results

- GIVEN 3 pending migrations exist and the 2nd contains invalid SQL
- WHEN a client sends `POST /api/migrate` with valid token
- THEN the response SHALL be HTTP 200 with `{ "success": false, "applied": 1, "failed": 1, "errors": ["003_xxx.sql: <message>"] }`
- AND the 3rd migration SHALL still be attempted
- AND the failed migration SHALL NOT be recorded in `_migrations`

#### Scenario: Concurrent migration requests

- GIVEN a migration is already running (lock held)
- WHEN another `POST /api/migrate` request arrives
- THEN the second request SHALL return HTTP 409 with `{ "error": true, "message": "Migration already in progress", "code": 409 }`

#### Scenario: Internal error during migration

- GIVEN the database file is corrupted or unreadable
- WHEN a client sends `POST /api/migrate` with valid token
- THEN the response SHALL be HTTP 500 with `{ "error": true, "message": "Migration failed", "code": 500 }`
- AND the error SHALL be logged via `error_log()`

## Contracts

### Endpoint Summary (Additions)

| Method | Path | Auth | Response |
|--------|------|------|----------|
| POST | `/api/migrate` | Yes (service token or admin session) | `{ success, applied, failed, errors? }` |

### Response Schema (Addition)

```json
{
  "success": "boolean",
  "applied": "integer (count of migrations applied)",
  "failed": "integer (count of migrations that failed)",
  "errors": "string[] (optional, present only when failed > 0)"
}
```
