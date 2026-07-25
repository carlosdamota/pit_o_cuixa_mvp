<?php
/**
 * Pit o Cuixa — Admin Products Page Controller
 *
 * GET /admin/products — Product list with edit/delete controls.
 * Requires valid session cookie.
 *
 * @package Pit\Cuixa\Backend\Pages\Admin
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages\Admin;

use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Db\Repositories\Product as ProductRepo;
use Pit\Cuixa\Backend\Db\Repositories\Category as CategoryRepo;

class Products
{
    /**
     * Render the admin products management page.
     */
    public static function render(): void
    {
        $user = Auth::requireSession();

        $productRepo  = new ProductRepo();
        $categoryRepo = new CategoryRepo();

        $page  = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $products   = $productRepo->all(null, $limit, $offset);
        $total      = $productRepo->count();
        $categories = $categoryRepo->all();

        $meta = [
            'title'       => 'Productos — Admin — ' . __('site.name'),
            'description' => __('site.description'),
            'canonical'   => \Config::siteUrl() . '/admin/products',
            'index'       => false,
        ];

        $data = [
            'locale'      => LANG,
            'user'        => $user,
            'products'    => $products,
            'categories'  => $categories,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => (int) ceil($total / $limit),
            'csrf_token'  => Auth::getCsrfToken(),
        ];

        \renderPage('admin/products', $meta, $data);
    }
}
