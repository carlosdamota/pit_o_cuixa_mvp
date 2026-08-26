<?php
/**
 * Pit o Cuixa — Backup Code Repository
 *
 * One-time recovery codes for TOTP. Each code is stored as a bcrypt hash; the
 * plaintext is shown to the admin only at enrollment time.
 *
 * @package Pit\Cuixa\Backend\Db\Repositories
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db\Repositories;

use Pit\Cuixa\Backend\Db\Connection;

class BackupCode
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    /**
     * Store a batch of bcrypt-hashed backup codes for a user.
     *
     * @param string[] $codeHashes
     */
    public function storeMany(int $userId, array $codeHashes): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO backup_codes (user_id, code_hash) VALUES (:user_id, :code_hash)'
        );

        foreach ($codeHashes as $hash) {
            $stmt->execute([
                ':user_id'   => $userId,
                ':code_hash' => $hash,
            ]);
        }
    }

    /**
     * Find and verify a backup code for a user among unused codes.
     *
     * @return int|null The matched backup_codes.id, or null if none matched
     */
    public function verify(string $code, int $userId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code_hash FROM backup_codes WHERE user_id = :user_id AND used_at IS NULL'
        );
        $stmt->execute([':user_id' => $userId]);

        while (($row = $stmt->fetch()) !== false) {
            if (password_verify($code, (string) $row['code_hash'])) {
                return (int) $row['id'];
            }
        }

        return null;
    }

    /**
     * Mark a backup code as used (single-use).
     */
    public function markUsed(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE backup_codes SET used_at = datetime('now') WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Remove all backup codes for a user (used before re-enrollment).
     */
    public function clearForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM backup_codes WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
    }
}
