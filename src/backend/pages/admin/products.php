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

        $products   = $productRepo->all(null, 500);
        $categories = $categoryRepo->all();

        $meta = [
            'title'       => 'Productos — Admin — ' . __('site.name'),
            'description' => __('site.description'),
            'canonical'   => \Config::siteUrl() . '/admin/products',
            'index'       => false,
        ];

        $data = [
            'locale'     => LANG,
            'user'       => $user,
            'products'   => $products,
            'categories' => $categories,
            'csrf_token' => Auth::getCsrfToken(),
        ];

        \renderPage('admin/products', $meta, $data);
    }
}
