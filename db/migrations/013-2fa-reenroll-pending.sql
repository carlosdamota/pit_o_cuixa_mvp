-- Pit o Cuixa — Stage pending TOTP secret for re-enrollment
-- Migration 013
--
-- Adds a column to two_factor_challenges so a re-enrolling admin can stage a
-- NEW TOTP secret without overwriting the ACTIVE one (users.totp_secret).
-- The active secret stays valid until the new secret is CONFIRMED, preventing
-- lockout if the admin abandons the re-enrollment flow mid-way.
--
-- Safe to re-run: the duplicate-column error is swallowed by MigrationRunner
-- (treated as already-applied).

ALTER TABLE two_factor_challenges ADD COLUMN pending_secret TEXT NULL;
