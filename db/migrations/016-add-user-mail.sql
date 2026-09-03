-- Pit o Cuixa — Recovery email for admin users
-- Migration 016
--
-- Adds the mail column to users: the recovery address where the 6-digit
-- 2FA fallback code is delivered (AuthController::twoFactorMailCode reads
-- $user['mail'], and AdminSettings writes users.mail from the admin
-- Settings panel).
--
-- Starts NULL for existing users — each admin sets their address from the
-- admin Settings panel. While NULL, the mail-code endpoint fails closed
-- with "No hay email de recuperación configurado".
--
-- Safe to re-run: the duplicate-column error is swallowed by MigrationRunner
-- (treated as already-applied).

ALTER TABLE users ADD COLUMN mail TEXT NULL;
