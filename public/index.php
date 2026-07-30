<?php
/**
 * Pit o Cuixa — Front Controller
 *
 * Single entry point for all HTTP requests.
 * Routes /api/* to JSON API controllers and all other paths
 * to HTML page controllers (SSR).
 *
 * @package Pit\Cuixa
 */

declare(strict_types=1);

// ── 1. Bootstrap ───────────────────────────────────────────────────────
require_once __DIR__ . '/../src/shared/bootstrap.php';

use Pit\Cuixa\Backend\Router;
use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Api\Products;
use Pit\Cuixa\Backend\Api\Menu;
use Pit\Cuixa\Backend\Api\AuthController;
use Pit\Cuixa\Backend\Api\AdminProducts;
use Pit\Cuixa\Backend\Api\AdminCategories;
use Pit\Cuixa\Backend\Api\AdminIO;
use Pit\Cuixa\Backend\Api\WebScraper;
use Pit\Cuixa\Backend\Pages\Home;
use Pit\Cuixa\Backend\Pages\Menu as MenuPage;
use Pit\Cuixa\Backend\Pages\Admin\Login as AdminLogin;
use Pit\Cuixa\Backend\Pages\Admin\Dashboard as AdminDashboard;
use Pit\Cuixa\Backend\Pages\Admin\Products as AdminProductsPage;
use Pit\Cuixa\Backend\Pages\Admin\Categories as AdminCategoriesPage;
use Pit\Cuixa\Backend\Pages\Admin\SettingsPage as AdminSettingsPage;
use Pit\Cuixa\Backend\Api\AdminSettings;
use Pit\Cuixa\Backend\Pages\Admin\ImportExport as AdminImportExportPage;
use Pit\Cuixa\Backend\Pages\Faq;
use Pit\Cuixa\Backend\Pages\Sitemap;
use Pit\Cuixa\Backend\Pages\Robots;
use Pit\Cuixa\Backend\Pages\LlmsTxt;

// ── 2. Determine request path and method ───────────────────────────────
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

// ── 3. Helper: Render an SSR HTML page ────────────────────────────────

/**
 * Render a page template wrapped in the default layout.
 *
 * @param string $page   Page template name (without .php, loaded from src/frontend/templates/pages/)
 * @param array  $meta   SEO meta tags (title, description, canonical, og_image, langs)
 * @param array  $data   Page-specific data passed to the template
 * @param int    $code   HTTP status code
 */
function renderPage(string $page, array $meta = [], array $data = [], int $code = 200): void
{
    // Validate page name to prevent Local File Inclusion
    // Allow alphanumeric, underscore, hyphen, and forward slash for subdirectories
    if (!preg_match('/^[a-z0-9_\/-]+$/i', $page)) {
        Response::error('Invalid page', 400);
        return;
    }

    http_response_code($code);

    // Extract variables for template use
    $pageName = $page;
    $metaData = $meta;
    $pageData = $data;

    // Capture page content into buffer, then render inside layout
    ob_start();

    $pageTemplate = __DIR__ . '/../src/frontend/templates/pages/' . $pageName . '.php';

    if (is_file($pageTemplate)) {
        require $pageTemplate;
    } else {
        // Fallback if page template doesn't exist yet
        echo '<h1>' . ($metaData['title'] ?? 'Pit o Cuixa') . '</h1>';
        echo '<p>' . ($metaData['description'] ?? '') . '</p>';
    }

    $content = ob_get_clean();

    // Render layout with captured content
    require __DIR__ . '/../src/frontend/templates/layouts/default.php';
}

// ── 4. Build the router and register routes ───────────────────────────
$router = new Router();

// ── 4a. API Routes ───────────────────────────────────────────────────
// Products API
$router->add('GET', '/api/products', static function (array $params): void {
    $categoryId = isset($_GET['id_category']) ? (int) $_GET['id_category'] : null;
    $limit      = min((int) ($_GET['limit'] ?? 100), 200);
    Products::list($categoryId, $limit);
});

$router->add('GET', '/api/products/popular', static function (array $params): void {
    $limit = min((int) ($_GET['limit'] ?? 5), 50);
    Products::popular($limit);
});

$router->add('POST', '/api/products/{id}/click', static function (array $params): void {
    $productId = (int) ($params['id'] ?? 0);
    Products::recordClick($productId);
});

$router->add('GET', '/api/products/{slug}', static function (array $params): void {
    Products::show($params['slug'] ?? '');
});

$router->add('GET', '/api/categories', static function (array $params): void {
    Products::categories();
});

$router->add('GET', '/api/menu', static function (array $params): void {
    Menu::grouped();
});

// Auth API
$router->add('POST', '/api/auth/login', static function (array $params): void {
    AuthController::login();
});

$router->add('POST', '/api/auth/logout', static function (array $params): void {
    AuthController::logout();
});

//Añadida ruta al Scraper
$router->add('GET', '/api/scraper', static function(array $params): void{
    $scraper = new WebScraper();

    Response::json($scraper->scraper());
});

// Admin API CRUD
$router->add('GET',    '/api/admin/products',       static function (array $params): void { AdminProducts::list(); });
$router->add('POST',   '/api/admin/products',       static function (array $params): void { AdminProducts::create(); });
$router->add('PUT',    '/api/admin/products/{id}',  static function (array $params): void { AdminProducts::update((int) ($params['id'] ?? 0)); });
$router->add('DELETE', '/api/admin/products/{id}',  static function (array $params): void { AdminProducts::delete((int) ($params['id'] ?? 0)); });
$router->add('POST',   '/api/admin/categories',     static function (array $params): void { AdminCategories::create(); });
$router->add('PUT',    '/api/admin/categories/{id}', static function (array $params): void { AdminCategories::update((int) ($params['id'] ?? 0)); });
$router->add('DELETE', '/api/admin/categories/{id}', static function (array $params): void { AdminCategories::delete((int) ($params['id'] ?? 0)); });
$router->add('POST',   '/api/admin/import',         static function (array $params): void { AdminIO::import(); });
$router->add('GET',    '/api/admin/export',         static function (array $params): void { AdminIO::export(); });
$router->add('GET',    '/api/admin/settings',       static function (array $params): void { AdminSettings::get(); });
$router->add('PUT',    '/api/admin/settings',       static function (array $params): void { AdminSettings::update(); });

// ── 4b. Sitemap and Robots (Phase 4) ──────────────────────────────────
$router->add('GET', '/sitemap.xml', static function (array $params): void {
    Sitemap::render();
});

$router->add('GET', '/robots.txt', static function (array $params): void {
    Robots::render();
});

$router->add('GET', '/llms.txt', static function (array $params): void {
    LlmsTxt::render();
});

// ── 4c. HTML Page Routes ──────────────────────────────────────────────

// Home page
$router->add('GET', '/', static function (array $params): void {
    Home::render();
});

// Menu page
$router->add('GET', '/menu', static function (array $params): void {
    MenuPage::render();
});

// FAQ page
$router->add('GET', '/faq', static function (array $params): void {
    Faq::render();
});

// FAQ page with locale prefix (e.g. /ca/faq, /es/faq, /en/faq)
$router->add('GET', '/{lang}/faq', static function (array $params): void {
    $lang = $params['lang'] ?? '';

    if (in_array($lang, ['ca', 'es', 'en'], true)) {
        // Redirect so the request re-enters bootstrap locale resolution,
        // ensuring LANG constant matches translations.
        Response::redirect('/faq?lang=' . $lang, 302);
        return;
    }

    // Unrecognised locale prefix — delegate to 404
    Response::error('Not Found', 404);
});

// Admin pages
$router->add('GET', '/admin', static function (array $params): void {
    AdminDashboard::render();
});

$router->add('GET', '/admin/login', static function (array $params): void {
    AdminLogin::render();
});

$router->add('GET', '/admin/products', static function (array $params): void {
    AdminProductsPage::render();
});

$router->add('GET', '/admin/categories', static function (array $params): void {
    AdminCategoriesPage::render();
});

$router->add('GET', '/admin/import-export', static function (array $params): void {
    AdminImportExportPage::render();
});

$router->add('GET', '/admin/settings', static function (array $params): void {
    AdminSettingsPage::render();
});

// ── 4d. 404 Fallback ──────────────────────────────────────────────────
$router->setNotFound(static function (array $params): void {
    $meta = [
        'title'       => __('error.404'),
        'description' => __('error.404.desc'),
        'canonical'   => \Config::siteUrl() . $_SERVER['REQUEST_URI'],
    ];

    $data = [
        'locale'  => LANG,
        'message' => __('error.404.desc'),
    ];

    renderPage('404', $meta, $data, 404);
});

// ── 5. Dispatch ────────────────────────────────────────────────────────
$router->dispatch($method, $uri);
