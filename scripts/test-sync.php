<?php
/**
 * Pit o Cuixa — Product Sync CLI Test
 *
 * Dependency-free test for Product::sync() (vanilla PHP + SQLite, no
 * Composer). Runs against a THROWAWAY database in the system temp dir and
 * never touches data/pitocuixa.db.
 *
 * Covers the sync() contract:
 *   1. First fill on an empty DB inserts every product as active.
 *   2. Re-fill keeps present products active and deactivates the ones that
 *      disappeared from the scraped data.
 *   3. Duplicate-slug input: sync() itself still aborts on UNIQUE(slug) —
 *      that is documented here — so the fill path relies on fill-menu's
 *      dedupeBySlug(), whose semantics are mirrored to exercise the
 *      protection without hitting the network.
 *   4. Products with an empty/unknown category fall back to `otros` instead
 *      of aborting on the NOT NULL category_id constraint.
 *   5. An empty scrape (or a scrape whose products all drop in cleanup) is a
 *      no-op for activation state: nothing gets deactivated, existing active
 *      products stay active. Regression guard for the deactivation loop that
 *      now runs after the scrape loop.
 *   6. Scraped prices are normalized before hitting the REAL column: raw DOM
 *      text "19,50 €" (comma decimal + NBSP + €) round-trips as numeric 19.5,
 *      plain dot-decimal numbers pass through unchanged, and a re-sync
 *      repairs rows previously corrupted with raw price TEXT.
 *   7. Image URL null handling: image_url=null stores NULL, ''/whitespace
 *      converges to NULL on insert, re-syncs with null/'' images are
 *      idempotent (NULL and '' compare EQUAL, so no spurious UPDATE), and a
 *      real image change (URL → null) still updates the row.
 *
 * Usage: php scripts/test-sync.php
 * Exit code: 0 when every check passes, 1 otherwise.
 *
 * @package Pit\Cuixa\Scripts
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from CLI.\n";
    exit(1);
}

use Pit\Cuixa\Backend\Db\Connection;
use Pit\Cuixa\Backend\Db\Repositories\Product;

// ── Test DB override ─────────────────────────────────────────────────────
// Connection::get() resolves the DB file through Config::dbPath(). The real
// Config (src/shared/config.php) gives .env precedence — and this repo's .env
// sets DB_PATH=./data/pitocuixa.db, so any putenv()/getenv() attempt would be
// overridden and would silently point the test at the LIVE database.
// The test therefore defines a minimal Config stub returning the throwaway
// path BEFORE any connection is opened. The production config is never
// loaded, so data/pitocuixa.db is never opened by this script.
$dbPath = sys_get_temp_dir() . '/pitocuixa-sync-test-' . uniqid('', true) . '.db';

if (!class_exists('Config', false)) {
    final class Config
    {
        public static string $dbPath = '';

        public static function dbPath(): string
        {
            return self::$dbPath;
        }
    }
}
Config::$dbPath = $dbPath;

require_once __DIR__ . '/../src/Backend/Db/Connection.php';
require_once __DIR__ . '/../src/Backend/Db/Repositories/Category.php';
require_once __DIR__ . '/../src/Backend/Db/Repositories/Product.php';

// ── Helpers ──────────────────────────────────────────────────────────────

$results = ['pass' => 0, 'fail' => 0];

function status(string $label, string $message): void
{
    $green  = "\033[32m";
    $yellow = "\033[33m";
    $red    = "\033[31m";
    $reset  = "\033[0m";
    $color  = match ($label) {
        '✓' => $green,
        '!' => $yellow,
        '✗' => $red,
        default => '',
    };
    echo "{$color}[{$label}]{$reset} {$message}\n";
}

function record(bool $ok, string $label, string $detail = ''): void
{
    global $results;
    $results[$ok ? 'pass' : 'fail']++;
    $suffix = $detail !== '' ? " ({$detail})" : '';
    if ($ok) {
        status('✓', $label . $suffix);
    } else {
        status('✗', $label . $suffix);
    }
}

/**
 * Minimal scraped-product shape accepted by Product::sync()/insert().
 */
function makeProduct(string $slug, string $category = 'platos', int $sortOrder = 1): array
{
    return [
        'slug'            => $slug,
        'category'        => $category,
        'name_es'         => 'Product ' . $slug,
        'description_es'  => 'Description for ' . $slug,
        'price'           => '9.90',
        'image_url'       => 'https://example.com/img/' . $slug . '.jpg',
        'last_shop_url'   => 'https://example.com/p/' . $slug,
        'sort_order'      => $sortOrder,
    ];
}

/**
 * Seed the category set fill-menu.php ensures before syncing (categoryNames()
 * + the `otros` fallback bucket). Without these, every product would hit the
 * defensive skip path and the test would assert nothing useful.
 */
function seedCategories(\PDO $pdo): void
{
    $pdo->exec("INSERT INTO categories (slug, name_es, name_en, name_ca, name_uk, sort_order, is_active) VALUES
        ('menus', 'Menu', 'Menu', 'Menú', '', 1, 1),
        ('platos', 'Platos principales', 'Main dishes', 'Plats principals', '', 2, 1),
        ('entrantes', 'Entrantes', 'Starters', 'Entrants', '', 3, 1),
        ('bebidas', 'Bebidas', 'Drinks', 'Begudes', '', 4, 1),
        ('postres', 'Postres', 'Desserts', 'Postres', '', 5, 1),
        ('portes', 'Portes', 'Delivery Charges', 'Portes', '', 6, 1),
        ('otros', 'Otros', '', '', '', 7, 1)");
}

/**
 * Wipe catalog tables and re-seed categories so each case starts from a
 * known state (same as a fresh fill-menu run on an empty DB).
 */
function resetDb(\PDO $pdo): void
{
    $pdo->exec('DELETE FROM products');
    $pdo->exec('DELETE FROM categories');
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('products', 'categories')");
    seedCategories($pdo);
}

function productCount(\PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
}

function productRow(\PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE slug = :slug');
    $stmt->execute([':slug' => $slug]);
    $row  = $stmt->fetch();
    return $row !== false ? $row : null;
}

function categoryId(\PDO $pdo, string $slug): int
{
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug');
    $stmt->execute([':slug' => $slug]);
    return (int) $stmt->fetchColumn();
}

// ── Test run ─────────────────────────────────────────────────────────────

echo "\n";
echo "  ╔══════════════════════════════════════════════╗\n";
echo "  ║   Pit o Cuixa — Product Sync Test            ║\n";
echo "  ║   Throwaway DB, live data untouched          ║\n";
echo "  ╚══════════════════════════════════════════════╝\n\n";

try {
    // Sanity check: Connection must resolve to the throwaway DB, never the live one.
    record(
        Config::dbPath() === $dbPath,
        'Config override: Connection will use the throwaway DB',
        $dbPath
    );

    $pdo = Connection::get();

    // Apply the schema (same file fresh setups run).
    $schema = file_get_contents(__DIR__ . '/../db/schema.sql');
    if ($schema === false) {
        throw new \RuntimeException('Cannot read db/schema.sql');
    }
    $pdo->exec($schema);

    // ── Schema sanity (throwaway DB) ────────────────────────────────────
    record(productCount($pdo) === 0, 'Schema check: products table starts empty');
    record((int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 0, 'Schema check: categories table starts empty');
    record(
        (string) $pdo->query("SELECT value FROM settings WHERE key = 'menu_slider_enabled'")->fetchColumn() === '0',
        'Schema check: settings seed row present'
    );

    // ── Case 1: first fill on empty DB ──────────────────────────────────
    resetDb($pdo);
    $repo = new Product();
    $repo->sync([makeProduct('p1'), makeProduct('p2')]);

    $p1 = productRow($pdo, 'p1');
    $p2 = productRow($pdo, 'p2');
    record(productCount($pdo) === 2, 'Case 1: first fill inserts every product', 'expected 2, got ' . productCount($pdo));
    record($p1 !== null && (int) $p1['is_active'] === 1, 'Case 1: p1 inserted and active');
    record($p2 !== null && (int) $p2['is_active'] === 1, 'Case 1: p2 inserted and active');
    record($p1 !== null && (int) $p1['category_id'] === categoryId($pdo, 'platos'), 'Case 1: p1 lands in its scraped category');

    // ── Case 2: re-fill deactivates disappeared, keeps present ──────────
    resetDb($pdo);
    $repo->sync([makeProduct('p1'), makeProduct('p2')]);
    $repo->sync([makeProduct('p2'), makeProduct('p3')]);

    $p1 = productRow($pdo, 'p1');
    $p2 = productRow($pdo, 'p2');
    $p3 = productRow($pdo, 'p3');
    record(productCount($pdo) === 3, 'Case 2: re-fill keeps the total at 3 products', 'expected 3, got ' . productCount($pdo));
    record($p1 !== null && (int) $p1['is_active'] === 0, 'Case 2: p1 deactivated (disappeared from scrape)');
    record($p2 !== null && (int) $p2['is_active'] === 1, 'Case 2: p2 stays active');
    record($p3 !== null && (int) $p3['is_active'] === 1, 'Case 2: p3 inserted and active');

    // ── Case 3: duplicate-slug input ────────────────────────────────────
    // sync() itself does not dedupe: a duplicated slug hits the UNIQUE(slug)
    // constraint and throws PDOException. This is a known limitation — the
    // fill path owns dedupe (fill-menu.php dedupeBySlug()). Documented here
    // instead of fixed inside sync(), whose contract is upsert-by-slug.
    resetDb($pdo);
    $duplicateThrew = false;
    try {
        $repo->sync([makeProduct('dup'), makeProduct('dup')]);
    } catch (\PDOException $e) {
        $duplicateThrew = str_contains($e->getMessage(), 'UNIQUE');
    }
    record(
        $duplicateThrew,
        'Case 3a: duplicate slug passed to sync() throws UNIQUE — documented, fill-menu owns dedupe',
        'expected PDOException'
    );

    // Mirror of fill-menu.php dedupeBySlug(): fill-menu.php cannot be
    // required here because loading it executes the full web scrape. The
    // dedupe semantics are copied so the protection path is exercised
    // offline. Keep in sync with scripts/fill-menu.php.
    resetDb($pdo);
    $seen    = [];
    $deduped = array_values(array_filter(
        [makeProduct('dup'), makeProduct('dup')],
        static function (array $p) use (&$seen): bool {
            $slug = (string) ($p['slug'] ?? '');
            if ($slug === '' || isset($seen[$slug])) {
                return false;
            }
            $seen[$slug] = true;
            return true;
        }
    ));
    $repo->sync($deduped);
    record(productCount($pdo) === 1, 'Case 3b: deduped input (mirror of fill-menu dedupe) syncs cleanly', 'expected 1 product, got ' . productCount($pdo));

    // ── Case 4: empty/unknown category falls back to `otros` ────────────
    resetDb($pdo);
    $repo->sync([makeProduct('orphan', '')]);

    $orphan = productRow($pdo, 'orphan');
    record($orphan !== null, 'Case 4: empty-category product does not crash sync()');
    record($orphan !== null && (int) $orphan['category_id'] === categoryId($pdo, 'otros'), 'Case 4: empty-category product lands in `otros`');
    record($orphan !== null && (int) $orphan['is_active'] === 1, 'Case 4: fallback product active');

    resetDb($pdo);
    $repo->sync([makeProduct('rogue', 'no-such-category')]);

    $rogue = productRow($pdo, 'rogue');
    record($rogue !== null && (int) $rogue['category_id'] === categoryId($pdo, 'otros'), 'Case 4b: unknown category also falls back to `otros`');

    // ── Case 5: empty scrape is a no-op for activation state ─────────────
    // Regression guard: with the deactivation loop running after the scrape
    // loop, an empty scraped map marks every existing product as "not in the
    // map" and deactivates the whole catalog. A failed scrape (network error,
    // page changed, WebScraper returning []) must leave the menu untouched.
    resetDb($pdo);
    $repo->sync([makeProduct('p1'), makeProduct('p2')]);
    $repo->sync([]);

    $p1 = productRow($pdo, 'p1');
    $p2 = productRow($pdo, 'p2');
    record(productCount($pdo) === 2, 'Case 5: empty scrape inserts/deactivates nothing', 'expected 2 products, got ' . productCount($pdo));
    record($p1 !== null && (int) $p1['is_active'] === 1, 'Case 5: p1 stays active after empty scrape');
    record($p2 !== null && (int) $p2['is_active'] === 1, 'Case 5: p2 stays active after empty scrape');

    // Case 5b: non-empty input whose products ALL drop in cleanup (no category
    // — not even `otros` — resolves) must behave the same as an empty scrape.
    resetDb($pdo);
    $repo->sync([makeProduct('p1'), makeProduct('p2')]);
    $pdo->exec("DELETE FROM categories WHERE slug = 'otros'");
    $repo->sync([makeProduct('ghost', 'no-such-category')]);

    $ghost = productRow($pdo, 'ghost');
    $p1    = productRow($pdo, 'p1');
    $p2    = productRow($pdo, 'p2');
    record($ghost === null, 'Case 5b: product with no resolvable category is skipped (no `otros` fallback)');
    record($p1 !== null && (int) $p1['is_active'] === 1, 'Case 5b: p1 stays active when the whole scrape drops in cleanup');
    record($p2 !== null && (int) $p2['is_active'] === 1, 'Case 5b: p2 stays active when the whole scrape drops in cleanup');

    // ── Case 6: price normalization (scraped "19,50 €" must NOT truncate) ──
    // Regression guard for the price-corruption bug: the scraper emits raw DOM
    // text ("19,50 €" — comma decimal, NBSP, € symbol) and a naive
    // (float)"19,50 €" cast yields 19.0, showing 19,00 € instead of 19,50 € on
    // the live menu. Product::insert() must normalize BEFORE binding so the
    // REAL column stores 19.5, and plain admin/API numbers must pass through.
    resetDb($pdo);
    $comma          = makeProduct('comma-price');
    $comma['price'] = "19,50 €"; // raw DOM text as scraped from the external menu
    $dot            = makeProduct('dot-price');
    $dot['price']   = '9.90';    // plain dot-decimal (admin/API style)
    $repo->sync([$comma, $dot]);

    $row = productRow($pdo, 'comma-price');
    record($row !== null, 'Case 6: "19,50 €" price product inserted');
    record(
        $row !== null && is_float($row['price']) && (string) $row['price'] === '19.5',
        'Case 6: "19,50 €" round-trips to numeric 19.5',
        'got ' . gettype($row['price'] ?? 'null') . ' ' . ($row['price'] ?? 'null')
    );
    $row = productRow($pdo, 'dot-price');
    record(
        $row !== null && is_float($row['price']) && (string) $row['price'] === '9.9',
        'Case 6: plain "9.90" (admin/API style) passes through as 9.9',
        'got ' . gettype($row['price'] ?? 'null') . ' ' . ($row['price'] ?? 'null')
    );

    // Case 6b: a row already corrupted by the old code (price stored as raw
    // TEXT "19,50 €") is repaired on re-sync via the change-detection path.
    resetDb($pdo);
    $repo->sync([$comma]);
    $pdo->exec("UPDATE products SET price = '19,50 €' WHERE slug = 'comma-price'");
    record(
        (string) productRow($pdo, 'comma-price')['price'] === '19,50 €',
        'Case 6b: corrupted TEXT price seeded for repair check'
    );
    $repo->sync([$comma]);
    $row = productRow($pdo, 'comma-price');
    record(
        $row !== null && is_float($row['price']) && (string) $row['price'] === '19.5',
        'Case 6b: re-sync corrects corrupted TEXT price to 19.5',
        'got ' . gettype($row['price'] ?? 'null') . ' ' . ($row['price'] ?? 'null')
    );

    // Case 6c: TEXT rows with an integer value ("11,00 €" → 11.0) compare
    // float-equal to their normalized value, so getChanges() alone would never
    // repair them — the type-level repair must. Regression guard for
    // Product::repairTextPrices().
    resetDb($pdo);
    $intComma          = makeProduct('int-comma-price');
    $intComma['price'] = "11,00 €";
    $repo->sync([$intComma]);
    $pdo->exec("UPDATE products SET price = '11,00 €' WHERE slug = 'int-comma-price'");
    $repo->sync([$intComma]);
    $row = productRow($pdo, 'int-comma-price');
    record(
        $row !== null && is_float($row['price']) && (string) $row['price'] === '11',
        'Case 6c: integer-valued TEXT price ("11,00 €") repaired to REAL 11.0 on re-sync',
        'got ' . gettype($row['price'] ?? 'null') . ' ' . ($row['price'] ?? 'null')
    );

    // ── Case 7: image_url NULL handling ────────────────────────────────────
    // Contract: "no image" is stored as NULL, never ''. The scraper emits
    // null for missing/empty src, the admin/CSV paths normalize '' → NULL,
    // and getChanges() treats NULL and '' as EQUAL so a re-sync never fires
    // a spurious UPDATE when one side is '' and the other NULL.
    // updated_at (second precision) is the observable: a spurious UPDATE
    // bumps it, so each equivalence case sleeps 1s before the re-sync.

    // Case 7a: insert with image_url=null stores NULL.
    resetDb($pdo);
    $nullImg          = makeProduct('null-img');
    $nullImg['image_url'] = null;
    $repo->sync([$nullImg]);

    $row = productRow($pdo, 'null-img');
    record($row !== null, 'Case 7a: product with image_url=null inserted');
    record(
        $row !== null && $row['image_url'] === null,
        'Case 7a: image_url=null stored as NULL',
        'got ' . var_export($row['image_url'] ?? 'NULL', true)
    );

    // Case 7b: insert with image_url='' (and whitespace) converges to NULL.
    resetDb($pdo);
    $emptyImg          = makeProduct('empty-img');
    $emptyImg['image_url'] = '';
    $spaceImg          = makeProduct('space-img');
    $spaceImg['image_url'] = '   ';
    $repo->sync([$emptyImg, $spaceImg]);

    $row = productRow($pdo, 'empty-img');
    record($row !== null && $row['image_url'] === null, 'Case 7b: image_url=\'\' converges to NULL on insert');
    $row = productRow($pdo, 'space-img');
    record($row !== null && $row['image_url'] === null, 'Case 7b: whitespace-only image_url also converges to NULL');

    // Case 7c: re-sync with a null image is idempotent — no spurious UPDATE.
    resetDb($pdo);
    $stableImg          = makeProduct('stable-null');
    $stableImg['image_url'] = null;
    $repo->sync([$stableImg]);
    $before = productRow($pdo, 'stable-null');
    sleep(1);
    $repo->sync([$stableImg]); // same null image again
    $after = productRow($pdo, 'stable-null');
    record(
        $after !== null && $after['updated_at'] === $before['updated_at'],
        'Case 7c: re-sync with null image is idempotent (no spurious UPDATE)',
        'before ' . ($before['updated_at'] ?? 'null') . ', after ' . ($after['updated_at'] ?? 'null')
    );

    // Case 7d: stored NULL vs scraped '' compares EQUAL — no spurious UPDATE.
    resetDb($pdo);
    $equivImg          = makeProduct('equiv-null');
    $equivImg['image_url'] = null;
    $repo->sync([$equivImg]);
    $before = productRow($pdo, 'equiv-null');
    sleep(1);
    $equivImg['image_url'] = ''; // DB stores NULL, scrape emits '' → equal
    $repo->sync([$equivImg]);
    $after = productRow($pdo, 'equiv-null');
    record(
        $after !== null && $after['updated_at'] === $before['updated_at'],
        'Case 7d: scraped \'\' vs stored NULL does not trigger an UPDATE',
        'before ' . ($before['updated_at'] ?? 'null') . ', after ' . ($after['updated_at'] ?? 'null')
    );

    // Case 7e (positive control): a REAL image change (URL → null) still
    // updates the row and stores NULL — the equivalence above must not
    // suppress genuine changes.
    resetDb($pdo);
    $changedImg          = makeProduct('img-changes'); // has a Cloudinary-style URL
    $repo->sync([$changedImg]);
    $before = productRow($pdo, 'img-changes');
    sleep(1);
    $changedImg['image_url'] = null; // image disappears from the scrape
    $repo->sync([$changedImg]);
    $after = productRow($pdo, 'img-changes');
    record(
        $after !== null && $after['image_url'] === null,
        'Case 7e: URL → null image change updates the row to NULL',
        'got ' . var_export($after['image_url'] ?? 'NULL', true)
    );
    record(
        $after !== null && $after['updated_at'] !== $before['updated_at'],
        'Case 7e: genuine image change bumps updated_at',
        'before ' . ($before['updated_at'] ?? 'null') . ', after ' . ($after['updated_at'] ?? 'null')
    );
} catch (\Throwable $e) {
    record(false, 'Unexpected exception: ' . $e->getMessage());
} finally {
    // Close the singleton so the temp files can be removed, then clean up.
    Connection::close();
    foreach ([$dbPath, $dbPath . '-wal', $dbPath . '-shm'] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    status('!', 'Temp database removed: ' . $dbPath);
}

// ── Summary ──────────────────────────────────────────────────────────────
echo "\n";
$summary = "{$results['pass']} passed, {$results['fail']} failed.";
status($results['fail'] > 0 ? '✗' : '✓', $summary);
exit($results['fail'] > 0 ? 1 : 0);
