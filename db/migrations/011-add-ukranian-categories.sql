BEGIN TRANSACTION;

--Migracion para insertar la traducción de las categorías al ukraniano

UPDATE categories SET name_uk = 'меню' WHERE slug = 'menus';
UPDATE categories SET name_uk = 'Скачати додаток' WHERE slug = 'platos';
UPDATE categories SET name_uk = 'Клювання' WHERE slug = 'entrantes';
UPDATE categories SET name_uk = 'Напої' WHERE slug = 'bebidas';
UPDATE categories SET name_uk = 'Десерти' WHERE slug = 'postres';
UPDATE categories SET name_uk = 'Інші' WHERE slug = 'otros';

--Y actualizo los entrantes a Picoteo / Pica-pica

UPDATE categories SET name_es = 'Picoteo' WHERE slug = 'entrantes';
UPDATE categories SET name_ca = 'Pica-pica' WHERE slug = 'entrantes';

--Y Otros en catalan
UPDATE categories SET name_ca = 'Altres' WHERE slug = 'otros';

COMMIT;