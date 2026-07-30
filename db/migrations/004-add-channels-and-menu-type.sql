-- Add channels (is_dine_in, is_delivery, source) and product type (type, menu_data)
ALTER TABLE products ADD COLUMN is_dine_in INTEGER NOT NULL DEFAULT 1;
ALTER TABLE products ADD COLUMN is_delivery INTEGER NOT NULL DEFAULT 1;
ALTER TABLE products ADD COLUMN source TEXT NOT NULL DEFAULT 'delivery';
ALTER TABLE products ADD COLUMN type TEXT NOT NULL DEFAULT 'simple';
ALTER TABLE products ADD COLUMN menu_data TEXT DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_products_dine_in ON products(is_dine_in, is_active);
CREATE INDEX IF NOT EXISTS idx_products_delivery ON products(is_delivery, is_active);
CREATE INDEX IF NOT EXISTS idx_products_source ON products(source);
