<?php
/**
 * Pit o Cuixa — Fill Menu CLI Script
 *
 * Fills the database from the external online menu:
 *   1. Scrapes products from the external menu (WebScraper).
 *   2. Ensures the referenced categories exist (idempotent).
 *   3. Syncs products into SQLite (Product::sync — same flow as UpdateMenu).
 *   4. Auto-translates missing fields (CA, EN, UK) when DEEPL_API_KEY is set
 *      (best effort — failures are logged, never fatal).
 *   5. Ensures the `menu_slider_enabled` settings row exists.
 *
 * Intended to run:
 *   - automatically when the dev server starts (scripts/serve.sh)
 *   - nightly via cron at 02:00 (scripts/install-cron.sh)
 *   - manually: php scripts/fill-menu.php
 *
 * @package Pit\Cuixa\Scripts
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../src/shared/bootstrap.php';

use Pit\Cuixa\Backend\Api\WebScraper;
use Pit\Cuixa\Backend\Db\Connection;
use Pit\Cuixa\Backend\Db\Repositories\Product;
use Pit\Cuixa\Backend\Db\Repositories\Settings;
use Pit\Cuixa\Backend\Services\MenuTranslator;

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

/**
 * Display names for the category slugs produced by WebScraper::mapCategory().
 * Mirrors the names previously seeded by migration 007.
 *
 * @return array<string, array{name_es: string, name_en: string, name_ca: string}>
 */
function categoryNames(): array
{
    return [
        'menus'     => ['name_es' => 'Menu',              'name_en' => 'Menu',             'name_ca' => 'Menú'],
        'platos'    => ['name_es' => 'Platos principales', 'name_en' => 'Main dishes',     'name_ca' => 'Plats principals'],
        'entrantes' => ['name_es' => 'Entrantes',          'name_en' => 'Starters',        'name_ca' => 'Entrants'],
        'bebidas'   => ['name_es' => 'Bebidas',            'name_en' => 'Drinks',          'name_ca' => 'Begudes'],
        'postres'   => ['name_es' => 'Postres',            'name_en' => 'Desserts',        'name_ca' => 'Postres'],
        'portes'    => ['name_es' => 'Portes',             'name_en' => 'Delivery Charges', 'name_ca' => 'Portes'],
        'otros'     => ['name_es' => 'Otros',              'name_en' => '',                'name_ca' => ''],
    ];
}

/**
 * Map a last_shop category URL segment to a readable slug suffix.
 *
 * The external menu derives slugs from the URL path, so two products in
 * different categories can collide on the same slug (e.g. two "fideua"
 * products under `platos-principales` and `arroces-y-fideua-por-encargo`).
 * On collision the raw category segment is mapped here to a short, human
 * readable suffix. Categories without a mapping return null and the caller
 * falls back to a numeric suffix, so this map only makes collisions
 * prettier — it never needs to be exhaustive.
 *
 * @param  string $rawCategory Raw category segment from the last_shop URL
 * @return string|null Mapped suffix (e.g. "encargo") or null when unmapped
 */
function slugSuffixForCategory(string $rawCategory): ?string
{
    return match ($rawCategory) {
        'arroces-y-fideua-por-encargo-min-24h' => 'encargo',
        default => null,
    };
}

/**
 * Resolve slug collisions instead of dropping products.
 *
 * The external menu can expose the same slug twice (URL-derived slugs).
 * Product::sync() inserts every scraped item into an empty catalog, so a
 * duplicate would violate UNIQUE(products.slug). Instead of dropping the
 * second product, it gets a disambiguated slug:
 *   1. A readable suffix mapped from the last_shop category segment
 *      (slugSuffixForCategory), e.g. `fideua` -> `fideua-encargo`.
 *   2. If that is still taken (or unmapped), a numeric counter
 *      (`fideua-2`, `fideua-3`, ...) which is always unique.
 * Empty slugs are dropped (nothing useful can be derived from them).
 *
 * @param  array<int, array<string, mixed>> $products Scraped products
 * @return array{products: array<int, array<string, mixed>>, renamed: int, dropped: int}
 */
function dedupeBySlug(array $products): array
{
    $seen    = [];
    $renamed = 0;
    $dropped = 0;
    $kept    = [];

    foreach ($products as $p) {
        $slug = (string) ($p['slug'] ?? '');
        if ($slug === '') {
            $dropped++;
            continue;
        }

        if (isset($seen[$slug])) {
            // Collision: try category-derived suffix, then numeric counter.
            $rawCategory = '';
            if (preg_match('#/c/([^/]+)/p/#', (string) ($p['last_shop_url'] ?? ''), $m)) {
                $rawCategory = $m[1];
            }
            $suffix = slugSuffixForCategory($rawCategory);
            if ($suffix !== null) {
                $candidate = $slug . '-' . $suffix;
                if (!isset($seen[$candidate])) {
                    $slug = $candidate;
                }
            }
            if (isset($seen[$slug])) {
                $n = 2;
                do {
                    $candidate = $slug . '-' . $n++;
                } while (isset($seen[$candidate]));
                $slug = $candidate;
            }
            $p['slug'] = $slug;
            $renamed++;
        }

        $seen[$slug] = true;
        $kept[]      = $p;
    }

    return ['products' => $kept, 'renamed' => $renamed, 'dropped' => $dropped];
}

/**
 * Create any category referenced by the scraped products that is missing.
 *
 * Required because Product::sync() resolves category_id from Category::MapSlug()
 * and a fresh database starts with an empty categories table. Idempotent:
 * existing categories (e.g. from migration 007) are left untouched.
 *
 * @param  array<int, array<string, mixed>> $products Scraped products
 * @return int Number of categories ensured
 */
function ensureCategories(array $products): int
{
    $pdo   = Connection::get();
    $names = categoryNames();
    $seen  = [];
    $ensured = 0;

    foreach ($products as $product) {
        $slug = (string) ($product['category'] ?? '');
        // Empty category (scraper products appearing before the first category
        // header) falls back to the `otros` bucket instead of being skipped:
        // skipping it crashed Product::sync() on fresh setups (NULL category_id).
        if ($slug === '') {
            $slug = 'otros';
        }
        if (isset($seen[$slug])) {
            continue;
        }
        $seen[$slug] = true;

        $row  = $names[$slug] ?? ['name_es' => ucfirst($slug), 'name_en' => '', 'name_ca' => ''];
        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO categories (slug, name_es, name_en, name_ca, name_uk, sort_order, is_active)
             VALUES (:slug, :name_es, :name_en, :name_ca, :name_uk, :sort_order, 1)'
        );
        $stmt->execute([
            ':slug'       => $slug,
            ':name_es'    => $row['name_es'],
            ':name_en'    => $row['name_en'] ?? '',
            ':name_ca'    => $row['name_ca'] ?? '',
            ':name_uk'    => '',
            ':sort_order' => count($seen),
        ]);
        $ensured++;
    }

    return $ensured;
}

/**
 * Self-heal the schema on a fresh or partial database (idempotent).
 *
 * A fresh checkout has NO database at all (data/ is gitignored). Running only
 * Settings::ensureSchema() would create just the `settings` table, leaving
 * `categories`/`products` missing — ensureCategories() would then die with
 * "no such table: categories" and serve.sh would boot a server whose /menu
 * throws PDOException. Applying db/schema.sql first guarantees the catalog
 * tables exist; every statement in it is CREATE ... IF NOT EXISTS, so it is
 * safe to re-run on an existing DB. Deliberately non-interactive: admin-user
 * creation stays setup.php's job — this only needs the catalog + settings
 * tables so /menu can serve.
 *
 * @return void
 */
function ensureCatalogSchema(): void
{
    $pdo = Connection::get();

    // Fast path: both catalog tables already exist (normal re-sync on an
    // established DB) — nothing to do.
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name IN ('products', 'categories')"
    );
    if ((int) $stmt->fetchColumn() === 2) {
        return;
    }

    $schema = file_get_contents(__DIR__ . '/../db/schema.sql');
    if ($schema === false) {
        throw new \RuntimeException('Cannot read db/schema.sql');
    }

    $pdo->exec($schema);
}

echo "\n";
echo "  ╔══════════════════════════════════════════════╗\n";
echo "  ║    Pit o Cuixa — Fill Menu from Web          ║\n";
echo "  ║    Scrape, sync, translate (CA, EN, UK)      ║\n";
echo "  ╚══════════════════════════════════════════════╝\n\n";

try {
    // 0. Self-heal the schema on fresh/partial DBs (idempotent, non-interactive).
    //    Must run BEFORE ensureCategories()/Settings::ensureSchema(): a fresh
    //    clone has no database, and the catalog tables must exist before any
    //    product/category query (otherwise fill exits 1 and serve.sh boots a
    //    server whose /menu throws PDOException).
    ensureCatalogSchema();
    status('✓', 'Catalog schema ensured (categories + products present).');

    // 1. Self-heal settings (idempotent) — guarantees menu_slider_enabled on fresh DBs
    settings::ensureSchema();
    status('✓', 'Settings ensured (menu_slider_enabled row present).');

    // 2. Scrape the external menu
    status('!', 'Scraping external menu...');
    $scraper  = new WebScraper();
    $scraped  = $scraper->scraper();
    status('✓', 'Scraped ' . count($scraped) . ' product(s).');

    // 2b. Resolve slug collisions (the external menu can expose the same slug twice)
    $dedup    = dedupeBySlug($scraped);
    $products = $dedup['products'];
    if ($dedup['renamed'] > 0) {
        status('!', "Renamed {$dedup['renamed']} product(s) with duplicate slug.");
    }
    if ($dedup['dropped'] > 0) {
        status('!', "Dropped {$dedup['dropped']} product(s) with empty slug.");
    }

    // 3. Ensure the referenced categories exist before syncing
    $ensured = ensureCategories($products);
    status('✓', "Ensured {$ensured} category slug(s) referenced by the menu.");

    // 4. Sync products (same flow as UpdateMenu::update / /api/update-menu)
    $repo = new Product();
    $repo->sync($products);
    status('✓', 'Synced ' . count($products) . ' product(s) into the database.');

    // 5. Auto-translate missing fields in CA, EN, UK (best effort)
    try {
        $translator = new MenuTranslator();
        $stats      = $translator->translateMissing();
        status('✓', "Translation complete: {$stats['categories']} category field(s) and {$stats['products']} product field(s) translated.");
    } catch (\Throwable $e) {
        error_log('Menu translation error: ' . $e->getMessage());
        status('!', 'Translation skipped: ' . $e->getMessage());
    }

    status('✓', 'Menu fill complete.');
    exit(0);
} catch (\Throwable $e) {
    error_log('fill-menu error: ' . $e->getMessage());
    status('✗', 'Fill failed: ' . $e->getMessage());
    exit(1);
}
