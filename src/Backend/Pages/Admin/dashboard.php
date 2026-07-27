<?php
/**
 * Pit o Cuixa — Admin Dashboard Page Controller
 *
 * GET /admin — Dashboard with stats overview.
 * Requires valid session cookie.
 *
 * @package Pit\Cuixa\Backend\Pages\Admin
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages\Admin;

use Pit\Cuixa\Backend\Auth\Auth;

class Dashboard
{
    /**
     * Render the admin dashboard.
     */
    public static function render(): void
    {
        $user = Auth::requireSession();

        // Gather stats
        $pdo  = \Pit\Cuixa\Backend\Db\Connection::get();

        // Total products
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM products WHERE is_active = 1');
        $stmt->execute();
        $totalProducts = (int) $stmt->fetch()['cnt'];

        // Total categories
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM categories WHERE is_active = 1');
        $stmt->execute();
        $totalCategories = (int) $stmt->fetch()['cnt'];

        // Featured products
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM products WHERE is_featured = 1 AND is_active = 1');
        $stmt->execute();
        $featuredProducts = (int) $stmt->fetch()['cnt'];

        // Products per category
        $stmt = $pdo->prepare(
            'SELECT c.name_es, c.name_en, COUNT(p.id) AS cnt
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
             WHERE c.is_active = 1
             GROUP BY c.id
             ORDER BY c.sort_order'
        );
        $stmt->execute();
        $perCategory = $stmt->fetchAll();

        $meta = [
            'title'       => 'Admin — ' . __('site.name'),
            'description' => __('site.description'),
            'canonical'   => \Config::siteUrl() . '/admin',
            'index'       => false,
        ];

        $data = [
            'locale'          => LANG,
            'user'            => $user,
            'total_products'  => $totalProducts,
            'total_categories'=> $totalCategories,
            'featured_products' => $featuredProducts,
            'per_category'    => $perCategory,
            'csrf_token'      => Auth::getCsrfToken(),
        ];

        \renderPage('admin/dashboard', $meta, $data);
    }
}
