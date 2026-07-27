<?php
/**
 * Pit o Cuixa — Admin Products API Controller
 *
 * POST   /api/admin/products       — Create product
 * PUT    /api/admin/products/{id}  — Update product
 * DELETE /api/admin/products/{id}  — Delete (deactivate) product
 *
 * All endpoints require Bearer token auth.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Db\Repositories\Product as ProductRepo;

class AdminProducts
{
    /**
     * GET /api/admin/products — List products with pagination.
     * Query params: page (1-based), limit (max 100).
     */
    public static function list(): void
    {
        Auth::requireToken();

        $page  = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $repo     = new ProductRepo();
        $products = $repo->all(null, $limit, $offset);
        $total    = $repo->count();
        $totalPages = (int) ceil($total / $limit);

        Response::json([
            'error'       => false,
            'data'        => $products,
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => $totalPages,
        ]);
    }
    /**
     * Validate input data for product create/update.
     *
     * @param  array $input
     * @return array{ok: bool, errors: string[]}
     */
    private static function validate(array $input): array
    {
        $errors = [];

        // Required fields
        if (empty($input['name_es'])) {
            $errors[] = 'name_es is required';
        }
        if (empty($input['name_en'])) {
            $errors[] = 'name_en is required';
        }
        if (empty($input['slug'])) {
            $errors[] = 'slug is required';
        }
        if (empty($input['category_id'])) {
            $errors[] = 'category_id is required';
        }

        // Validate slug format
        if (!empty($input['slug']) && !preg_match('/^[a-z0-9-]+$/', $input['slug'])) {
            $errors[] = 'slug must contain only lowercase letters, numbers, and hyphens';
        }

        // Validate price
        if (isset($input['price'])) {
            if (!is_numeric($input['price']) || (float) $input['price'] < 0) {
                $errors[] = 'price must be a non-negative number';
            }
        }

        // Validate last_shop_url scheme (prevents javascript: XSS)
        if (!empty($input['last_shop_url'])) {
            $url = trim((string) $input['last_shop_url']);
            if (!preg_match('#^https?://#i', $url)) {
                $errors[] = 'last_shop_url must start with https:// or http://';
            }
        }

        // Validate image_url scheme (prevents javascript: XSS)
        if (!empty($input['image_url'])) {
            $imageUrl = trim((string) $input['image_url']);
            if (!preg_match('#^https?://#i', $imageUrl)) {
                $errors[] = 'image_url must start with https:// or http://';
            }
        }

        return [
            'ok'     => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * POST /api/admin/products — Create a new product.
     */
    public static function create(): void
    {
        Auth::requireToken();
        Auth::validateCsrfToken();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            Response::error('Invalid JSON body', 400);
            return;
        }

        $validation = self::validate($input);

        if (!$validation['ok']) {
            Response::json([
                'error'  => true,
                'errors' => $validation['errors'],
                'code'   => 422,
            ], 422);
            return;
        }

        $pdo  = \Pit\Cuixa\Backend\Db\Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, slug, name_es, name_en, description_es, description_en, price, image_url, last_shop_url, sort_order, is_active, is_featured)
             VALUES (:category_id, :slug, :name_es, :name_en, :description_es, :description_en, :price, :image_url, :last_shop_url, :sort_order, :is_active, :is_featured)'
        );

        $stmt->execute([
            ':category_id'    => (int) ($input['category_id'] ?? 0),
            ':slug'           => trim((string) ($input['slug'] ?? '')),
            ':name_es'        => trim((string) ($input['name_es'] ?? '')),
            ':name_en'        => trim((string) ($input['name_en'] ?? '')),
            ':description_es' => trim((string) ($input['description_es'] ?? '')),
            ':description_en' => trim((string) ($input['description_en'] ?? '')),
            ':price'          => (float) ($input['price'] ?? 0),
            ':image_url'      => trim((string) ($input['image_url'] ?? '')),
            ':last_shop_url'  => trim((string) ($input['last_shop_url'] ?? '')),
            ':sort_order'     => (int) ($input['sort_order'] ?? 0),
            ':is_active'      => !empty($input['is_active']) ? 1 : 0,
            ':is_featured'    => !empty($input['is_featured']) ? 1 : 0,
        ]);

        $newId = (int) $pdo->lastInsertId();

        // Fetch the created product with category info
        $pdo2  = \Pit\Cuixa\Backend\Db\Connection::get();
        $stmt2 = $pdo2->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en
             FROM products p
             JOIN categories c ON p.category_id = c.id
             WHERE p.id = :id'
        );
        $stmt2->execute([':id' => $newId]);
        $product = $stmt2->fetch();

        // Serialize types for JSON
        if ($product) {
            $product['id']          = (int) $product['id'];
            $product['category_id'] = (int) $product['category_id'];
            $product['price']       = (float) $product['price'];
            $product['sort_order']  = (int) $product['sort_order'];
            $product['is_active']   = (bool) $product['is_active'];
            $product['is_featured'] = (bool) $product['is_featured'];
        }

        Response::json([
            'error' => false,
            'data'  => $product ?: ['id' => $newId],
        ], 201);
    }

    /**
     * PUT /api/admin/products/{id} — Update an existing product.
     */
    public static function update(int $id): void
    {
        Auth::requireToken();
        Auth::validateCsrfToken();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            Response::error('Invalid JSON body', 400);
            return;
        }

        $validation = self::validate($input);

        if (!$validation['ok']) {
            Response::json([
                'error'  => true,
                'errors' => $validation['errors'],
                'code'   => 422,
            ], 422);
            return;
        }

        $pdo = \Pit\Cuixa\Backend\Db\Connection::get();

        // Check product exists
        $check = $pdo->prepare('SELECT id FROM products WHERE id = :id');
        $check->execute([':id' => $id]);

        if ($check->fetch() === false) {
            Response::error('Product not found', 404);
            return;
        }

        $stmt = $pdo->prepare(
            'UPDATE products SET
                category_id    = :category_id,
                slug           = :slug,
                name_es        = :name_es,
                name_en        = :name_en,
                description_es = :description_es,
                description_en = :description_en,
                price          = :price,
                image_url      = :image_url,
                last_shop_url  = :last_shop_url,
                sort_order     = :sort_order,
                is_active      = :is_active,
                is_featured    = :is_featured,
                updated_at     = datetime(\'now\')
             WHERE id = :id'
        );

        $stmt->execute([
            ':id'              => $id,
            ':category_id'     => (int) ($input['category_id'] ?? 0),
            ':slug'            => trim((string) ($input['slug'] ?? '')),
            ':name_es'         => trim((string) ($input['name_es'] ?? '')),
            ':name_en'         => trim((string) ($input['name_en'] ?? '')),
            ':description_es'  => trim((string) ($input['description_es'] ?? '')),
            ':description_en'  => trim((string) ($input['description_en'] ?? '')),
            ':price'           => (float) ($input['price'] ?? 0),
            ':image_url'       => trim((string) ($input['image_url'] ?? '')),
            ':last_shop_url'   => trim((string) ($input['last_shop_url'] ?? '')),
            ':sort_order'      => (int) ($input['sort_order'] ?? 0),
            ':is_active'       => !empty($input['is_active']) ? 1 : 0,
            ':is_featured'     => !empty($input['is_featured']) ? 1 : 0,
        ]);

        // Fetch updated product with category info
        $stmt2 = $pdo->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en
             FROM products p
             JOIN categories c ON p.category_id = c.id
             WHERE p.id = :id'
        );
        $stmt2->execute([':id' => $id]);
        $updated = $stmt2->fetch();

        // Serialize types for JSON
        if ($updated) {
            $updated['id']          = (int) $updated['id'];
            $updated['category_id'] = (int) $updated['category_id'];
            $updated['price']       = (float) $updated['price'];
            $updated['sort_order']  = (int) $updated['sort_order'];
            $updated['is_active']   = (bool) $updated['is_active'];
            $updated['is_featured'] = (bool) $updated['is_featured'];
        }

        Response::json([
            'error' => false,
            'data'  => $updated ?: ['id' => $id, 'updated' => true],
        ]);
    }

    /**
     * DELETE /api/admin/products/{id} — Deactivate a product (soft delete).
     */
    public static function delete(int $id): void
    {
        Auth::requireToken();
        Auth::validateCsrfToken();

        $pdo = \Pit\Cuixa\Backend\Db\Connection::get();

        $check = $pdo->prepare('SELECT id FROM products WHERE id = :id');
        $check->execute([':id' => $id]);

        if ($check->fetch() === false) {
            Response::error('Product not found', 404);
            return;
        }

        $stmt = $pdo->prepare(
            'UPDATE products SET is_active = 0, updated_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        Response::json([
            'error' => false,
            'data'  => ['id' => $id, 'deleted' => true],
        ]);
    }
}
