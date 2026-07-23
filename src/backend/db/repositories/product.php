<?php
/**
 * Pit o Cuixa — Product Repository
 *
 * Data access layer for the products table.
 * All methods use PDO prepared statements exclusively.
 *
 * @package Pit\Cuixa\Backend\Db\Repositories
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db\Repositories;

use Pit\Cuixa\Backend\Db\Connection;

class Product
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    /**
     * Return all active products, optionally filtered by category.
     *
     * @param  int|null $categoryId  Filter by category ID (null = all)
     * @param  int      $limit       Maximum rows to return (safety cap)
     * @return array<int, array<string, mixed>>
     */
    public function all(?int $categoryId = null, int $limit = 100): array
    {
        $sql = 'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1';

        $params = [];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        $sql .= ' ORDER BY c.sort_order, p.sort_order LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'serialize'], $stmt->fetchAll());
    }

    /**
     * Find a single active product by its slug.
     *
     * @param  string $slug  URL-safe product identifier
     * @return array<string, mixed>|null
     */
    public function bySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name_es AS category_name_es, c.name_en AS category_name_en
             FROM products p
             JOIN categories c ON p.category_id = c.id
             WHERE p.slug = :slug AND p.is_active = 1'
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $this->serialize($row) : null;
    }

    /**
     * Alias: return all products in a given category.
     *
     * @param  int $categoryId
     * @return array<int, array<string, mixed>>
     */
    public function byCategory(int $categoryId): array
    {
        return $this->all($categoryId);
    }

    /**
     * Normalise types for JSON-safe output.
     *
     * @param  array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function serialize(array $row): array
    {
        $row['id']          = (int) $row['id'];
        $row['category_id'] = (int) $row['category_id'];
        $row['price']       = (float) $row['price'];
        $row['sort_order']  = (int) $row['sort_order'];
        $row['is_active']   = (bool) $row['is_active'];
        $row['is_featured'] = (bool) $row['is_featured'];

        return $row;
    }
}
