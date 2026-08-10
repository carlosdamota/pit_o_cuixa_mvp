<?php

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Services;

final class MigrationRunner
{
    private string $dbPath;
    private string $migrationsDir;
    private string $lockDir;
    /** @var resource|null */
    private $lockHandle = null;

    public function __construct(
        ?string $dbPath = null,
        ?string $migrationsDir = null,
        ?string $lockDir = null,
    ) {
        $this->dbPath = $dbPath ?? \Config::dbPath();
        $this->migrationsDir = $migrationsDir ?? dirname(__DIR__, 3) . '/db/migrations';
        $this->lockDir = $lockDir ?? dirname($this->dbPath);
    }

    public function run(): array
    {
        $result = ['applied' => 0, 'failed' => 0, 'errors' => [], 'locked' => false];

        if (!is_file($this->dbPath)) {
            $result['failed'] = 1;
            $result['errors'][] = "Database not found at {$this->dbPath}";
            return $result;
        }

        if (!is_dir($this->migrationsDir)) {
            $result['failed'] = 1;
            $result['errors'][] = 'Migrations directory not found.';
            return $result;
        }

        if (!$this->acquireLock()) {
            $result['failed'] = 1;
            $result['errors'][] = 'Migration already in progress';
            $result['locked'] = true;
            return $result;
        }

        try {
            $pdo = new \PDO('sqlite:' . $this->dbPath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA foreign_keys = ON');

            $pdo->exec('CREATE TABLE IF NOT EXISTS _migrations (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                filename  TEXT    NOT NULL UNIQUE,
                applied_at TEXT   NOT NULL DEFAULT (datetime(\'now\'))
            )');

            $stmt = $pdo->query('SELECT filename FROM _migrations ORDER BY id');
            $applied = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $files = glob($this->migrationsDir . '/*.sql');
            if ($files === false) {
                $files = [];
            }
            sort($files);

            foreach ($files as $file) {
                $filename = basename($file);

                if (in_array($filename, $applied, true)) {
                    continue;
                }

                $sql = file_get_contents($file);

                if ($sql === false || trim($sql) === '') {
                    continue;
                }

                try {
                    $pdo->beginTransaction();

                    $statements = self::splitSql($sql);
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if ($statement === '') {
                            continue;
                        }
                        $pdo->exec($statement);
                    }

                    $ins = $pdo->prepare('INSERT INTO _migrations (filename) VALUES (:f)');
                    $ins->execute([':f' => $filename]);

                    $pdo->commit();
                    $result['applied']++;
                } catch (\PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $msg = $e->getMessage();

                    if (str_contains($msg, 'duplicate column name')) {
                        $ins = $pdo->prepare('INSERT OR IGNORE INTO _migrations (filename) VALUES (:f)');
                        $ins->execute([':f' => $filename]);
                        $result['applied']++;
                        continue;
                    }

                    $result['failed']++;
                    $result['errors'][] = "{$filename}: {$msg}";
                }
            }
        } finally {
            $this->releaseLock();
        }

        return $result;
    }

    public function getPendingMigrations(): array
    {
        if (!is_file($this->dbPath) || !is_dir($this->migrationsDir)) {
            return [];
        }

        $pdo = new \PDO('sqlite:' . $this->dbPath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->exec('CREATE TABLE IF NOT EXISTS _migrations (
            id        INTEGER PRIMARY KEY AUTOINCREMENT,
            filename  TEXT    NOT NULL UNIQUE,
            applied_at TEXT   NOT NULL DEFAULT (datetime(\'now\'))
        )');

        $stmt = $pdo->query('SELECT filename FROM _migrations ORDER BY id');
        $applied = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $files = glob($this->migrationsDir . '/*.sql');
        if ($files === false) {
            return [];
        }
        sort($files);

        $pending = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $applied, true)) {
                $pending[] = $filename;
            }
        }

        return $pending;
    }

    public function isLocked(): bool
    {
        $lockFile = $this->lockDir . '/.migrate.lock';

        if (!is_file($lockFile)) {
            return false;
        }

        $handle = @fopen($lockFile, 'r');
        if ($handle === false) {
            return false;
        }

        $locked = !flock($handle, LOCK_EX | LOCK_NB);
        fclose($handle);

        return $locked;
    }

    private function acquireLock(): bool
    {
        if (!is_dir($this->lockDir)) {
            if (!mkdir($this->lockDir, 0750, true) && !is_dir($this->lockDir)) {
                return false;
            }
        }

        $lockFile = $this->lockDir . '/.migrate.lock';
        $handle = @fopen($lockFile, 'c');

        if ($handle === false) {
            return false;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }

        $this->lockHandle = $handle;
        return true;
    }

    private function releaseLock(): void
    {
        if ($this->lockHandle !== null) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    public static function splitSql(string $sql): array
    {
        $parts = preg_split('/;\s*\n/', $sql);

        if ($parts === false || count($parts) <= 1) {
            $trimmed = trim($sql);
            return $trimmed !== '' ? [$trimmed] : [];
        }

        $statements = [];
        foreach ($parts as $part) {
            $lines = explode("\n", $part);
            $codeLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '' && !str_starts_with($trimmed, '--')) {
                    $codeLines[] = $line;
                }
            }
            $statement = trim(implode("\n", $codeLines));
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }

        return $statements;
    }
}
