<?php
/**
 * Pit o Cuixa — Menu API Controller
 *
 * GET /api/menu — returns all products grouped by category.
 * Frontend JS consumes this for dynamic filtering.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Db\Repositories\Product as ProductRepo;
use Pit\Cuixa\Backend\Db\Repositories\Category as CategoryRepo;

class Menu
{
    /**
     * GET /api/menu — grouped by category, localised.
     */
    public static function grouped(): void
    {
        $catRepo  = new CategoryRepo();
        $prodRepo = new ProductRepo();

        $categories = $catRepo->all();
        $products   = $prodRepo->all();
        $lang       = LANG;

        $groups = [];

        foreach ($categories as $category) {
            // Filter products belonging to this category
            $catProducts = array_values(
                array_filter(
                    $products,
                    fn(array $p): bool => (int) $p['category_id'] === (int) $category['id']
                )
            );

            // Skip empty categories
            if ($catProducts === []) {
                continue;
            }

            $groups[] = [
                'id'         => (int) $category['id'],
                'slug'       => $category['slug'],
                'name'       => $category["name_{$lang}"],
                'sort_order' => (int) $category['sort_order'],
                'products'   => array_map(
                    function (array $p) use ($lang): array {
                        return [
                            'id'              => (int) $p['id'],
                            'slug'            => $p['slug'],
                            'name'            => $p["name_{$lang}"],
                            'description'     => $p["description_{$lang}"],
                            'price'           => (float) $p['price'],
                            'price_formatted' => sprintf('€%.2f', (float) $p['price']),
                            'image_url'       => ($p['image_url'] ?? '') !== '' ? $p['image_url'] : null,
                            'last_shop_url'   => $p['last_shop_url'],
                            'is_featured'     => (bool) $p['is_featured'],
                            'sort_order'      => (int) $p['sort_order'],
                        ];
                    },
                    $catProducts
                ),
            ];
        }

        Response::json([
            'data'  => $groups,
            'error' => false,
        ]);
    }
}
