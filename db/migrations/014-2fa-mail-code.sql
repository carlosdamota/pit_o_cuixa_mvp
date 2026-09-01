-- Pit o Cuixa — 2FA backup code delivered by email
-- Migration 014
--
-- Adds two columns to two_factor_challenges so an admin who cannot use their
-- authenticator/QR at login time can request a short-lived single-use 6-digit
-- backup code sent to their email (users.username). The code is stored
-- HASHED (bcrypt, same as backup_codes) and NEVER in plaintext.
--
-- A dedicated column pair is used (rather than reusing pending_secret) because
-- pending_secret is already consumed by the 2FA re-enrollment flow: it stages a
-- new TOTP secret until confirmed. Sharing it would collide with that workflow.
--
-- Safe to re-run: the duplicate-column error is swallowed by MigrationRunner
-- (treated as already-applied).

ALTER TABLE two_factor_challenges ADD COLUMN mail_code_hash      TEXT NULL;
ALTER TABLE two_factor_challenges ADD COLUMN mail_code_expires_at TEXT NULL;
