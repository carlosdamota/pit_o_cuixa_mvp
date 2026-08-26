<?php
/**
 * Pit o Cuixa — Two-Factor Challenge Repository
 *
 * Short-lived single-use challenges issued after a correct password when the
 * admin has TOTP enabled. The holder must present a valid TOTP/backup code
 * before a session is created.
 *
 * @package Pit\Cuixa\Backend\Db\Repositories
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db\Repositories;

use Pit\Cuixa\Backend\Db\Connection;

class TwoFactorChallenge
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    /**
     * Create a new challenge for a user.
     *
     * @return string The challenge token (random, 256-bit)
     */
    public function create(int $userId): string
    {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes

        $stmt = $this->pdo->prepare(
            'INSERT INTO two_factor_challenges (user_id, token, expires_at, attempts)
             VALUES (:user_id, :token, :expires_at, 0)'
        );
        $stmt->execute([
            ':user_id'    => $userId,
            ':token'      => $token,
            ':expires_at' => $expires,
        ]);

        return $token;
    }

    /**
     * Look up a challenge by token. Returns null if missing or expired
     * (expired rows are deleted as a side effect).
     *
     * @return array<string, mixed>|null
     */
    public function find(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM two_factor_challenges WHERE token = :token');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            $this->delete($token);
            return null;
        }

        return $row;
    }

    /**
     * Increment the attempt counter and return the new value.
     */
    public function incrementAttempts(string $token): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE two_factor_challenges SET attempts = attempts + 1 WHERE token = :token'
        );
        $stmt->execute([':token' => $token]);

        $stmt = $this->pdo->prepare('SELECT attempts FROM two_factor_challenges WHERE token = :token');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();

        return $row === false ? 0 : (int) $row['attempts'];
    }

    /**
     * Delete a challenge (used on success or expiry).
     */
    public function delete(string $token): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM two_factor_challenges WHERE token = :token');
        $stmt->execute([':token' => $token]);
    }
}
