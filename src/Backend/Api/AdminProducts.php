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
     * Apply the cross-type slug rule before persisting.
     *
     * The slug is the product's identity — and the base of its local image
     * file /img/pic/{slug}.webp — so two products can never share one
     * (UNIQUE constraint). When the proposed slug collides with a product of
     * a DIFFERENT type, the incoming product is renamed with its own type
     * suffix (-local for menus, -delivery for simple products) and persisted
     * under that slug. When it collides with the SAME type, the slug is a
     * validation error (422) instead of a silent UNIQUE constraint crash.
     * $excludeId lets an update keep its own slug without self-colliding.
     *
     * @param  array $input      Product payload (mutated: 'slug' may be renamed)
     * @param  int   $excludeId  Product id to ignore (self on update, 0 on create)
     * @return bool True when the slug is safe to persist; false when the
     *              response was already sent (same-type collision, 422)
     */
    private static function applySlugRule(array &$input, int $excludeId = 0): bool
    {
        $slug = trim((string) ($input['slug'] ?? ''));
        $type = trim((string) ($input['type'] ?? 'simple'));

        if ($slug === '') {
            return true; // validated elsewhere (required when a name is present)
        }

        $resolved = (new ProductRepo())->resolveSlugCollision($slug, $type, $excludeId);

        if ($resolved === null) {
            Response::json([
                'error'  => true,
                'errors' => ['El slug "' . $slug . '" ya está en uso por otro producto del mismo tipo'],
                'code'   => 422,
            ], 422);
            return false;
        }

        $input['slug'] = $resolved;

        return true;
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

        // Slug collisions with a product of the same type are a 422 here; a
        // collision with a product of a different type renames this one with
        // its own type suffix (see applySlugRule()).
        if (!self::applySlugRule($input)) {
            return;
        }

        $menuDataJson = null;
        if (isset($input['menu_data']) && (is_array($input['menu_data']) || is_string($input['menu_data']))) {
            $menuDataJson = is_array($input['menu_data']) ? json_encode($input['menu_data'], JSON_UNESCAPED_UNICODE) : $input['menu_data'];
        }

        // Persist through the centralized Product repository — the single write
        // path for the products table — instead of inline SQL. The values below
        // mirror exactly what the previous inline INSERT wrote, so the stored
        // row is unchanged.
        $repo = new ProductRepo();
        $repo->insert([
            'category_id'    => (int) ($input['category_id'] ?? 0),
            'slug'           => trim((string) ($input['slug'] ?? '')),
            'name_es'        => trim((string) ($input['name_es'] ?? '')),
            'name_en'        => trim((string) ($input['name_en'] ?? '')),
            'name_ca'        => trim((string) ($input['name_ca'] ?? '')),
            'name_uk'        => trim((string) ($input['name_uk'] ?? '')),
            'description_es' => trim((string) ($input['description_es'] ?? '')),
            'description_en' => trim((string) ($input['description_en'] ?? '')),
            'description_ca' => trim((string) ($input['description_ca'] ?? '')),
            'description_uk' => trim((string) ($input['description_uk'] ?? '')),
            'price'          => (float) ($input['price'] ?? 0),
            'image_url'      => trim((string) ($input['image_url'] ?? '')),
            'last_shop_url'  => trim((string) ($input['last_shop_url'] ?? '')),
            'sort_order'     => (int) ($input['sort_order'] ?? 0),
            'is_active'      => !empty($input['is_active']) ? 1 : 0,
            'is_featured'    => !empty($input['is_featured']) ? 1 : 0,
            'is_dine_in'     => !isset($input['is_dine_in']) || !empty($input['is_dine_in']) ? 1 : 0,
            'is_delivery'    => !isset($input['is_delivery']) || !empty($input['is_delivery']) ? 1 : 0,
            'source'         => trim((string) ($input['source'] ?? 'manual')),
            'type'           => trim((string) ($input['type'] ?? 'simple')),
            'menu_data'      => $menuDataJson,
        ]);

        $newId = (int) \Pit\Cuixa\Backend\Db\Connection::get()->lastInsertId();

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

        // Slug collisions with a product of the same type are a 422 here; a
        // collision with a product of a different type renames this one with
        // its own type suffix (see applySlugRule()). $id is excluded so the
        // product can keep its own slug while renaming is still applied when
        // the proposed slug belongs to ANOTHER product.
        if (!self::applySlugRule($input, $id)) {
            return;
        }

        $menuDataJson = null;
        if (isset($input['menu_data']) && (is_array($input['menu_data']) || is_string($input['menu_data']))) {
            $menuDataJson = is_array($input['menu_data']) ? json_encode($input['menu_data'], JSON_UNESCAPED_UNICODE) : $input['menu_data'];
        }

        // Overwrite through the centralized Product repository (single write
        // path for the products table). A full 21-column overwrite preserves
        // the exact row the inline UPDATE used to produce, including a slug
        // change, which is why updateById() is used instead of update() (the
        // latter keys on slug and would clobber a renamed slug).
        $repo = new ProductRepo();
        $repo->updateById($id, [
            'category_id'    => (int) ($input['category_id'] ?? 0),
            'slug'           => trim((string) ($input['slug'] ?? '')),
            'name_es'        => trim((string) ($input['name_es'] ?? '')),
            'name_en'        => trim((string) ($input['name_en'] ?? '')),
            'name_ca'        => trim((string) ($input['name_ca'] ?? '')),
            'name_uk'        => trim((string) ($input['name_uk'] ?? '')),
            'description_es' => trim((string) ($input['description_es'] ?? '')),
            'description_en' => trim((string) ($input['description_en'] ?? '')),
            'description_ca' => trim((string) ($input['description_ca'] ?? '')),
            'description_uk' => trim((string) ($input['description_uk'] ?? '')),
            'price'          => (float) ($input['price'] ?? 0),
            'image_url'      => trim((string) ($input['image_url'] ?? '')),
            'last_shop_url'  => trim((string) ($input['last_shop_url'] ?? '')),
            'sort_order'     => (int) ($input['sort_order'] ?? 0),
            'is_active'      => !empty($input['is_active']) ? 1 : 0,
            'is_featured'    => !empty($input['is_featured']) ? 1 : 0,
            'is_dine_in'     => !isset($input['is_dine_in']) || !empty($input['is_dine_in']) ? 1 : 0,
            'is_delivery'    => !isset($input['is_delivery']) || !empty($input['is_delivery']) ? 1 : 0,
            'source'         => trim((string) ($input['source'] ?? 'manual')),
            'type'           => trim((string) ($input['type'] ?? 'simple')),
            'menu_data'      => $menuDataJson,
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

        // Soft-delete through the centralized Product repository (same write
        // path as create/update for the products table).
        (new ProductRepo())->setStatusById($id, false);

        Response::json([
            'error' => false,
            'data'  => ['id' => $id, 'deleted' => true],
        ]);
    }
}
