-- Add Catalan (ca) locale fields to categories and products
-- This migration adds name_ca and description_ca columns to support Catalan language

-- Categories: add name_ca
ALTER TABLE categories ADD COLUMN name_ca TEXT NOT NULL DEFAULT '';

-- Products: add name_ca and description_ca
ALTER TABLE products ADD COLUMN name_ca TEXT NOT NULL DEFAULT '';
ALTER TABLE products ADD COLUMN description_ca TEXT NOT NULL DEFAULT '';
