# Delta for Admin Panel

## ADDED Requirements

### AP-007: Settings Page

The system MUST provide a Settings page at `/admin/settings` accessible only to authenticated admins. The page SHALL display a toggle to enable or disable the menu slider feature.

#### Scenario: Access settings page

- GIVEN an authenticated admin
- WHEN they navigate to `/admin/settings`
- THEN the system SHALL render the Settings page with the slider toggle

#### Scenario: Unauthenticated access blocked

- GIVEN a visitor without a valid session
- WHEN they attempt to access `/admin/settings`
- THEN the system SHALL redirect to `/admin/login`

### AP-008: Slider Toggle

The system MUST persist the slider enabled/disabled state in a `settings` SQLite table with schema `settings(key TEXT PRIMARY KEY, value TEXT)`. The key `menu_slider_enabled` MUST default to `'0'` (disabled).

#### Scenario: Toggle slider ON

- GIVEN an authenticated admin on the Settings page
- AND `menu_slider_enabled` is currently `'0'`
- WHEN they toggle the slider setting to enabled and save
- THEN the system SHALL update `menu_slider_enabled` to `'1'` in the `settings` table
- AND the menu page SHALL show the image slider on next load (if images exist)

#### Scenario: Toggle slider OFF

- GIVEN an authenticated admin on the Settings page
- AND `menu_slider_enabled` is currently `'1'`
- WHEN they toggle the slider setting to disabled and save
- THEN the system SHALL update `menu_slider_enabled` to `'0'`
- AND the menu page SHALL show the CSS fallback hero on next load

#### Scenario: Default state on fresh install

- GIVEN a new database with no prior settings
- WHEN the `settings` table is created by migration
- THEN `menu_slider_enabled` SHALL default to `'0'`

### AP-009: Settings API

The system MUST provide REST API endpoints for reading and updating settings.

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/admin/settings` | Yes | Read all settings |
| PUT | `/api/admin/settings` | Yes | Update settings |

#### Scenario: GET settings

- GIVEN an authenticated admin
- WHEN they call `GET /api/admin/settings`
- THEN the system SHALL return JSON with current settings including `menu_slider_enabled`

#### Scenario: PUT update slider setting

- GIVEN an authenticated admin
- WHEN they call `PUT /api/admin/settings` with body `{"menu_slider_enabled": "1"}`
- THEN the system SHALL update the value in the database
- AND SHALL return the updated settings as JSON

#### Scenario: PUT with invalid key rejected

- GIVEN an authenticated admin
- WHEN they call `PUT /api/admin/settings` with an unknown key
- THEN the system SHALL return HTTP 400
- AND SHALL NOT modify any settings

### AP-010: Settings Database Migration

The system MUST include a migration (e.g., `002_settings.sql`) that creates the `settings` table if it does not exist. The migration MUST be idempotent.

#### Scenario: Migration creates settings table

- GIVEN a database without a `settings` table
- WHEN migration `002` runs
- THEN the `settings(key TEXT PRIMARY KEY, value TEXT)` table SHALL be created
- AND `menu_slider_enabled` SHALL be inserted with value `'0'`

#### Scenario: Migration idempotent

- GIVEN the `settings` table already exists
- WHEN migration `002` runs again
- THEN no error SHALL occur
- AND existing data SHALL be preserved

## Contracts

### Settings Table Schema

```sql
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
);
INSERT OR IGNORE INTO settings (key, value) VALUES ('menu_slider_enabled', '0');
```

### Admin API Endpoints (Extended)

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/api/admin/settings` | Yes | Read all settings |
| PUT | `/api/admin/settings` | Yes | Update settings |

## Acceptance Criteria

- [ ] Settings page accessible at `/admin/settings` (auth required)
- [ ] Toggle enables/disables `menu_slider_enabled` in SQLite
- [ ] Default value is `'0'` on fresh install
- [ ] `GET /api/admin/settings` returns current values
- [ ] `PUT /api/admin/settings` updates and returns new values
- [ ] Unknown keys rejected with HTTP 400
- [ ] Migration `002` creates table idempotently
- [ ] `php -l` passes on all new PHP files
