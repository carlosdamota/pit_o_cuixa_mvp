<?php
/**
 * Pit o Cuixa — Admin Categories Page Controller
 *
 * GET /admin/categories — Category list with edit controls.
 * Requires valid session cookie.
 *
 * @package Pit\Cuixa\Backend\Pages\Admin
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages\Admin;

use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Db\Repositories\Category as CategoryRepo;

class Categories
{
    /**
     * Render the admin categories management page.
     */
    public static function render(): void
    {
        $user   = Auth::requireSession();
        $repo   = new CategoryRepo();
        $categories = $repo->all();

        // Also fetch all categories (including inactive) for management
        $pdo        = \Pit\Cuixa\Backend\Db\Connection::get();
        $stmt       = $pdo->prepare('SELECT * FROM categories ORDER BY sort_order');
        $stmt->execute();
        $allCategories = $stmt->fetchAll();

        $meta = [
            'title'       => 'Categorías — Admin — ' . __('site.name'),
            'description' => __('site.description'),
            'canonical'   => \Config::siteUrl() . '/admin/categories',
            'index'       => false,
        ];

        $data = [
            'locale'     => LANG,
            'user'       => $user,
            'categories' => $allCategories,
            'csrf_token' => Auth::getCsrfToken(),
        ];

        \renderPage('admin/categories', $meta, $data);
    }
}
