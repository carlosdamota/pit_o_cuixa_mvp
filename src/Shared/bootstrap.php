<?php
/**
 * Pit o Cuixa — Application Bootstrap
 *
 * Loaded once by public/index.php on every request.
 * Sets up autoload, error handling, config, and i18n.
 *
 * @package Pit\Cuixa\Shared
 */

declare(strict_types=1);

// ── 1. Load Config ──────────────────────────────────────────────────────
require_once __DIR__ . '/config.php';

// ── 2. Error Reporting ──────────────────────────────────────────────────
if (Config::isDev()) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../../data/error.log');
}

// ── 3. Simple PSR-4-like Autoloader ────────────────────────────────────
// Maps namespace prefix → base directory. No Composer dependency.
// Supports: Pit\Cuixa\Backend\*, Pit\Cuixa\Frontend\*, Pit\Cuixa\Shared\*

spl_autoload_register(static function (string $class): void {
    $prefix   = 'Pit\\Cuixa\\';
    $baseDir  = dirname(__DIR__) . '/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file          = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

// ── 4. Locale Detection ─────────────────────────────────────────────────
// Priority: ?lang= query param → lang cookie → Config::defaultLocale()
$locale = Config::defaultLocale();

if (isset($_GET['lang'])) {
    $requested = $_GET['lang'];

    if (in_array($requested, Config::supportedLocales(), true)) {
        $locale = $requested;
        // Persist choice in cookie for subsequent requests (1 year)
        setcookie('lang', $locale, time() + 365 * 86400, '/', '', true, true);
    }
    // Invalid ?lang= falls back to default — no cookie set
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], Config::supportedLocales(), true)) {
    $locale = $_COOKIE['lang'];
}

// Make locale available globally (used by __() and templates)
define('LANG', $locale);

// ── 5. Load i18n with Fallback Chain ────────────────────────────────────
// Each locale file now returns a pure data array (no function definitions).
// Fallback order: requested locale → CA → EN
$localeStrings = require __DIR__ . '/i18n/' . LANG . '.php';
$caStrings     = require __DIR__ . '/i18n/ca.php';
$enStrings     = require __DIR__ . '/i18n/en.php';

$GLOBALS['_translations'] = array_merge($enStrings, $caStrings, $localeStrings);

/**
 * Translate a key into the current locale.
 * Falls back through: requested locale → CA → EN → key itself.
 *
 * @param  string $key     Translation key (dot-notation: section.key)
 * @param  array  $params  Optional sprintf parameters
 * @return string
 */
function __(string $key, array $params = []): string
{
    $text = $GLOBALS['_translations'][$key] ?? $key;

    if ($params !== []) {
        $text = sprintf($text, ...$params);
    }

    return $text;
}

// ── 6. Set timezone ─────────────────────────────────────────────────────
date_default_timezone_set('Europe/Madrid');
