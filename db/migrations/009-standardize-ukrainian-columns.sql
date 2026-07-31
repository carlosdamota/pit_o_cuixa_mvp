-- Standardize Ukrainian column names from _ukr to _uk to match i18n locale convention ('uk')
ALTER TABLE categories RENAME COLUMN name_ukr TO name_uk;
ALTER TABLE products RENAME COLUMN name_ukr TO name_uk;
ALTER TABLE products RENAME COLUMN description_ukr TO description_uk;
