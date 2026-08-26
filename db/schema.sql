-- Pit o Cuixa — Database Schema
-- SQLite with WAL mode, foreign keys, and indexes
-- Executed by scripts/setup.php on first deploy.
--
-- NOTE: This file is the COMPLETE current schema — every migration has been
-- folded in (role column, settings, Catalan/Ukrainian fields, channels,
-- clicks_count, category refactor). A fresh setup runs ONLY this file and
-- no longer needs db/migrations/.
--
-- The catalog tables (categories, products) are intentionally EMPTY.
-- They are populated by `php scripts/fill-menu.php`:
--   - automatically when the PHP dev server starts (scripts/serve.sh)
--   - nightly via cron at 02:00 (scripts/install-cron.sh)
-- The `settings` seed row below is app configuration (admin slider toggle),
-- not catalog data.

PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;
PRAGMA busy_timeout = 5000;

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    username    TEXT    NOT NULL UNIQUE,
    password    TEXT    NOT NULL,  -- bcrypt hash
    display_name TEXT   NOT NULL DEFAULT '',
    role        TEXT    NOT NULL DEFAULT 'admin',
    is_active   INTEGER NOT NULL DEFAULT 1,
    -- 2FA (TOTP): secret is stored ENCRYPTED at rest (AES-256-GCM, see Config::totpEncryptionKey)
    totp_secret     TEXT    NULL,
    totp_enabled    INTEGER NOT NULL DEFAULT 0,
    totp_verified_at TEXT   NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sessions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token       TEXT    NOT NULL UNIQUE,
    expires_at  TEXT    NOT NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS categories (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        TEXT    NOT NULL UNIQUE,
    name_ca     TEXT    NOT NULL DEFAULT '',
    name_es     TEXT    NOT NULL,
    name_en     TEXT    NOT NULL,
    name_uk     TEXT    NOT NULL DEFAULT '',
    sort_order  INTEGER NOT NULL DEFAULT 0,
    is_active   INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS products (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id     INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    slug            TEXT    NOT NULL UNIQUE,
    name_ca         TEXT    NOT NULL DEFAULT '',
    name_es         TEXT    NOT NULL,
    name_en         TEXT    NOT NULL,
    name_uk         TEXT    NOT NULL DEFAULT '',
    description_ca  TEXT    NOT NULL DEFAULT '',
    description_es  TEXT    NOT NULL DEFAULT '',
    description_en  TEXT    NOT NULL DEFAULT '',
    description_uk  TEXT    NOT NULL DEFAULT '',
    price           REAL    NOT NULL DEFAULT 0.00,
    image_url       TEXT    DEFAULT NULL,
    last_shop_url   TEXT    NOT NULL DEFAULT '',
    sort_order      INTEGER NOT NULL DEFAULT 0,
    is_active       INTEGER NOT NULL DEFAULT 1,
    is_featured     INTEGER NOT NULL DEFAULT 0,
    is_dine_in      INTEGER NOT NULL DEFAULT 1,
    is_delivery     INTEGER NOT NULL DEFAULT 1,
    source          TEXT    NOT NULL DEFAULT 'delivery',
    type            TEXT    NOT NULL DEFAULT 'simple',
    menu_data       TEXT    DEFAULT NULL,
    clicks_count    INTEGER NOT NULL DEFAULT 0,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ============================================================
-- APP SETTINGS (key/value store for admin toggles)
-- ============================================================

CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

INSERT OR IGNORE INTO settings (key, value) VALUES ('menu_slider_enabled', '0');

-- ============================================================
-- INDEXES
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_products_category  ON products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_slug      ON products(slug);
CREATE INDEX IF NOT EXISTS idx_products_active     ON products(is_active);
CREATE INDEX IF NOT EXISTS idx_products_featured   ON products(is_featured, is_active);
CREATE INDEX IF NOT EXISTS idx_categories_active   ON categories(is_active);
CREATE INDEX IF NOT EXISTS idx_categories_slug     ON categories(slug);
CREATE INDEX IF NOT EXISTS idx_sessions_token      ON sessions(token);
CREATE INDEX IF NOT EXISTS idx_sessions_expires    ON sessions(expires_at);

-- ============================================================
-- 2FA (TOTP) — challenges + backup codes
-- ============================================================

CREATE TABLE IF NOT EXISTS two_factor_challenges (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token       TEXT    NOT NULL UNIQUE,
    expires_at  TEXT    NOT NULL,
    attempts    INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS backup_codes (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    code_hash   TEXT    NOT NULL,        -- password_hash() of the plaintext backup code
    used_at     TEXT    NULL,           -- NULL = unused, otherwise timestamp
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_2fa_challenges_token  ON two_factor_challenges(token);
CREATE INDEX IF NOT EXISTS idx_2fa_challenges_user   ON two_factor_challenges(user_id);
CREATE INDEX IF NOT EXISTS idx_backup_codes_user     ON backup_codes(user_id);
CREATE INDEX IF NOT EXISTS idx_products_dine_in    ON products(is_dine_in, is_active);
CREATE INDEX IF NOT EXISTS idx_products_delivery   ON products(is_delivery, is_active);
CREATE INDEX IF NOT EXISTS idx_products_source     ON products(source);
CREATE INDEX IF NOT EXISTS idx_products_clicks     ON products(clicks_count DESC, is_active);
