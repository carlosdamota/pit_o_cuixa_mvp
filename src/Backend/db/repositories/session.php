<?php
/**
 * Pit o Cuixa — Session Repository
 *
 * Data access layer for the sessions table.
 * Token CRUD: create, find, delete, expire.
 * All methods use PDO prepared statements exclusively.
 *
 * @package Pit\Cuixa\Backend\Db\Repositories
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db\Repositories;

use Pit\Cuixa\Backend\Db\Connection;

class Session
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    /**
     * Create a new session token.
     *
     * @param  int    $userId
     * @param  string $token
     * @param  string $expiresAt  Datetime string (Y-m-d H:i:s)
     * @return int  New session ID
     */
    public function create(int $userId, string $token, string $expiresAt): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)'
        );
        $stmt->execute([
            ':user_id'    => $userId,
            ':token'      => $token,
            ':expires_at' => $expiresAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Find a session by token.
     *
     * @param  string $token
     * @return array<string, mixed>|null
     */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM sessions WHERE token = :token'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();

        return $row !== false ? $this->serialize($row) : null;
    }

    /**
     * Delete a session by token (logout).
     *
     * @param  string $token
     * @return bool
     */
    public function deleteByToken(string $token): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM sessions WHERE token = :token'
        );

        return $stmt->execute([':token' => $token]);
    }

    /**
     * Delete all expired sessions (cleanup).
     *
     * @return int  Number of deleted rows
     */
    public function deleteExpired(): int
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM sessions WHERE expires_at < datetime('now')"
        );
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Delete all sessions for a user (e.g. password reset).
     *
     * @param  int $userId
     * @return bool
     */
    public function deleteByUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM sessions WHERE user_id = :user_id'
        );

        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Extend session expiry (touch on use).
     *
     * @param  string $token
     * @param  string $newExpiresAt
     * @return bool
     */
    public function extendExpiry(string $token, string $newExpiresAt): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sessions SET expires_at = :expires_at WHERE token = :token'
        );

        return $stmt->execute([
            ':token'      => $token,
            ':expires_at' => $newExpiresAt,
        ]);
    }

    /**
     * Normalise types for consistent output.
     *
     * @param  array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function serialize(array $row): array
    {
        $row['id']      = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];

        return $row;
    }
}
