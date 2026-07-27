<?php
/**
 * Pit o Cuixa — Admin Import/Export Page Controller
 *
 * GET /admin/import-export — Upload CSV, download products/categories export.
 * Requires valid session cookie.
 *
 * @package Pit\Cuixa\Backend\Pages\Admin
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages\Admin;

use Pit\Cuixa\Backend\Auth\Auth;

class ImportExport
{
    /**
     * Render the admin import/export page.
     */
    public static function render(): void
    {
        $user = Auth::requireSession();

        $meta = [
            'title'       => 'Importar / Exportar — Admin — ' . __('site.name'),
            'description' => __('site.description'),
            'canonical'   => \Config::siteUrl() . '/admin/import-export',
            'index'       => false,
        ];

        $data = [
            'locale'     => LANG,
            'user'       => $user,
            'csrf_token' => Auth::getCsrfToken(),
        ];

        \renderPage('admin/import-export', $meta, $data);
    }
}
