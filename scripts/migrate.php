<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/shared/config.php';

define('MIGRATIONS_DIR', __DIR__ . '/../db/migrations');

function status(string $icon, string $message): void
{
    $green = "\033[32m";
    $red   = "\033[31m";
    $cyan  = "\033[36m";
    $reset = "\033[0m";
    $color = match ($icon) {
        '✓' => $green,
        '✗' => $red,
        '→' => $cyan,
        default => '',
    };
    echo "{$color}[{$icon}]{$reset} {$message}\n";
}

echo "\n";
echo "  ╔══════════════════════════════════╗\n";
echo "  ║   Pit o Cuixa — Migrate          ║\n";
echo "  ║   SQLite migrations              ║\n";
echo "  ╚══════════════════════════════════╝\n";
echo "\n";

$dbPath = Config::dbPath();
echo "Database: {$dbPath}\n";
echo "Migrations: " . realpath(MIGRATIONS_DIR) . "\n";
echo "\n";

if (!is_file($dbPath)) {
    status('✗', "Database not found at {$dbPath}. Run `php scripts/setup.php` first.");
    exit(1);
}

if (!is_dir(MIGRATIONS_DIR)) {
    status('✗', 'Migrations directory not found.');
    exit(1);
}

try {
    $pdo = new \PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (\PDOException $e) {
    status('✗', "Cannot open database: " . $e->getMessage());
    exit(1);
}

$pdo->exec('CREATE TABLE IF NOT EXISTS _migrations (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    filename  TEXT    NOT NULL UNIQUE,
    applied_at TEXT   NOT NULL DEFAULT (datetime(\'now\'))
)');

$stmt = $pdo->query('SELECT filename FROM _migrations ORDER BY id');
$applied = $stmt->fetchAll(\PDO::FETCH_COLUMN);

$files = glob(MIGRATIONS_DIR . '/*.sql');
sort($files);

$pending = 0;
$failed  = 0;

foreach ($files as $file) {
    $filename = basename($file);

    if (in_array($filename, $applied, true)) {
        continue;
    }

    $sql = file_get_contents($file);

    if ($sql === false || trim($sql) === '') {
        status('→', "{$filename} — empty file, skipped.");
        continue;
    }

    try {
        $pdo->beginTransaction();

        $statements = explode(';', $sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }
            $lines = explode("\n", $stmt);
            $codeLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '' && !str_starts_with($trimmed, '--')) {
                    $codeLines[] = $line;
                }
            }
            $stmt = trim(implode("\n", $codeLines));
            if ($stmt === '') {
                continue;
            }
            $pdo->exec($stmt);
        }

        $ins = $pdo->prepare('INSERT INTO _migrations (filename) VALUES (:f)');
        $ins->execute([':f' => $filename]);

        $pdo->commit();
        status('✓', "{$filename}");
        $pending++;
    } catch (\PDOException $e) {
        $pdo->rollBack();

        $msg = $e->getMessage();

        // SQLite has no IF NOT EXISTS for ALTER TABLE ADD COLUMN,
        // so treat "duplicate column" as already-applied.
        if (str_contains($msg, 'duplicate column name')) {
            $ins = $pdo->prepare('INSERT OR IGNORE INTO _migrations (filename) VALUES (:f)');
            $ins->execute([':f' => $filename]);
            status('✓', "{$filename} — (column already exists)");
            $pending++;
            continue;
        }

        status('✗', "{$filename} — " . $msg);
        $failed++;
    }
}
echo "\n";
if ($pending === 0 && $failed === 0) {
    status('✓', 'Nothing to migrate. All migrations already applied.');
} else {
    $appliedCount = count($applied) + $pending;
    status('✓', "{$pending} migration(s) applied.");
    if ($failed > 0) {
        status('✗', "{$failed} migration(s) failed.");
    }
    echo "Total migrations applied: {$appliedCount}\n";
}
echo "\n";
