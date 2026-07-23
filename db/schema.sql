-- Pit o Cuixa — Database Schema + Seed Data
-- SQLite with WAL mode, foreign keys, and indexes
-- Executed by scripts/setup.php on first deploy

PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;
PRAGMA busy_timeout = 5000;

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    username    TEXT    NOT NULL UNIQUE,
    password    TEXT    NOT NULL,  -- bcrypt hash
    display_name TEXT   NOT NULL DEFAULT '',
    role        TEXT    NOT NULL DEFAULT 'admin',
    is_active   INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sessions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token       TEXT    NOT NULL UNIQUE,
    expires_at  TEXT    NOT NULL,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS categories (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        TEXT    NOT NULL UNIQUE,
    name_es     TEXT    NOT NULL,
    name_en     TEXT    NOT NULL,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    is_active   INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS products (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id     INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    slug            TEXT    NOT NULL UNIQUE,
    name_es         TEXT    NOT NULL,
    name_en         TEXT    NOT NULL,
    description_es  TEXT    NOT NULL DEFAULT '',
    description_en  TEXT    NOT NULL DEFAULT '',
    price           REAL    NOT NULL DEFAULT 0.00,
    image_url       TEXT    NOT NULL DEFAULT '',
    last_shop_url   TEXT    NOT NULL DEFAULT '',
    sort_order      INTEGER NOT NULL DEFAULT 0,
    is_active       INTEGER NOT NULL DEFAULT 1,
    is_featured     INTEGER NOT NULL DEFAULT 0,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ============================================================
-- INDEXES
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_products_category  ON products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_slug      ON products(slug);
CREATE INDEX IF NOT EXISTS idx_products_active     ON products(is_active);
CREATE INDEX IF NOT EXISTS idx_products_featured   ON products(is_featured, is_active);
CREATE INDEX IF NOT EXISTS idx_categories_active   ON categories(is_active);
CREATE INDEX IF NOT EXISTS idx_categories_slug     ON categories(slug);
CREATE INDEX IF NOT EXISTS idx_sessions_token      ON sessions(token);
CREATE INDEX IF NOT EXISTS idx_sessions_expires    ON sessions(expires_at);

-- ============================================================
-- SEED: Categories (11)
-- ============================================================

INSERT INTO categories (slug, name_es, name_en, sort_order, is_active) VALUES
    ('pollos',          'Pollos a l''ast',          'Rotisserie Chicken',  1,  1),
    ('broquetes',       'Broquetes',                'Skewers',             2,  1),
    ('hamburguesas',    'Hamburguesas',             'Burgers',             3,  1),
    ('entrepans',       'Entrepans',                'Sandwiches',          4,  1),
    ('amanides',        'Amanides',                 'Salads',              5,  1),
    ('patates',         'Patates',                  'Potatoes',            6,  1),
    ('salses',           'Salses',                  'Sauces',              7,  1),
    ('begudes',          'Begudes',                 'Drinks',              8,  1),
    ('postres',          'Postres',                 'Desserts',            9,  1),
    ('menus',            'Menús',                   'Menus',               10, 1),
    ('extras',           'Extras',                  'Extras',              11, 1);

-- ============================================================
-- SEED: Products (~45)
-- ============================================================

-- Pollos a l'ast (category 1)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (1, 'pollo-enter',      'Pollo a l''ast enter',         'Whole Rotisserie Chicken',    'Pollo a l''ast sencer, tendre i sucós, cuinat lentament.',                    'Whole rotisserie chicken, tender and juicy, slow-cooked.',                    12.50, 1, 1, 1),
    (1, 'pollo-mitja',      'Mig pollo a l''ast',           'Half Rotisserie Chicken',     'Mitja unitat de pollo a l''ast, perfecte per a una persona.',                  'Half rotisserie chicken, perfect for one person.',                            7.50,  2, 1, 1),
    (1, 'pollo-quarter',    'Quart de pollo a l''ast',      'Quarter Rotisserie Chicken',  'Quart de pollo a l''ast, ideal per als més petits.',                            'Quarter rotisserie chicken, ideal for kids.',                                 4.50,  3, 1, 0),
    (1, 'pollo-espetec',    'Pollo a l''ast amb espetec',   'Rotisserie Chicken with Sausage', 'Pollo a l''ast acompanyat d''espetec de la casa.',                            'Rotisserie chicken served with house sausage.',                               14.00, 4, 1, 1),
    (1, 'pollo-pattes',     'Pollo a l''ast amb patates',   'Rotisserie Chicken with Potatoes','Pollo a l''ast guarnit amb patates rostides al forn.',                          'Rotisserie chicken with oven-roasted potatoes.',                              14.50, 5, 1, 1);

-- Broquetes (category 2)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (2, 'broqueta-pollo',   'Broqueta de pollo',            'Chicken Skewer',               'Broqueta de pollo marinat amb herbes i rostit a la brasa.',                     'Marinated chicken skewer, grilled to perfection.',                            4.50,  1, 1, 0),
    (2, 'broqueta-vedella', 'Broqueta de vedella',          'Beef Skewer',                  'Broqueta de vedella tendra amb pebrot i ceba.',                                  'Tender beef skewer with bell pepper and onion.',                              5.50,  2, 1, 0),
    (2, 'broqueta-mixta',   'Broqueta mixta',               'Mixed Skewer',                 'Broqueta de pollo i vedella amb verdures a la brasa.',                           'Chicken and beef skewer with grilled vegetables.',                            5.00,  3, 1, 0),
    (2, 'broqueta-xai',     'Broqueta de xai',              'Lamb Skewer',                  'Broqueta de xai especiada, servida amb salsa de iogurt.',                        'Spiced lamb skewer served with yogurt sauce.',                                6.00,  4, 1, 0);

-- Hamburguesas (category 3)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (3, 'hamburguesa-clasica',  'Hamburguesa clàssica',     'Classic Burger',              'Hamburguesa de vedella amb enciam, tomàquet i ceba.',                            'Beef burger with lettuce, tomato and onion.',                                 6.50,  1, 1, 0),
    (3, 'hamburguesa-formatge', 'Hamburguesa amb formatge',  'Cheese Burger',               'Hamburguesa de vedella amb formatge cheddar fos.',                               'Beef burger with melted cheddar cheese.',                                     7.00,  2, 1, 0),
    (3, 'hamburguesa-pollo',    'Hamburguesa de pollo',      'Chicken Burger',              'Hamburguesa de pit de pollo a la planxa amb salsa rostita.',                     'Grilled chicken breast burger with roast sauce.',                             6.50,  3, 1, 0),
    (3, 'hamburguesa-vegetal',  'Hamburguesa vegetal',       'Veggie Burger',               'Hamburguesa de llenties i verdures amb alioli.',                                 'Lentil and vegetable burger with aioli.',                                     6.00,  4, 1, 0);

-- Entrepans (category 4)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (4, 'entrepà-pollo',        'Entrepà de pollo',          'Chicken Sandwich',            'Entrepà de pollo a l''ast amb enciam i maionesa.',                               'Rotisserie chicken sandwich with lettuce and mayo.',                          5.00,  1, 1, 0),
    (4, 'entrepà-formatge',     'Entrepà de formatge',       'Cheese Sandwich',             'Entrepà de formatge de cabra amb tomàquet sec i ruca.',                          'Goat cheese sandwich with sun-dried tomato and rocket.',                      4.50,  2, 1, 0),
    (4, 'entrepà-brut',         'Entrepà brut',              'Loaded Sandwich',             'Entrepà de pollo, formatge, bacon i salsa especial de la casa.',                 'Chicken, cheese, bacon and house special sauce sandwich.',                    6.00,  3, 1, 1),
    (4, 'entrepà-vegetal',      'Entrepà vegetal',           'Veggie Sandwich',             'Entrepà de verdures rostides amb hummus i alvocat.',                             'Roasted veggie sandwich with hummus and avocado.',                            4.50,  4, 1, 0),
    (4, 'entrepà-bikini',       'Bikini',                    'Bikini Toastie',              'Pa de motlle amb pernil dolç i formatge emmental fos.',                           'Toasted sandwich with ham and melted emmental cheese.',                       3.50,  5, 1, 0);

-- Amanides (category 5)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (5, 'amanida-verda',        'Amanida verda',             'Green Salad',                 'Barreja d''enciams, tomàquet cherry i vinagreta balsàmica.',                     'Mixed greens, cherry tomato and balsamic vinaigrette.',                       4.00,  1, 1, 0),
    (5, 'amanida-pollo',        'Amanida de pollo',          'Chicken Salad',               'Amanida amb tires de pollo a l''ast, blat de moro i crujients de pa.',           'Salad with rotisserie chicken strips, corn and croutons.',                    6.00,  2, 1, 0),
    (5, 'amanida-caprese',      'Amanida caprese',           'Caprese Salad',               'Tomàquet, mozzarella fresca i alfàbrega amb oli d''oliva verge extra.',           'Tomato, fresh mozzarella and basil with extra virgin olive oil.',             5.50,  3, 1, 0);

-- Patates (category 6)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (6, 'patates-fregides',     'Patates fregides',          'French Fries',                'Patates fregides cruixents, acabades de fer.',                                    'Crispy french fries, freshly made.',                                          2.50,  1, 1, 0),
    (6, 'patates-rostides',     'Patates rostides',          'Roasted Potatoes',            'Patates rostides al forn amb herbes aromàtiques.',                                'Oven-roasted potatoes with aromatic herbs.',                                 3.00,  2, 1, 0),
    (6, 'patates-bravas',       'Patates braves',            'Patatas Bravas',              'Patates fregides amb salsa brava picant i alioli.',                               'French fries with spicy brava sauce and aioli.',                              3.50,  3, 1, 1),
    (6, 'patates-boletes',      'Boletes de patata',         'Potato Croquettes',           'Boletes de patata farcides de formatge, arrebossades i fregides.',                 'Cheese-filled potato croquettes, breaded and fried.',                         4.00,  4, 1, 0);

-- Salses (category 7)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (7, 'alioli',               'Alioli',                    'Aioli',                       'Alioli tradicional de la casa, fet amb all i oli d''oliva.',                      'House-made traditional garlic aioli.',                                        1.00,  1, 1, 0),
    (7, 'salsa-rostita',        'Salsa rostita',             'Roast Sauce',                 'Salsa rostita de la casa, perfecta per acompanyar el pollo.',                     'House roast sauce, perfect for chicken.',                                     1.00,  2, 1, 0),
    (7, 'salsa-barbacoa',       'Salsa barbacoa',            'BBQ Sauce',                   'Salsa barbacoa casolana amb un toc fumat.',                                       'House-made BBQ sauce with a smoky touch.',                                    1.00,  3, 1, 0),
    (7, 'mojo-verde',           'Mojo verde',                'Green Mojo',                  'Mojo verd canari, fresc i aromàtic.',                                              'Canarian green mojo, fresh and aromatic.',                                    1.00,  4, 1, 0),
    (7, 'salsa-xili',           'Salsa de xili',             'Chili Sauce',                 'Salsa de xili picant per als paladars més atrevits.',                              'Spicy chili sauce for adventurous palates.',                                  1.00,  5, 1, 0);

-- Begudes (category 8)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (8, 'aigua',                'Aigua mineral',             'Mineral Water',               'Aigua mineral natural, ampolla de 500ml.',                                        'Natural mineral water, 500ml bottle.',                                        1.50,  1, 1, 0),
    (8, 'coca-cola',            'Coca-Cola',                 'Coca-Cola',                   'Refresc de cola, llauna de 330ml.',                                                'Cola soft drink, 330ml can.',                                                 1.80,  2, 1, 0),
    (8, 'cervesa',              'Cervesa',                   'Beer',                        'Cervesa freda, ampolla de 330ml.',                                                 'Cold beer, 330ml bottle.',                                                    2.00,  3, 1, 0),
    (8, 'vi-blanc',             'Vi blanc',                  'White Wine',                  'Vi blanc de la terra, ampolla de 750ml.',                                          'Local white wine, 750ml bottle.',                                             8.00,  4, 1, 0),
    (8, 'vi-negre',             'Vi negre',                  'Red Wine',                    'Vi negre de la D.O. Tarragona, ampolla de 750ml.',                                 'D.O. Tarragona red wine, 750ml bottle.',                                      8.00,  5, 1, 0),
    (8, 'suc-taronja',          'Suc de taronja',            'Orange Juice',                'Suc de taronja natural acabat d''esprémer.',                                       'Freshly squeezed orange juice.',                                              2.50,  6, 1, 0);

-- Postres (category 9)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (9, 'crema-catalana',       'Crema catalana',            'Catalan Cream',               'Crema catalana tradicional amb caramel a sobre.',                                  'Traditional Catalan custard with caramel topping.',                           3.50,  1, 1, 0),
    (9, 'pastis-xocolata',      'Pastís de xocolata',        'Chocolate Cake',              'Pastís de xocolata farcit amb ganache i cobertura de cacau.',                      'Chocolate cake filled with ganache and cocoa topping.',                       4.00,  2, 1, 0),
    (9, 'gelat-vainilla',       'Gelat de vainilla',         'Vanilla Ice Cream',           'Gelat de vainilla artesà, servit en copa.',                                        'Artisan vanilla ice cream served in a cup.',                                  2.50,  3, 1, 0),
    (9, 'tarta-formatge',       'Tarta de formatge',         'Cheesecake',                  'Tarta de formatge cremós amb base de galeta.',                                     'Creamy cheesecake with biscuit base.',                                        3.50,  4, 1, 0);

-- Menús (category 10)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (10, 'menu-mitja',          'Menú mig pollo',            'Half Chicken Menu',           'Mig pollo a l''ast, patates fregides, beguda i postre.',                           'Half rotisserie chicken, fries, drink and dessert.',                         11.00, 1, 1, 1),
    (10, 'menu-sencer',         'Menú pollo sencer',          'Whole Chicken Menu',          'Pollo a l''ast enter, patates rostides, beguda i postre.',                         'Whole rotisserie chicken, roasted potatoes, drink and dessert.',             16.00, 2, 1, 1),
    (10, 'menu-broquetes',      'Menú broquetes',             'Skewer Menu',                 'Dues broquetes a triar, patates, beguda i postre.',                                'Two skewers of choice, potatoes, drink and dessert.',                        10.00, 3, 1, 0),
    (10, 'menu-hamburguesa',    'Menú hamburguesa',           'Burger Menu',                 'Hamburguesa amb patates, beguda i postre.',                                        'Burger with fries, drink and dessert.',                                      9.50,  4, 1, 0),
    (10, 'menu-vegetal',        'Menú vegetal',               'Veggie Menu',                 'Amanida o entrepà vegetal, patates, beguda i postre.',                             'Veggie salad or sandwich, fries, drink and dessert.',                        9.00,  5, 1, 0);

-- Extras (category 11)
INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, sort_order, is_active, is_featured) VALUES
    (11, 'pa',                  'Pa',                         'Bread',                       'Pa de pagès artesà, llesques generoses.',                                           'Artisan country bread, generous slices.',                                    1.00,  1, 1, 0),
    (11, 'all-i-oli',           'All i oli',                  'Garlic and Oil',              'Pa amb tomàquet i allioli per untar.',                                              'Bread with tomato and garlic aioli spread.',                                 1.50,  2, 1, 0),
    (11, 'olives',              'Olives',                     'Olives',                      'Olives arbequines marinades, ració generosa.',                                      'Marinated arbequina olives, generous portion.',                              2.00,  3, 1, 0);
