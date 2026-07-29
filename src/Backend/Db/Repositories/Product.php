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
     * @param  int      $offset      Offset for pagination
     * @return array<int, array<string, mixed>>
     */
    /**
     * Return all active products, optionally filtered by category and channel.
     *
     * @param  int|null    $categoryId  Filter by category ID (null = all)
     * @param  int         $limit       Maximum rows to return (safety cap)
     * @param  int         $offset      Offset for pagination
     * @param  string|null $channel     Filter by channel ('dine_in' | 'delivery' | null)
     * @return array<int, array<string, mixed>>
     */
    public function all(?int $categoryId = null, int $limit = 100, int $offset = 0, ?string $channel = null): array
    {
        $sql = 'SELECT p.*, c.slug AS category_slug, c.name_ca AS category_name_ca, c.name_es AS category_name_es, c.name_en AS category_name_en
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1';

        $params = [];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        if ($channel === 'dine_in') {
            $sql .= ' AND p.is_dine_in = 1';
        } elseif ($channel === 'delivery') {
            $sql .= ' AND p.is_delivery = 1';
        }

        $sql .= ' ORDER BY c.sort_order, p.sort_order LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'serialize'], $stmt->fetchAll());
    }

    /**
     * Count all active products, optionally filtered by category and channel.
     *
     * @param  int|null    $categoryId  Filter by category ID (null = all)
     * @param  string|null $channel     Filter by channel ('dine_in' | 'delivery' | null)
     * @return int
     */
    public function count(?int $categoryId = null, ?string $channel = null): int
    {
        $sql = 'SELECT COUNT(*) FROM products p WHERE p.is_active = 1';
        $params = [];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        if ($channel === 'dine_in') {
            $sql .= ' AND p.is_dine_in = 1';
        } elseif ($channel === 'delivery') {
            $sql .= ' AND p.is_delivery = 1';
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
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
            'SELECT p.*, c.slug AS category_slug, c.name_ca AS category_name_ca, c.name_es AS category_name_es, c.name_en AS category_name_en
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
     * @param  int         $categoryId
     * @param  string|null $channel
     * @return array<int, array<string, mixed>>
     */
    public function byCategory(int $categoryId, ?string $channel = null): array
    {
        return $this->all($categoryId, 100, 0, $channel);
    }

    /**
     * Normalise types for JSON-safe output.
     *
     * @param  array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function serialize(array $row): array
    {
        $row['id']          = (int) $row['id'];
        $row['category_id'] = (int) $row['category_id'];
        $row['price']       = (float) $row['price'];
        $row['sort_order']  = (int) $row['sort_order'];
        $row['is_active']   = (bool) $row['is_active'];
        $row['is_featured'] = (bool) $row['is_featured'];
        $row['is_dine_in']  = isset($row['is_dine_in']) ? (bool) $row['is_dine_in'] : true;
        $row['is_delivery'] = isset($row['is_delivery']) ? (bool) $row['is_delivery'] : true;
        $row['source']      = (string) ($row['source'] ?? 'delivery');
        $row['type']        = (string) ($row['type'] ?? 'simple');

        if (isset($row['menu_data']) && is_string($row['menu_data']) && $row['menu_data'] !== '') {
            $decoded = json_decode($row['menu_data'], true);
            $row['menu_data'] = is_array($decoded) ? $decoded : null;
        } else if (!isset($row['menu_data'])) {
            $row['menu_data'] = null;
        }

        return $row;
    }
}
