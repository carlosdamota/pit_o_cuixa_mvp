-- ============================================================
-- Migration 002: Add settings table for admin toggles
-- Idempotent: safe to run multiple times
-- ============================================================

CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

INSERT OR IGNORE INTO settings (key, value) VALUES ('menu_slider_enabled', '0');
