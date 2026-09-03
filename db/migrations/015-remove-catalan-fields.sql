-- Pit o Cuixa — Migration 015: remove Catalan fields
--
-- The client rejected the auto-translated Catalan (CA) content and decided to
-- drop the Catalan locale entirely. Spanish (es) becomes the default locale;
-- English (en) and Ukrainian (uk) keep using the existing free DeepL API.
--
-- This migration permanently deletes the Catalan column data from
-- categories and products. The application no longer reads or writes
-- these columns (i18n fallback chain: requested locale → es → en).
--
-- Requires SQLite >= 3.35 (ALTER TABLE ... DROP COLUMN). Hosts running an
-- older SQLite would need the classic table-rebuild fallback instead:
-- create new tables without the CA columns, INSERT ... SELECT the kept
-- columns, drop the old tables, rename the new ones.
--
-- Applied by src/Backend/Services/MigrationRunner.php (POST /api/migrate at
-- deploy time, or `php scripts/migrate.php` locally).

ALTER TABLE products DROP COLUMN name_ca;
ALTER TABLE products DROP COLUMN description_ca;
ALTER TABLE categories DROP COLUMN name_ca;
