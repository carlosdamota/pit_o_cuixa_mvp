-- Migration 005: Add clicks_count column to products table and create index

ALTER TABLE products ADD COLUMN clicks_count INTEGER NOT NULL DEFAULT 0;

CREATE INDEX IF NOT EXISTS idx_products_clicks ON products(clicks_count DESC, is_active);
