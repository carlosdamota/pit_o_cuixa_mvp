-- Pit o Cuixa — Add 2FA (TOTP) support
-- Migration 012
--
-- New columns on `users` plus two new tables for the TOTP second factor.
-- Safe to re-run: ALTER ADD COLUMN failures on "duplicate column name" are
-- swallowed by MigrationRunner (treated as already-applied), and the new
-- tables use IF NOT EXISTS.

ALTER TABLE users ADD COLUMN totp_secret      TEXT    NULL;
ALTER TABLE users ADD COLUMN totp_enabled     INTEGER NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN totp_verified_at TEXT    NULL;

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
    code_hash   TEXT    NOT NULL,
    used_at     TEXT    NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_2fa_challenges_token  ON two_factor_challenges(token);
CREATE INDEX IF NOT EXISTS idx_2fa_challenges_user   ON two_factor_challenges(user_id);
CREATE INDEX IF NOT EXISTS idx_backup_codes_user     ON backup_codes(user_id);
