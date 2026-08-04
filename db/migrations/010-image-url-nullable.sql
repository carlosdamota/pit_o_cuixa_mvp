-- ============================================================
-- Migration 010: Make products.image_url nullable
-- ============================================================
-- New contract: "no image" is stored as NULL, not ''. schema.sql
-- already defines image_url TEXT DEFAULT NULL for fresh databases;
-- this migration brings EXISTING databases to the same state.
--
-- SQLite cannot alter a column's nullability (no ALTER COLUMN),
-- so this rebuilds the products table with image_url nullable,
-- copies the data, normalizes legacy '' (and whitespace-only)
-- values to NULL, and recreates the indexes dropped with the old
-- table. Idempotent: running it again is a no-op data-wise and is
-- recorded once in _migrations by scripts/migrate.php.
-- ============================================================

CREATE TABLE products_new (
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

INSERT INTO products_new (
    id, category_id, slug, name_ca, name_es, name_en, name_uk,
    description_ca, description_es, description_en, description_uk,
    price, image_url, last_shop_url, sort_order, is_active, is_featured,
    is_dine_in, is_delivery, source, type, menu_data, clicks_count,
    created_at, updated_at
)
SELECT
    id, category_id, slug, name_ca, name_es, name_en, name_uk,
    description_ca, description_es, description_en, description_uk,
    price,
    CASE WHEN TRIM(image_url) = '' THEN NULL ELSE image_url END,
    last_shop_url, sort_order, is_active, is_featured,
    is_dine_in, is_delivery, source, type, menu_data, clicks_count,
    created_at, updated_at
FROM products;

DROP TABLE products;

ALTER TABLE products_new RENAME TO products;

CREATE INDEX IF NOT EXISTS idx_products_category  ON products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_slug      ON products(slug);
CREATE INDEX IF NOT EXISTS idx_products_active     ON products(is_active);
CREATE INDEX IF NOT EXISTS idx_products_featured   ON products(is_featured, is_active);
CREATE INDEX IF NOT EXISTS idx_products_dine_in    ON products(is_dine_in, is_active);
CREATE INDEX IF NOT EXISTS idx_products_delivery   ON products(is_delivery, is_active);
CREATE INDEX IF NOT EXISTS idx_products_source     ON products(source);
CREATE INDEX IF NOT EXISTS idx_products_clicks     ON products(clicks_count DESC, is_active);
