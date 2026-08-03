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
     * Generate a clean URL-friendly slug from string.
     */
    private static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        if (function_exists('iconv')) {
            $trans = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
            if ($trans !== false) {
                $text = $trans;
            }
        }
        $text = preg_replace('~[^-\w]+~', '', (string)$text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower((string)$text);

        return !empty($text) ? $text : 'producto-' . time();
    }

    /**
     * Translate missing language fields using DeepL if auto-translate is active.
     */
    private static function resolveTranslations(array &$input): void
    {
        $nameEs = trim((string) ($input['name_es'] ?? $input['name'] ?? ''));
        $descEs = trim((string) ($input['description_es'] ?? $input['description'] ?? ''));

        $nameEn = trim((string) ($input['name_en'] ?? ''));
        $nameCa = trim((string) ($input['name_ca'] ?? ''));
        $nameUk = trim((string) ($input['name_uk'] ?? ''));

        $descEn = trim((string) ($input['description_en'] ?? ''));
        $descCa = trim((string) ($input['description_ca'] ?? ''));
        $descUk = trim((string) ($input['description_uk'] ?? ''));

        $autoTranslate = !isset($input['auto_translate']) || !empty($input['auto_translate']);

        if ($autoTranslate && !empty($nameEs)) {
            try {
                $deepl = new \Pit\Cuixa\Backend\Services\DeepLService();

                // Translate title
                if (empty($nameEn)) {
                    $t = $deepl->translate($nameEs, 'en', 'ES');
                    $nameEn = $t[0] ?? '';
                }
                if (empty($nameCa)) {
                    $t = $deepl->translate($nameEs, 'ca', 'ES');
                    $nameCa = $t[0] ?? '';
                }
                if (empty($nameUk)) {
                    $t = $deepl->translate($nameEs, 'uk', 'ES');
                    $nameUk = $t[0] ?? '';
                }

                // Translate description
                if (!empty($descEs)) {
                    if (empty($descEn)) {
                        $t = $deepl->translate($descEs, 'en', 'ES');
                        $descEn = $t[0] ?? '';
                    }
                    if (empty($descCa)) {
                        $t = $deepl->translate($descEs, 'ca', 'ES');
                        $descCa = $t[0] ?? '';
                    }
                    if (empty($descUk)) {
                        $t = $deepl->translate($descEs, 'uk', 'ES');
                        $descUk = $t[0] ?? '';
                    }
                }
            } catch (\Throwable $e) {
                error_log('[AdminProducts] DeepL translation fallback warning: ' . $e->getMessage());
            }
        }

        // Fallback for any remaining empty language fields
        $input['name_es']        = $nameEs;
        $input['name_en']        = !empty($nameEn) ? $nameEn : $nameEs;
        $input['name_ca']        = !empty($nameCa) ? $nameCa : $nameEs;
        $input['name_uk']        = !empty($nameUk) ? $nameUk : $nameEs;

        $input['description_es'] = $descEs;
        $input['description_en'] = !empty($descEn) ? $descEn : $descEs;
        $input['description_ca'] = !empty($descCa) ? $descCa : $descEs;
        $input['description_uk'] = !empty($descUk) ? $descUk : $descEs;
    }

    /**
     * Validate input data for product create/update.
     *
     * @param  array $input
     * @return array{ok: bool, errors: string[]}
     */
    private static function validate(array &$input): array
    {
        $errors = [];

        // Support 'name' alias as name_es
        if (empty($input['name_es']) && !empty($input['name'])) {
            $input['name_es'] = $input['name'];
        }

        if (empty($input['name_es'])) {
            $errors[] = 'El título/nombre del producto es obligatorio';
        }

        // Auto-generate slug if empty
        if (empty($input['slug']) && !empty($input['name_es'])) {
            $input['slug'] = self::slugify($input['name_es']);
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

        self::resolveTranslations($input);

        $menuDataJson = null;
        if (isset($input['menu_data']) && (is_array($input['menu_data']) || is_string($input['menu_data']))) {
            $menuDataJson = is_array($input['menu_data']) ? json_encode($input['menu_data'], JSON_UNESCAPED_UNICODE) : $input['menu_data'];
        }

        $pdo  = \Pit\Cuixa\Backend\Db\Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, slug, name_es, name_en, name_ca, name_uk, description_es, description_en, description_ca, description_uk, price, image_url, last_shop_url, sort_order, is_active, is_featured, is_dine_in, is_delivery, source, type, menu_data)
             VALUES (:category_id, :slug, :name_es, :name_en, :name_ca, :name_uk, :description_es, :description_en, :description_ca, :description_uk, :price, :image_url, :last_shop_url, :sort_order, :is_active, :is_featured, :is_dine_in, :is_delivery, :source, :type, :menu_data)'
        );

        $stmt->execute([
            ':category_id'    => (int) ($input['category_id'] ?? 0),
            ':slug'           => trim((string) ($input['slug'] ?? '')),
            ':name_es'        => trim((string) ($input['name_es'] ?? '')),
            ':name_en'        => trim((string) ($input['name_en'] ?? '')),
            ':name_ca'        => trim((string) ($input['name_ca'] ?? '')),
            ':name_uk'        => trim((string) ($input['name_uk'] ?? '')),
            ':description_es' => trim((string) ($input['description_es'] ?? '')),
            ':description_en' => trim((string) ($input['description_en'] ?? '')),
            ':description_ca' => trim((string) ($input['description_ca'] ?? '')),
            ':description_uk' => trim((string) ($input['description_uk'] ?? '')),
            ':price'          => (float) ($input['price'] ?? 0),
            ':image_url'      => trim((string) ($input['image_url'] ?? '')),
            ':last_shop_url'  => trim((string) ($input['last_shop_url'] ?? '')),
            ':sort_order'     => (int) ($input['sort_order'] ?? 0),
            ':is_active'      => !empty($input['is_active']) ? 1 : 0,
            ':is_featured'    => !empty($input['is_featured']) ? 1 : 0,
            ':is_dine_in'     => !isset($input['is_dine_in']) || !empty($input['is_dine_in']) ? 1 : 0,
            ':is_delivery'    => !isset($input['is_delivery']) || !empty($input['is_delivery']) ? 1 : 0,
            ':source'         => trim((string) ($input['source'] ?? 'manual')),
            ':type'           => trim((string) ($input['type'] ?? 'simple')),
            ':menu_data'      => $menuDataJson,
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

        $repo = new ProductRepo();
        $serialized = $product ? $repo->serialize($product) : ['id' => $newId];

        Response::json([
            'error' => false,
            'data'  => $serialized,
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

        self::resolveTranslations($input);

        $pdo = \Pit\Cuixa\Backend\Db\Connection::get();

        // Check product exists
        $check = $pdo->prepare('SELECT id FROM products WHERE id = :id');
        $check->execute([':id' => $id]);

        if ($check->fetch() === false) {
            Response::error('Product not found', 404);
            return;
        }

        $menuDataJson = null;
        if (isset($input['menu_data']) && (is_array($input['menu_data']) || is_string($input['menu_data']))) {
            $menuDataJson = is_array($input['menu_data']) ? json_encode($input['menu_data'], JSON_UNESCAPED_UNICODE) : $input['menu_data'];
        }

        $stmt = $pdo->prepare(
            'UPDATE products SET
                category_id    = :category_id,
                slug           = :slug,
                name_es        = :name_es,
                name_en        = :name_en,
                name_ca        = :name_ca,
                name_uk        = :name_uk,
                description_es = :description_es,
                description_en = :description_en,
                description_ca = :description_ca,
                description_uk = :description_uk,
                price          = :price,
                image_url      = :image_url,
                last_shop_url  = :last_shop_url,
                sort_order     = :sort_order,
                is_active      = :is_active,
                is_featured    = :is_featured,
                is_dine_in     = :is_dine_in,
                is_delivery    = :is_delivery,
                source         = :source,
                type           = :type,
                menu_data      = :menu_data,
                updated_at     = datetime(\'now\')
             WHERE id = :id'
        );

        $stmt->execute([
            ':id'              => $id,
            ':category_id'     => (int) ($input['category_id'] ?? 0),
            ':slug'            => trim((string) ($input['slug'] ?? '')),
            ':name_es'         => trim((string) ($input['name_es'] ?? '')),
            ':name_en'         => trim((string) ($input['name_en'] ?? '')),
            ':name_ca'         => trim((string) ($input['name_ca'] ?? '')),
            ':name_uk'         => trim((string) ($input['name_uk'] ?? '')),
            ':description_es'  => trim((string) ($input['description_es'] ?? '')),
            ':description_en'  => trim((string) ($input['description_en'] ?? '')),
            ':description_ca'  => trim((string) ($input['description_ca'] ?? '')),
            ':description_uk'  => trim((string) ($input['description_uk'] ?? '')),
            ':price'           => (float) ($input['price'] ?? 0),
            ':image_url'       => trim((string) ($input['image_url'] ?? '')),
            ':last_shop_url'   => trim((string) ($input['last_shop_url'] ?? '')),
            ':sort_order'      => (int) ($input['sort_order'] ?? 0),
            ':is_active'       => !empty($input['is_active']) ? 1 : 0,
            ':is_featured'     => !empty($input['is_featured']) ? 1 : 0,
            ':is_dine_in'      => !isset($input['is_dine_in']) || !empty($input['is_dine_in']) ? 1 : 0,
            ':is_delivery'     => !isset($input['is_delivery']) || !empty($input['is_delivery']) ? 1 : 0,
            ':source'          => trim((string) ($input['source'] ?? 'manual')),
            ':type'            => trim((string) ($input['type'] ?? 'simple')),
            ':menu_data'       => $menuDataJson,
        ]);

        // Fetch updated product with category info
        $stmt2 = $pdo->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en
             FROM products p
             JOIN categories c ON p.category_id = c.id
             WHERE p.id = :id'
        );
        $stmt2->execute([':id' => $id]);
        $product = $stmt2->fetch();

        $repo = new ProductRepo();
        $serialized = $product ? $repo->serialize($product) : ['id' => $id];

        Response::json([
            'error' => false,
            'data'  => $serialized,
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
