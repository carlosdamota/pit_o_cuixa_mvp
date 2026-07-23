<?php
/**
 * Pit o Cuixa — Database Connection (PDO Singleton)
 *
 * Returns a single PDO instance configured for SQLite with:
 * - WAL journal mode (concurrent reads without blocking)
 * - Foreign key enforcement
 * - Busy timeout (5000ms to mitigate SQLITE_BUSY)
 * - Exception error mode
 * - Associative fetch by default
 *
 * @package Pit\Cuixa\Backend\Db
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Db;

final class Connection
{
    private static ?\PDO $instance = null;

    /**
     * Get the singleton PDO connection.
     * Opens the connection on first call.
     */
    public static function get(): \PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dbPath = \Config::dbPath();
        $dbDir  = dirname($dbPath);

        // Ensure the data directory exists
        if (!is_dir($dbDir)) {
            if (!mkdir($dbDir, 0750, true) && !is_dir($dbDir)) {
                throw new \RuntimeException("Cannot create database directory: {$dbDir}");
            }
        }

        $pdo = new \PDO('sqlite:' . $dbPath);

        // ── PDO attributes ───────────────────────────────────────────
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        // ── Pragmas ──────────────────────────────────────────────────
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA synchronous = NORMAL');

        self::$instance = $pdo;

        return self::$instance;
    }

    /**
     * Close the connection (primarily for testing).
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}
