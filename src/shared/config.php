<?php
/**
 * Pit o Cuixa — Application Configuration
 *
 * Reads environment variables (hand-rolled .env loader).
 * All config access via static Config methods — no globals.
 *
 * @package Pit\Cuixa\Shared
 */

declare(strict_types=1);

final class Config
{
    private static ?array $env = null;

    /**
     * Load .env file if present and not already loaded.
     */
    private static function load(): void
    {
        if (self::$env !== null) {
            return;
        }

        self::$env = [];

        $envPath = dirname(__DIR__, 2) . '/.env';

        if (!is_file($envPath) || !is_readable($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $key   = trim($parts[0]);
            $value = trim($parts[1]);

            // Strip surrounding quotes if present
            if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true)) {
                $quote = $value[0];
                if ($value[strlen($value) - 1] === $quote) {
                    $value = substr($value, 1, -1);
                }
            }

            self::$env[$key] = $value;

            // Also set as real env variable for getenv() access
            putenv("{$key}={$value}");
        }
    }

    /**
     * Get a value from .env or real environment.
     */
    private static function get(string $key, string $default = ''): string
    {
        self::load();

        return self::$env[$key] ?? (getenv($key) ?: $default);
    }

    /**
     * Absolute path to the SQLite database file.
     */
    public static function dbPath(): string
    {
        return self::get('DB_PATH', dirname(__DIR__, 2) . '/data/pitocuixa.db');
    }

    /**
     * Base site URL (no trailing slash).
     */
    public static function siteUrl(): string
    {
        return rtrim(self::get('SITE_URL', 'https://pitocuixa.es'), '/');
    }

    /**
     * Application environment: dev|prod|test.
     */
    public static function env(): string
    {
        return self::get('APP_ENV', 'prod');
    }

    /**
     * Session token lifetime in seconds (default: 8 hours).
     */
    public static function sessionLifetime(): int
    {
        return (int) self::get('SESSION_LIFETIME', '28800');
    }

    /**
     * Default locale (es|en).
     */
    public static function defaultLocale(): string
    {
        return self::get('DEFAULT_LOCALE', 'es');
    }

    /**
     * Supported locales.
     *
     * @return string[]
     */
    public static function supportedLocales(): array
    {
        return ['es', 'en'];
    }

    /**
     * Check if we are in development mode.
     */
    public static function isDev(): bool
    {
        return self::env() === 'dev';
    }

    /**
     * Check if we are in production mode.
     */
    public static function isProd(): bool
    {
        return self::env() === 'prod';
    }
}
