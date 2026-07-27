<?php
/**
 * Pit o Cuixa — Admin Import/Export API Controller
 *
 * POST /api/admin/import — CSV upload, parse, upsert by last_shop_url
 * GET  /api/admin/export — CSV download of all products
 *
 * All endpoints require Bearer token auth.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Auth\Auth;

class AdminIO
{
    /**
     * POST /api/admin/import
     *
     * Accepts multipart/form-data with a "file" field containing CSV.
     * Columns: slug, name_es, name_en, description_es, description_en, price,
     *          category_id, image_url, last_shop_url, sort_order, is_active, is_featured
     *
     * Upserts by last_shop_url: if exists → update, if not → insert.
     * Returns: { imported: int, errors: string[] }
     */
    public static function import(): void
    {
        Auth::requireToken();
        Auth::validateCsrfToken();

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('CSV file is required', 400);
            return;
        }

        // WARNING-2 fix: File size limit (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($_FILES['file']['size'] > $maxSize) {
            Response::json([
                'error'   => true,
                'message' => 'File too large. Maximum size is 5MB.',
                'code'    => 413,
            ], 413);
            return;
        }

        // WARNING-3 fix: CSV extension validation
        $filename = $_FILES['file']['name'] ?? '';
        if (!preg_match('/\.csv$/i', $filename)) {
            Response::json([
                'error'   => true,
                'message' => 'Only CSV files are accepted.',
                'code'    => 415,
            ], 415);
            return;
        }

        $tmpPath = $_FILES['file']['tmp_name'];
        $handle  = fopen($tmpPath, 'rb');

        if ($handle === false) {
            Response::error('Cannot read uploaded file', 500);
            return;
        }

        $pdo    = \Pit\Cuixa\Backend\Db\Connection::get();
        $errors = [];
        $imported = 0;
        $lineNum  = 0;

        // Read header row
        $headers = fgetcsv($handle);

        if ($headers === false || $headers === null) {
            fclose($handle);
            Response::error('CSV file is empty or has no header row', 400);
            return;
        }

        // Normalise headers (trim BOM, whitespace, lowercase)
        $headers = array_map(static function (string $h): string {
            // Remove UTF-8 BOM if present
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            return trim(strtolower($h));
        }, $headers);

        $requiredCols = ['slug', 'name_es', 'name_en', 'category_id'];
        $colIndex     = array_flip($headers);

        // Check required columns exist
        foreach ($requiredCols as $col) {
            if (!isset($colIndex[$col])) {
                fclose($handle);
                Response::error("Required column '{$col}' not found in CSV header", 400);
                return;
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;
            $row = array_map('trim', $row);

            // Build associative row
            $data = [];
            foreach ($headers as $i => $header) {
                $data[$header] = $row[$i] ?? '';
            }

            // ── Validate row ───────────────────────────────────────────
            $rowErrors = [];

            if ($data['slug'] === '') {
                $rowErrors[] = "Row {$lineNum}: slug is required";
            }
            if ($data['name_es'] === '') {
                $rowErrors[] = "Row {$lineNum}: name_es is required";
            }
            if ($data['name_en'] === '') {
                $rowErrors[] = "Row {$lineNum}: name_en is required";
            }
            if ($data['category_id'] === '' || !ctype_digit($data['category_id'])) {
                $rowErrors[] = "Row {$lineNum}: category_id must be a numeric ID";
            }
            if ($data['price'] !== '' && (!is_numeric($data['price']) || (float) $data['price'] < 0)) {
                $rowErrors[] = "Row {$lineNum}: price must be a non-negative number";
            }
            if ($data['last_shop_url'] !== '' && !preg_match('#^https?://#i', $data['last_shop_url'])) {
                $rowErrors[] = "Row {$lineNum}: last_shop_url must start with https:// or http://";
            }
            if ($data['image_url'] !== '' && !preg_match('#^https?://#i', $data['image_url'])) {
                $rowErrors[] = "Row {$lineNum}: image_url must start with https:// or http://";
            }
            if ($data['slug'] !== '' && !preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
                $rowErrors[] = "Row {$lineNum}: slug must contain only lowercase letters, numbers, hyphens";
            }

            if ($rowErrors !== []) {
                $errors = array_merge($errors, $rowErrors);
                continue;
            }

            // ── Upsert by last_shop_url ────────────────────────────────
            try {
                // Check if product exists by slug
                $check = $pdo->prepare('SELECT id FROM products WHERE slug = :slug');
                $check->execute([':slug' => $data['slug']]);
                $existing = $check->fetch();

                if ($existing !== false) {
                    // Update
                    $stmt = $pdo->prepare(
                        'UPDATE products SET
                            name_es = :name_es, name_en = :name_en,
                            description_es = :description_es, description_en = :description_en,
                            price = :price, category_id = :category_id,
                            image_url = :image_url, last_shop_url = :last_shop_url,
                            sort_order = :sort_order, is_active = :is_active,
                            is_featured = :is_featured,
                            updated_at = datetime(\'now\')
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':id'             => $existing['id'],
                        ':name_es'        => $data['name_es'],
                        ':name_en'        => $data['name_en'],
                        ':description_es' => $data['description_es'] ?? '',
                        ':description_en' => $data['description_en'] ?? '',
                        ':price'          => (float) ($data['price'] ?? 0),
                        ':category_id'    => (int) $data['category_id'],
                        ':image_url'      => $data['image_url'] ?? '',
                        ':last_shop_url'  => $data['last_shop_url'] ?? '',
                        ':sort_order'     => (int) ($data['sort_order'] ?? 0),
                        ':is_active'      => !empty($data['is_active']) ? 1 : 0,
                        ':is_featured'    => !empty($data['is_featured']) ? 1 : 0,
                    ]);
                } else {
                    // Insert
                    $stmt = $pdo->prepare(
                        'INSERT INTO products
                            (slug, name_es, name_en, description_es, description_en, price,
                             category_id, image_url, last_shop_url, sort_order, is_active, is_featured)
                         VALUES
                            (:slug, :name_es, :name_en, :description_es, :description_en, :price,
                             :category_id, :image_url, :last_shop_url, :sort_order, :is_active, :is_featured)'
                    );
                    $stmt->execute([
                        ':slug'           => $data['slug'],
                        ':name_es'        => $data['name_es'],
                        ':name_en'        => $data['name_en'],
                        ':description_es' => $data['description_es'] ?? '',
                        ':description_en' => $data['description_en'] ?? '',
                        ':price'          => (float) ($data['price'] ?? 0),
                        ':category_id'    => (int) $data['category_id'],
                        ':image_url'      => $data['image_url'] ?? '',
                        ':last_shop_url'  => $data['last_shop_url'] ?? '',
                        ':sort_order'     => (int) ($data['sort_order'] ?? 0),
                        ':is_active'      => !empty($data['is_active']) ? 1 : 0,
                        ':is_featured'    => !empty($data['is_featured']) ? 1 : 0,
                    ]);
                }

                $imported++;
            } catch (\PDOException $e) {
                error_log("CSV import DB error at row {$lineNum}: " . $e->getMessage());
                $errors[] = "Row {$lineNum}: import failed — check data format";
            }
        }

        fclose($handle);

        Response::json([
            'error'    => false,
            'data'     => [
                'imported' => $imported,
                'errors'   => $errors,
            ],
        ]);
    }

    /**
     * GET /api/admin/export
     *
     * Downloads all products as CSV with BOM for Excel UTF-8 compatibility.
     */
    public static function export(): void
    {
        Auth::requireToken();

        $pdo  = \Pit\Cuixa\Backend\Db\Connection::get();
        $stmt = $pdo->prepare(
            'SELECT p.slug, p.name_es, p.name_en, p.description_es, p.description_en,
                    p.price, p.category_id, p.image_url, p.last_shop_url,
                    p.sort_order, p.is_active, p.is_featured
             FROM products p
             ORDER BY p.category_id, p.sort_order'
        );
        $stmt->execute();
        $products = $stmt->fetchAll();

        // Ensure types are correct for CSV output
        $rows = array_map(static function (array $row): array {
            return [
                'slug'           => $row['slug'],
                'name_es'        => $row['name_es'],
                'name_en'        => $row['name_en'],
                'description_es' => $row['description_es'],
                'description_en' => $row['description_en'],
                'price'          => (float) $row['price'],
                'category_id'    => (int) $row['category_id'],
                'image_url'      => $row['image_url'] ?? '',
                'last_shop_url'  => $row['last_shop_url'] ?? '',
                'sort_order'     => (int) $row['sort_order'],
                'is_active'      => (int) $row['is_active'],
                'is_featured'    => (int) $row['is_featured'],
            ];
        }, $products);

        Response::csv($rows, 'pitocuixa-products.csv');
    }
}
