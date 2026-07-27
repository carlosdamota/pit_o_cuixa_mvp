<?php
/**
 * Pit o Cuixa — User Repository
 *
 * Data access layer for the users table.
 * All methods use PDO prepared statements exclusively.
 *
 * @package Pit\Cuixa\Backend\Db\Repositories
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db\Repositories;

use Pit\Cuixa\Backend\Db\Connection;

class User
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    /**
     * Find a user by username.
     *
     * @param  string $username
     * @return array<string, mixed>|null
     */
    public function byUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE username = :username AND is_active = 1'
        );
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();

        return $row !== false ? $this->serialize($row) : null;
    }

    /**
     * Find a user by ID.
     *
     * @param  int $id
     * @return array<string, mixed>|null
     */
    public function byId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE id = :id AND is_active = 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $this->serialize($row) : null;
    }

    /**
     * Return all active users.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE is_active = 1 ORDER BY display_name'
        );
        $stmt->execute();

        return array_map([$this, 'serialize'], $stmt->fetchAll());
    }

    /**
     * Create a new user.
     *
     * @param  string $username
     * @param  string $passwordHash  bcrypt hash
     * @param  string $displayName
     * @return int  New user ID
     */
    public function create(string $username, string $passwordHash, string $displayName = '', string $role = 'admin'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password, display_name, role) VALUES (:username, :password, :display_name, :role)'
        );
        $stmt->execute([
            ':username'     => $username,
            ':password'     => $passwordHash,
            ':display_name' => $displayName,
            ':role'         => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update user password.
     *
     * @param  int    $id
     * @param  string $newPasswordHash
     * @return bool
     */
    public function updatePassword(int $id, string $newPasswordHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password = :password, updated_at = datetime(\'now\') WHERE id = :id'
        );

        return $stmt->execute([
            ':password' => $newPasswordHash,
            ':id'       => $id,
        ]);
    }

    /**
     * Update user display name.
     *
     * @param  int    $id
     * @param  string $displayName
     * @return bool
     */
    public function updateDisplayName(int $id, string $displayName): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET display_name = :display_name, updated_at = datetime(\'now\') WHERE id = :id'
        );

        return $stmt->execute([
            ':display_name' => $displayName,
            ':id'           => $id,
        ]);
    }

    /**
     * Deactivate a user (soft delete).
     *
     * @param  int $id
     * @return bool
     */
    public function deactivate(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET is_active = 0, updated_at = datetime(\'now\') WHERE id = :id'
        );

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Normalise types for consistent output.
     *
     * @param  array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function serialize(array $row): array
    {
        $row['id']    = (int) $row['id'];
        $row['is_active'] = (bool) $row['is_active'];

        // Never expose password hash
        unset($row['password']);

        return $row;
    }
}
