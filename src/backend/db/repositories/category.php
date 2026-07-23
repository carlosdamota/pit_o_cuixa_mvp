<?php
/**
 * Pit o Cuixa — Category Repository
 *
 * Data access layer for the categories table.
 * All methods use PDO prepared statements exclusively.
 *
 * @package Pit\Cuixa\Backend\Db\Repositories
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db\Repositories;

use Pit\Cuixa\Backend\Db\Connection;

class Category
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    /**
     * Return all active categories ordered by sort_order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order'
        );
        $stmt->execute();

        return array_map([$this, 'serialize'], $stmt->fetchAll());
    }

    /**
     * Find a single active category by its slug.
     *
     * @param  string $slug
     * @return array<string, mixed>|null
     */
    public function bySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM categories WHERE slug = :slug AND is_active = 1'
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $this->serialize($row) : null;
    }

    /**
     * Normalise types for JSON-safe output.
     *
     * @param  array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function serialize(array $row): array
    {
        $row['id']         = (int) $row['id'];
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_active']  = (bool) $row['is_active'];

        return $row;
    }
}
