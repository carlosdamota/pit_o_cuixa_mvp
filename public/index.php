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
use Pit\Cuixa\Backend\Api\UpdateMenu;
use Pit\Cuixa\Backend\Api\Migrate;
use Pit\Cuixa\Backend\Api\AdminUpload;
use Pit\Cuixa\Backend\Api\AdminTranslate;
use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Pages\Home;
use Pit\Cuixa\Backend\Pages\Menu as MenuPage;
use Pit\Cuixa\Backend\Pages\Admin\Login as AdminLogin;
use Pit\Cuixa\Backend\Pages\Admin\Dashboard as AdminDashboard;
use Pit\Cuixa\Backend\Pages\Admin\Products as AdminProductsPage;
use Pit\Cuixa\Backend\Pages\Admin\Categories as AdminCategoriesPage;
use Pit\Cuixa\Backend\Pages\Admin\SettingsPage as AdminSettingsPage;
use Pit\Cuixa\Backend\Api\AdminSettings;
use Pit\Cuixa\Backend\Db\Repositories\Settings;
use Pit\Cuixa\Backend\Pages\Admin\ImportExport as AdminImportExportPage;
use Pit\Cuixa\Backend\Pages\FaqPage;
use Pit\Cuixa\Backend\Pages\Privacy;
use Pit\Cuixa\Backend\Pages\Cookies;
use Pit\Cuixa\Backend\Pages\Sitemap;
use Pit\Cuixa\Backend\Pages\Robots;
use Pit\Cuixa\Backend\Pages\LlmsTxt;

// ── 2. Determine request path and method ───────────────────────────────
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

// Shared IIS hosting (dinahosting) only routes GET/HEAD/POST to PHP:
// PUT/DELETE are rejected by the web server (405) before this script runs.
// The admin client tunnels those verbs through POST with the standard
// X-HTTP-Method-Override header — unwrap it here, whitelisted, so the
// router sees the intended verb. Real PUT/DELETE requests (Apache, PHP
// built-in server) keep working unchanged.
if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $override = strtoupper(trim((string) $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']));

    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $override;
    }
}

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

    // Every page layout renders the footer, which reads company settings.
    // Self-heal the settings table + seeds before any page render (idempotent).
    Settings::ensureSchema();

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

// 2FA (TOTP) second factor — part of the admin login flow + self-enrollment
$router->add('POST', '/api/auth/2fa-verify', static function (array $params): void {
    AuthController::twoFactorVerify();
});

$router->add('POST', '/api/auth/2fa-enroll-start', static function (array $params): void {
    AuthController::twoFactorEnrollStart();
});

$router->add('POST', '/api/auth/2fa-enroll-confirm', static function (array $params): void {
    AuthController::twoFactorEnrollConfirm();
});

$router->add('POST', '/api/auth/2fa-mail-code', static function (array $params): void {
    AuthController::twoFactorMailCode();
});

//Añadida ruta al Scraper — read-only utility, requires auth (AUTH-1)
$router->add('GET', '/api/scraper', static function(array $params): void{
    Auth::authorizeSync();

    $scraper = new WebScraper();

    Response::json($scraper->scraper());
});

//AÑADIR ROUTER A CHAT
$router->add('POST','/api/chat', static function(array $params): void{
    require_once __DIR__ . '/../src/Backend/Api/chat.php';
});

//POST /api/update-menu — state-mutating menu sync, requires auth, POST-only
$router->add('POST', '/api/update-menu', static function (array $params): void {
    Auth::authorizeSync();

    try {
        $handler = new UpdateMenu();
        Response::json($handler->update());
    } catch (\Throwable $e) {
        error_log('update-menu sync failed: ' . $e->getMessage());
        Response::error('Sync failed', 500);
    }
});

//GET /api/update-menu — 405: the sync is POST-only and MUST NOT trigger on GET (METHOD-4)
$router->add('GET', '/api/update-menu', static function (array $params): void {
    header('Allow: POST');
    Response::error('Method Not Allowed', 405);
});

//POST /api/migrate — apply pending SQL migrations, requires auth
$router->add('POST', '/api/migrate', static function (array $params): void {
    $handler = new Migrate();
    $handler->handle();
});

//GET /api/migrate — 405: migrations are POST-only
$router->add('GET', '/api/migrate', static function (array $params): void {
    header('Allow: POST');
    Response::error('Method Not Allowed', 405);
});

// Admin API CRUD
$router->add('GET',    '/api/pitocuixa/products',       static function (array $params): void { AdminProducts::list(); });
$router->add('POST',   '/api/pitocuixa/products',       static function (array $params): void { AdminProducts::create(); });
$router->add('PUT',    '/api/pitocuixa/products/{id}',  static function (array $params): void { AdminProducts::update((int) ($params['id'] ?? 0)); });
$router->add('DELETE', '/api/pitocuixa/products/{id}',  static function (array $params): void { AdminProducts::delete((int) ($params['id'] ?? 0)); });
$router->add('POST',   '/api/pitocuixa/categories',     static function (array $params): void { AdminCategories::create(); });
$router->add('PUT',    '/api/pitocuixa/categories/{id}', static function (array $params): void { AdminCategories::update((int) ($params['id'] ?? 0)); });
$router->add('DELETE', '/api/pitocuixa/categories/{id}', static function (array $params): void { AdminCategories::delete((int) ($params['id'] ?? 0)); });
$router->add('POST',   '/api/pitocuixa/import',         static function (array $params): void { AdminIO::import(); });
$router->add('GET',    '/api/pitocuixa/export',         static function (array $params): void { AdminIO::export(); });
$router->add('GET',    '/api/pitocuixa/settings',       static function (array $params): void { AdminSettings::get(); });
$router->add('PUT',    '/api/pitocuixa/settings',       static function (array $params): void { AdminSettings::update(); });
$router->add('POST',   '/api/pitocuixa/upload',         static function (array $params): void { AdminUpload::uploadImage(); });
//POST /api/pitocuixa/translate — translate missing fields via DeepL, admin-session auth
$router->add('POST',   '/api/pitocuixa/translate',      static function (array $params): void { AdminTranslate::run(); });

//GET /api/pitocuixa/translate — 405: POST-only, must not trigger translation on GET
$router->add('GET',    '/api/pitocuixa/translate',      static function (array $params): void {
    header('Allow: POST');
    Response::error('Method Not Allowed', 405);
});

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
    FaqPage::render();
});

// FAQ page with locale prefix (e.g. /es/faq, /en/faq)
$router->add('GET', '/{lang}/faq', static function (array $params): void {
    $lang = $params['lang'] ?? '';

    // Catalan was removed as a locale — permanently 301 the legacy /ca/*
    // URLs to the unprefixed path so indexed Catalan pages keep working.
    if ($lang === 'ca') {
        Response::redirect('/faq', 301);
        return;
    }

    if (in_array($lang, \Config::supportedLocales(), true)) {
        // Redirect so the request re-enters bootstrap locale resolution,
        // ensuring LANG constant matches translations.
        Response::redirect('/faq?lang=' . $lang, 302);
        return;
    }

    // Unrecognised locale prefix — delegate to 404
    Response::error('Not Found', 404);
});

// Privacy page
$router->add('GET', '/privacy', static function (array $params): void {
    Privacy::render();
});

// Privacy page with locale prefix
$router->add('GET', '/{lang}/privacy', static function (array $params): void {
    $lang = $params['lang'] ?? '';

    // Catalan was removed as a locale — permanently 301 the legacy /ca/* URLs.
    if ($lang === 'ca') {
        Response::redirect('/privacy', 301);
        return;
    }

    if (in_array($lang, \Config::supportedLocales(), true)) {
        Response::redirect('/privacy?lang=' . $lang, 302);
        return;
    }

    Response::error('Not Found', 404);
});

// Cookies page
$router->add('GET', '/cookies', static function (array $params): void {
    Cookies::render();
});

// Cookies page with locale prefix
$router->add('GET', '/{lang}/cookies', static function (array $params): void {
    $lang = $params['lang'] ?? '';

    // Catalan was removed as a locale — permanently 301 the legacy /ca/* URLs.
    if ($lang === 'ca') {
        Response::redirect('/cookies', 301);
        return;
    }

    if (in_array($lang, \Config::supportedLocales(), true)) {
        Response::redirect('/cookies?lang=' . $lang, 302);
        return;
    }

    Response::error('Not Found', 404);
});

// Admin pages
$router->add('GET', '/pitocuixa', static function (array $params): void {
    AdminDashboard::render();
});

$router->add('GET', '/pitocuixa/login', static function (array $params): void {
    AdminLogin::render();
});

$router->add('GET', '/pitocuixa/products', static function (array $params): void {
    AdminProductsPage::render();
});

$router->add('GET', '/pitocuixa/categories', static function (array $params): void {
    AdminCategoriesPage::render();
});

$router->add('GET', '/pitocuixa/import-export', static function (array $params): void {
    AdminImportExportPage::render();
});

$router->add('GET', '/pitocuixa/settings', static function (array $params): void {
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
