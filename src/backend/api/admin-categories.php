<?php
/**
 * Pit o Cuixa — Admin Categories API Controller
 *
 * POST   /api/admin/categories       — Create category
 * PUT    /api/admin/categories/{id}  — Update category
 * DELETE /api/admin/categories/{id}  — Delete (deactivate) category
 *
 * All endpoints require Bearer token auth.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Auth\Auth;

class AdminCategories
{
    /**
     * Validate input data for category create/update.
     *
     * @param  array $input
     * @return array{ok: bool, errors: string[]}
     */
    private static function validate(array $input): array
    {
        $errors = [];

        if (empty($input['name_es'])) {
            $errors[] = 'name_es is required';
        }
        if (empty($input['name_en'])) {
            $errors[] = 'name_en is required';
        }
        if (empty($input['slug'])) {
            $errors[] = 'slug is required';
        }

        if (!empty($input['slug']) && !preg_match('/^[a-z0-9-]+$/', $input['slug'])) {
            $errors[] = 'slug must contain only lowercase letters, numbers, and hyphens';
        }

        return [
            'ok'     => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * POST /api/admin/categories — Create a new category.
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

        $pdo = \Pit\Cuixa\Backend\Db\Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO categories (slug, name_es, name_en, sort_order, is_active)
             VALUES (:slug, :name_es, :name_en, :sort_order, :is_active)'
        );

        $stmt->execute([
            ':slug'       => trim((string) ($input['slug'] ?? '')),
            ':name_es'    => trim((string) ($input['name_es'] ?? '')),
            ':name_en'    => trim((string) ($input['name_en'] ?? '')),
            ':sort_order' => (int) ($input['sort_order'] ?? 0),
            ':is_active'  => !empty($input['is_active']) ? 1 : 0,
        ]);

        $newId = (int) $pdo->lastInsertId();

        Response::json([
            'error' => false,
            'data'  => ['id' => $newId],
        ], 201);
    }

    /**
     * PUT /api/admin/categories/{id} — Update an existing category.
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

        $check = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
        $check->execute([':id' => $id]);

        if ($check->fetch() === false) {
            Response::error('Category not found', 404);
            return;
        }

        $stmt = $pdo->prepare(
            'UPDATE categories SET
                slug       = :slug,
                name_es    = :name_es,
                name_en    = :name_en,
                sort_order = :sort_order,
                is_active  = :is_active,
                updated_at = datetime(\'now\')
             WHERE id = :id'
        );

        $stmt->execute([
            ':id'         => $id,
            ':slug'       => trim((string) ($input['slug'] ?? '')),
            ':name_es'    => trim((string) ($input['name_es'] ?? '')),
            ':name_en'    => trim((string) ($input['name_en'] ?? '')),
            ':sort_order' => (int) ($input['sort_order'] ?? 0),
            ':is_active'  => !empty($input['is_active']) ? 1 : 0,
        ]);

        Response::json([
            'error' => false,
            'data'  => ['id' => $id, 'updated' => true],
        ]);
    }

    /**
     * DELETE /api/admin/categories/{id} — Deactivate a category (soft delete).
     */
    public static function delete(int $id): void
    {
        Auth::requireToken();
        Auth::validateCsrfToken();

        $pdo = \Pit\Cuixa\Backend\Db\Connection::get();

        $check = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
        $check->execute([':id' => $id]);

        if ($check->fetch() === false) {
            Response::error('Category not found', 404);
            return;
        }

        $stmt = $pdo->prepare(
            'UPDATE categories SET is_active = 0, updated_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        Response::json([
            'error' => false,
            'data'  => ['id' => $id, 'deleted' => true],
        ]);
    }
}
