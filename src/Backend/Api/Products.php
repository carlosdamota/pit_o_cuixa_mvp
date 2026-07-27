<?php
/**
 * Pit o Cuixa — Products API Controller
 *
 * Public read-only API endpoints for products and categories.
 * Every response uses the uniform JSON envelope.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Db\Repositories\Product as ProductRepo;
use Pit\Cuixa\Backend\Db\Repositories\Category as CategoryRepo;

class Products
{
    /**
     * GET /api/products
     *
     * Query param: ?id_category= (optional), ?limit= (optional, max 200)
     */
    public static function list(?int $categoryId = null, int $limit = 100): void
    {
        $repo     = new ProductRepo();
        $products = $repo->all($categoryId, $limit);

        Response::json([
            'data'  => array_map([self::class, 'localize'], $products),
            'error' => false,
        ]);
    }

    /**
     * GET /api/products/{slug}
     */
    public static function show(string $slug): void
    {
        if ($slug === '') {
            Response::error('Product slug is required', 400);
            return;
        }

        $repo    = new ProductRepo();
        $product = $repo->bySlug($slug);

        if ($product === null) {
            Response::error('Product not found', 404);
            return;
        }

        Response::json([
            'data'  => self::localize($product),
            'error' => false,
        ]);
    }

    /**
     * GET /api/categories
     */
    public static function categories(): void
    {
        $repo       = new CategoryRepo();
        $categories = $repo->all();

        Response::json([
            'data'  => array_map([self::class, 'localizeCategory'], $categories),
            'error' => false,
        ]);
    }

    /**
     * Localise a product row to the current locale.
     *
     * @param  array<string, mixed> $product  Raw DB row
     * @return array<string, mixed>
     */
    private static function localize(array $product): array
    {
        $lang = LANG;

        return [
            'id'              => (int) $product['id'],
            'slug'            => $product['slug'],
            'name'            => $product["name_{$lang}"],
            'description'     => $product["description_{$lang}"],
            'price'           => (float) $product['price'],
            'price_formatted' => sprintf('€%.2f', (float) $product['price']),
            'image_url'       => ($product['image_url'] ?? '') !== '' ? $product['image_url'] : null,
            'last_shop_url'   => $product['last_shop_url'],
            'category_id'     => (int) $product['category_id'],
            'category_slug'   => $product['category_slug'],
            'category_name'   => $product["category_name_{$lang}"],
            'is_featured'     => (bool) $product['is_featured'],
            'sort_order'      => (int) $product['sort_order'],
        ];
    }

    /**
     * Localise a category row to the current locale.
     *
     * @param  array<string, mixed> $category  Raw DB row
     * @return array<string, mixed>
     */
    private static function localizeCategory(array $category): array
    {
        $lang = LANG;

        return [
            'id'         => (int) $category['id'],
            'slug'       => $category['slug'],
            'name'       => $category["name_{$lang}"],
            'sort_order' => (int) $category['sort_order'],
        ];
    }
}
