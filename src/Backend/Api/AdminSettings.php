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
    private const ALLOWED_KEYS = ['menu_slider_enabled'];

    /**
     * Valid values per whitelisted key.
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

        $all        = Settings::all();
        $imageCount = self::countSliderImages();

        Response::json([
            'error' => false,
            'data'  => [
                'menu_slider_enabled' => $all['menu_slider_enabled'] ?? '0',
                'image_count'         => $imageCount,
            ],
        ]);
    }

    /**
     * PUT /api/admin/settings — Update a whitelisted setting.
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

        // Only process one key at a time (expects {key: value})
        $keys = array_keys($input);
        $key  = $keys[0] ?? '';

        if (!in_array($key, self::ALLOWED_KEYS, true)) {
            Response::json([
                'error'  => true,
                'errors' => ["Unknown or disallowed key: {$key}"],
                'code'   => 400,
            ], 400);
            return;
        }

        $value = (string) $input[$key];

        // Validate value against whitelist
        if (isset(self::ALLOWED_VALUES[$key]) && !in_array($value, self::ALLOWED_VALUES[$key], true)) {
            Response::json([
                'error'  => true,
                'errors' => ["Invalid value for {$key}. Allowed: " . implode(', ', self::ALLOWED_VALUES[$key])],
                'code'   => 422,
            ], 422);
            return;
        }

        Settings::ensureSchema();
        Settings::set($key, $value);

        $all        = Settings::all();
        $imageCount = self::countSliderImages();

        Response::json([
            'error' => false,
            'data'  => [
                'menu_slider_enabled' => $all['menu_slider_enabled'] ?? '0',
                'image_count'         => $imageCount,
            ],
        ]);
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
