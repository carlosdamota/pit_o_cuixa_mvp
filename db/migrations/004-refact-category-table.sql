BEGIN TRANSACTION;

--RESETEAMOS TABLAS
DELETE FROM products;
DELETE FROM categories;

DELETE FROM sqlite_sequence AS s WHERE name = 'categories';
DELETE FROM sqlite_sequence AS s WHERE name = 'products';

--INSERTAMOS CATEGORIAS CORRECTAS

INSERT INTO categories (slug, name_es, name_en, name_ca, sort_order, is_active)
VALUES
('menus', 'Menu', 'Menu', 'Menú', 1, 1),
('platos', 'Platos principales', 'Main dishes', 'Plats principals', 2, 1),
('entrantes', 'Entrantes', 'Starters', 'Entrants', 3, 1),
('bebidas', 'Bebidas', 'Drinks', 'Begudes', 4, 1),
('postres', 'Postres', 'Desserts', 'Postres', 5, 1);

COMMIT;
