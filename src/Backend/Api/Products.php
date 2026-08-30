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
use Pit\Cuixa\Backend\Auth\ClickRateLimiter;
use Pit\Cuixa\Backend\Services\ProductImage;

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
     * GET /api/products/popular
     *
     * Query param: ?limit= (optional, max 50, default 5)
     */
    public static function popular(int $limit = 5): void
    {
        $repo     = new ProductRepo();
        $products = $repo->popular($limit);

        Response::json([
            'data'  => array_map([self::class, 'localize'], $products),
            'error' => false,
        ]);
    }

    /**
     * POST /api/products/{id}/click
     *
     * RATE-5: windowed per-client-IP click rate limit (max 20 requests / 60s
     * sliding window) applied BEFORE the counter is touched. The key is derived
     * from REMOTE_ADDR only — proxy/X-Forwarded-For headers are untrusted, so no
     * header spoofing can widen the window. On deny the caller receives HTTP 429
     * with a Retry-After hint and the click is NOT recorded (no DB write).
     */
    public static function recordClick(int $productId): void
    {
        if ($productId <= 0) {
            Response::error('Invalid product ID', 400);
            return;
        }

        $limiter = new ClickRateLimiter();
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $result  = $limiter->allow("click:ip:{$ip}");

        if (!$result['allowed']) {
            // 429 + Retry-After; the click is skipped entirely.
            header('Retry-After: ' . $result['retryAfter']);
            Response::error('Too many requests', 429);
            return;
        }

        $repo    = new ProductRepo();
        $success = $repo->incrementClick($productId);

        if (!$success) {
            Response::error('Product not found or inactive', 404);
            return;
        }

        Response::json([
            'data'    => ['success' => true, 'product_id' => $productId],
            'error'   => false,
            'message' => 'Click recorded',
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
            'image_url'       => ProductImage::displayUrl($product['image_url'] ?? null),
            'last_shop_url'   => $product['last_shop_url'],
            'category_id'     => (int) $product['category_id'],
            'category_slug'   => $product['category_slug'],
            'category_name'   => $product["category_name_{$lang}"],
            'is_featured'     => (bool) $product['is_featured'],
            'sort_order'      => (int) $product['sort_order'],
            'clicks_count'    => (int) ($product['clicks_count'] ?? 0),
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
