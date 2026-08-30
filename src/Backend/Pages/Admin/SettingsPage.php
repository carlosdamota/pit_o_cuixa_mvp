<?php
/**
 * Pit o Cuixa — Admin Settings Page Controller
 *
 * GET /admin/settings — Settings page with slider toggle UI.
 * Requires valid session cookie.
 *
 * @package Pit\Cuixa\Backend\Pages\Admin
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages\Admin;

use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Db\Repositories\Settings;

class SettingsPage
{
    /**
     * Render the admin settings page.
     */
    public static function render(): void
    {
        $user = Auth::requireSession();

        Settings::ensureSchema();

        $all        = Settings::all();
        $imageCount = self::countSliderImages();

        $meta = [
            'title'       => 'Settings — ' . __('site.name'),
            'description' => __('site.description'),
            'canonical'   => \Config::siteUrl() . '/pitocuixa/settings',
            'index'       => false,
        ];

        $data = [
            'locale'                 => LANG,
            'user'                   => $user,
            'menu_slider_enabled'    => $all['menu_slider_enabled'] ?? '0',
            'company_address'        => $all['company_address'] ?? '',
            'company_phone'          => Settings::companyPhone(),
            'company_whatsapp'       => $all['company_whatsapp'] ?? '',
            'image_count'            => $imageCount,
            'csrf_token'             => Auth::getCsrfToken(),
        ];

        \renderPage('admin/settings', $meta, $data);
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
