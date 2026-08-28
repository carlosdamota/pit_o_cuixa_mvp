<?php
/**
 * Pit o Cuixa — Admin Settings API Controller
 *
 * GET  /api/admin/settings — Read all settings
 * PUT  /api/admin/settings — Update a whitelisted setting
 *
 * All endpoints require Bearer token auth.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Db\Repositories\Settings;

class AdminSettings
{
    /**
     * Whitelist of keys that can be updated via PUT.
     */
    private const ALLOWED_KEYS = [
        'menu_slider_enabled',
        'company_address',
        'company_phone',
        'company_whatsapp',
    ];

    /**
     * Valid values for enum-like keys.
     */
    private const ALLOWED_VALUES = [
        'menu_slider_enabled' => ['0', '1'],
    ];

    /**
     * GET /api/admin/settings — Return all settings + image count.
     */
    public static function get(): void
    {
        Auth::requireToken();

        Settings::ensureSchema();

        Response::json([
            'error' => false,
            'data'  => self::payload(),
        ]);
    }

    /**
     * PUT /api/admin/settings — Update whitelisted settings.
     *
     * Accepts a single {key: value} pair or several at once
     * (e.g. the company form sends address + both phones together).
     * All-or-nothing: nothing is written if any key fails validation.
     */
    public static function update(): void
    {
        Auth::requireToken();
        Auth::validateCsrfToken();

        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput ?: '', true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        if (!is_array($input) || empty($input)) {
            Response::error('Invalid JSON body', 400);
            return;
        }

        // Validate every key BEFORE writing anything (atomic save).
        $errors = [];
        $clean  = [];

        foreach ($input as $key => $value) {
            $key   = (string) $key;
            $value = is_scalar($value) ? trim((string) $value) : '';

            $error = self::validateValue($key, $value);

            if ($error !== null) {
                $errors[] = $error;
                continue;
            }

            $clean[$key] = $value;
        }

        if ($errors !== []) {
            Response::json([
                'error'  => true,
                'errors' => $errors,
                'code'   => 422,
            ], 422);
            return;
        }

        Settings::ensureSchema();

        foreach ($clean as $key => $value) {
            Settings::set($key, $value);
        }

        Response::json([
            'error' => false,
            'data'  => self::payload(),
        ]);
    }

    /**
     * Validate a single key/value pair against the whitelist and per-key rules.
     *
     * @return string|null Error message, or null when valid.
     */
    private static function validateValue(string $key, string $value): ?string
    {
        if (!in_array($key, self::ALLOWED_KEYS, true)) {
            return "Unknown or disallowed key: {$key}";
        }

        if (isset(self::ALLOWED_VALUES[$key])) {
            if (!in_array($value, self::ALLOWED_VALUES[$key], true)) {
                return 'Invalid value for ' . $key . '. Allowed: ' . implode(', ', self::ALLOWED_VALUES[$key]);
            }

            return null;
        }

        switch ($key) {
            case 'company_phone':
                if (!self::isValidPhone($value)) {
                    return 'Invalid phone format for company_phone. Use digits, spaces and an optional leading + (6-20 chars), e.g. +34 977 64 20 10';
                }
                return null;

            case 'company_whatsapp':
                if ($value === '') {
                    return null; // Optional — falls back to company_phone
                }
                if (!self::isValidPhone($value)) {
                    return 'Invalid phone format for company_whatsapp. Use digits, spaces and an optional leading + (6-20 chars), e.g. +34 612 34 56 78';
                }
                return null;

            case 'company_address':
                if (mb_strlen($value) > 200) {
                    return 'Invalid value for company_address. Maximum 200 characters';
                }
                return null;
        }

        return null;
    }

    /**
     * Phone format: optional leading +, digits and spaces, 6-20 chars total.
     */
    private static function isValidPhone(string $value): bool
    {
        return preg_match('/^\+?[0-9][0-9 ]{4,18}[0-9]$/', $value) === 1;
    }

    /**
     * Build the response payload (settings + image count).
     *
     * @return array<string, mixed>
     */
    private static function payload(): array
    {
        $all = Settings::all();

        return [
            'menu_slider_enabled' => $all['menu_slider_enabled'] ?? '0',
            'company_address'     => $all['company_address'] ?? '',
            'company_phone'       => Settings::companyPhone(),
            'company_whatsapp'    => $all['company_whatsapp'] ?? '',
            'image_count'         => self::countSliderImages(),
        ];
    }

    /**
     * Count images in the menu-slider directory.
     *
     * @return int
     */
    private static function countSliderImages(): int
    {
        $pattern = \Config::publicDir() . '/img/menu-slider/*.{jpg,jpeg,png,webp}';
        $files   = glob($pattern, GLOB_BRACE);

        return is_array($files) ? count($files) : 0;
    }
}
