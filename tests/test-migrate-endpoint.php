<?php
/**
 * Pit o Cuixa — Migration Endpoint Tests
 *
 * Tests for MigrationRunner service and /api/migrate endpoint.
 * Uses a throwaway temp DB — never touches data/pitocuixa.db.
 *
 * Usage: php tests/test-migrate-endpoint.php
 * Exit code: 0 when every check passes, 1 otherwise.
 *
 * @package Pit\Cuixa\Tests
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from CLI.\n";
    exit(1);
}

$dbPath = sys_get_temp_dir() . '/pitocuixa-migrate-test-' . uniqid('', true) . '.db';
$migrationsDir = sys_get_temp_dir() . '/pitocuixa-migrate-test-migrations-' . uniqid('', true);
$lockDir = sys_get_temp_dir() . '/pitocuixa-migrate-test-lock-' . uniqid('', true);

if (!class_exists('Config', false)) {
    final class Config
    {
        public static string $dbPath = '';
        public static string $serviceApiToken = '';

        public static function dbPath(): string
        {
            return self::$dbPath;
        }

        public static function serviceApiToken(): string
        {
            return self::$serviceApiToken;
        }

        public static function siteUrl(): string
        {
            return 'https://test.example.com';
        }

        public static function isDev(): bool
        {
            return true;
        }

        public static function defaultLocale(): string
        {
            return 'ca';
        }

        public static function supportedLocales(): array
        {
            return ['ca', 'es', 'en'];
        }

        public static function sessionLifetime(): int
        {
            return 28800;
        }

        public static function env(): string
        {
            return 'test';
        }

        public static function isProd(): bool
        {
            return false;
        }

        public static function phone(): string
        {
            return '+34 000 000 000';
        }

        public static function productUrl(): string
        {
            return 'https://test.example.com/shop';
        }
    }
}
Config::$dbPath = $dbPath;
Config::$serviceApiToken = 'test-service-token';

ini_set('display_errors', '0');

require_once __DIR__ . '/../src/Backend/Services/MigrationRunner.php';
require_once __DIR__ . '/../src/Backend/Http/Response.php';
require_once __DIR__ . '/../src/Backend/Auth/Auth.php';
require_once __DIR__ . '/../src/Backend/Auth/ClickRateLimiter.php';
require_once __DIR__ . '/../src/Backend/Db/Connection.php';
require_once __DIR__ . '/../src/Backend/Db/Repositories/Session.php';
require_once __DIR__ . '/../src/Backend/Db/Repositories/User.php';
require_once __DIR__ . '/../src/Backend/Api/Migrate.php';

$results = ['pass' => 0, 'fail' => 0];

function status(string $label, string $message): void
{
    $green  = "\033[32m";
    $red    = "\033[31m";
    $reset  = "\033[0m";
    $color  = $label === '✓' ? $green : $red;
    echo "{$color}[{$label}]{$reset} {$message}\n";
}

function record(bool $ok, string $label, string $detail = ''): void
{
    global $results;
    $results[$ok ? 'pass' : 'fail']++;
    $suffix = $detail !== '' ? " ({$detail})" : '';
    status($ok ? '✓' : '✗', $label . $suffix);
}

function createMigration(string $dir, string $filename, string $sql): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    file_put_contents($dir . '/' . $filename, $sql);
}

function resetEnv(string $dbPath, string $migrationsDir, string $lockDir): void
{
    foreach ([$dbPath, $dbPath . '-wal', $dbPath . '-shm'] as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
    if (is_dir($migrationsDir)) {
        foreach (glob($migrationsDir . '/*.sql') ?: [] as $f) {
            @unlink($f);
        }
    }
    $lockFile = $lockDir . '/.migrate.lock';
    if (is_file($lockFile)) {
        @unlink($lockFile);
    }
}

function initDb(string $dbPath): \PDO
{
    $pdo = new \PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

echo "\n";
echo "  ╔══════════════════════════════════════════════════╗\n";
echo "  ║   Pit o Cuixa — Migration Endpoint Tests         ║\n";
echo "  ║   Throwaway DB, live data untouched              ║\n";
echo "  ╚══════════════════════════════════════════════════╝\n\n";

try {
    // ── Test 1: MigrationRunner applies pending migrations ─────────────
    resetEnv($dbPath, $migrationsDir, $lockDir);
    $pdo = initDb($dbPath);
    $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');
    $pdo = null;

    createMigration($migrationsDir, '001-create-users.sql', "CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT);\nINSERT INTO users (name) VALUES ('Alice');");
    createMigration($migrationsDir, '002-create-posts.sql', "CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT);");

    $runner = new \Pit\Cuixa\Backend\Services\MigrationRunner($dbPath, $migrationsDir, $lockDir);
    $result = $runner->run();

    record($result['applied'] === 2, 'Test 1: MigrationRunner applies 2 pending migrations', "applied={$result['applied']}");
    record($result['failed'] === 0, 'Test 1: No failures on valid migrations', "failed={$result['failed']}");

    $pdo = initDb($dbPath);
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $postCount = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $pdo = null;
    record($userCount === 1, 'Test 1: users table created with data', "count={$userCount}");
    record($postCount === 0, 'Test 1: posts table created (empty)', "count={$postCount}");

    // ── Test 2: Idempotent re-run ──────────────────────────────────────
    $runner2 = new \Pit\Cuixa\Backend\Services\MigrationRunner($dbPath, $migrationsDir, $lockDir);
    $result2 = $runner2->run();

    record($result2['applied'] === 0, 'Test 2: Idempotent re-run applies 0 migrations', "applied={$result2['applied']}");
    record($result2['failed'] === 0, 'Test 2: Idempotent re-run has 0 failures', "failed={$result2['failed']}");

    // ── Test 3: Atomic failure (invalid SQL) ───────────────────────────
    resetEnv($dbPath, $migrationsDir, $lockDir);
    $pdo = initDb($dbPath);
    $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');
    $pdo = null;

    createMigration($migrationsDir, '001-good.sql', "CREATE TABLE good_table (id INTEGER PRIMARY KEY);");
    createMigration($migrationsDir, '002-bad.sql', "INVALID SQL STATEMENT HERE;");
    createMigration($migrationsDir, '003-also-good.sql', "CREATE TABLE also_good (id INTEGER PRIMARY KEY);");

    $runner3 = new \Pit\Cuixa\Backend\Services\MigrationRunner($dbPath, $migrationsDir, $lockDir);
    $result3 = $runner3->run();

    record($result3['applied'] === 2, 'Test 3: Atomic failure — 2 good migrations applied', "applied={$result3['applied']}");
    record($result3['failed'] === 1, 'Test 3: Atomic failure — 1 bad migration failed', "failed={$result3['failed']}");
    record(count($result3['errors']) === 1, 'Test 3: One error message returned');
    record(str_contains($result3['errors'][0], '002-bad.sql'), 'Test 3: Error references the failed migration file');

    $pdo = initDb($dbPath);
    $goodExists = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='good_table'")->fetchColumn();
    $alsoGoodExists = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='also_good'")->fetchColumn();
    $migrationsCount = (int) $pdo->query('SELECT COUNT(*) FROM _migrations')->fetchColumn();
    $pdo = null;
    record($goodExists === 1, 'Test 3: good_table created (before bad migration)');
    record($alsoGoodExists === 1, 'Test 3: also_good created (after bad migration — subsequent migrations still attempted)');
    record($migrationsCount === 2, 'Test 3: Failed migration NOT recorded in _migrations', "count={$migrationsCount}");

    // ── Test 4: Duplicate column idempotency ───────────────────────────
    resetEnv($dbPath, $migrationsDir, $lockDir);
    $pdo = initDb($dbPath);
    $pdo->exec('CREATE TABLE existing_table (id INTEGER PRIMARY KEY, name TEXT)');
    $pdo = null;

    createMigration($migrationsDir, '001-add-existing-column.sql', "ALTER TABLE existing_table ADD COLUMN name TEXT;");

    $runner4 = new \Pit\Cuixa\Backend\Services\MigrationRunner($dbPath, $migrationsDir, $lockDir);
    $result4 = $runner4->run();

    record($result4['applied'] === 1, 'Test 4: Duplicate column migration marked as applied', "applied={$result4['applied']}");
    record($result4['failed'] === 0, 'Test 4: Duplicate column not counted as failure', "failed={$result4['failed']}");

    // ── Test 5: Concurrency lock ───────────────────────────────────────
    resetEnv($dbPath, $migrationsDir, $lockDir);
    $pdo = initDb($dbPath);
    $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');
    $pdo = null;

    createMigration($migrationsDir, '001-test.sql', "CREATE TABLE lock_test (id INTEGER PRIMARY KEY);");

    if (!is_dir($lockDir)) {
        mkdir($lockDir, 0750, true);
    }
    $lockFile = $lockDir . '/.migrate.lock';
    $lockHandle = fopen($lockFile, 'c');
    flock($lockHandle, LOCK_EX);

    $runner5 = new \Pit\Cuixa\Backend\Services\MigrationRunner($dbPath, $migrationsDir, $lockDir);
    record($runner5->isLocked(), 'Test 5: isLocked() returns true when lock is held');

    $result5 = $runner5->run();
    record($result5['failed'] === 1, 'Test 5: run() fails when lock is held', "failed={$result5['failed']}");
    record(str_contains($result5['errors'][0], 'Migration already in progress'), 'Test 5: Error message indicates concurrent migration');

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);

    $runner5b = new \Pit\Cuixa\Backend\Services\MigrationRunner($dbPath, $migrationsDir, $lockDir);
    record(!$runner5b->isLocked(), 'Test 5: isLocked() returns false after lock released');

    $result5b = $runner5b->run();
    record($result5b['applied'] === 1, 'Test 5: Migration proceeds after lock released', "applied={$result5b['applied']}");

    // ── Test 6: getPendingMigrations ───────────────────────────────────
    resetEnv($dbPath, $migrationsDir, $lockDir);
    $pdo = initDb($dbPath);
    $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');
    $pdo = null;

    createMigration($migrationsDir, '001-first.sql', "CREATE TABLE first_table (id INTEGER PRIMARY KEY);");
    createMigration($migrationsDir, '002-second.sql', "CREATE TABLE second_table (id INTEGER PRIMARY KEY);");

    $runner6 = new \Pit\Cuixa\Backend\Services\MigrationRunner($dbPath, $migrationsDir, $lockDir);
    $pending = $runner6->getPendingMigrations();

    record(count($pending) === 2, 'Test 6: getPendingMigrations returns 2 before running', "count=" . count($pending));
    record(in_array('001-first.sql', $pending, true), 'Test 6: First migration is pending');
    record(in_array('002-second.sql', $pending, true), 'Test 6: Second migration is pending');

    $runner6->run();
    $pendingAfter = $runner6->getPendingMigrations();
    record(count($pendingAfter) === 0, 'Test 6: getPendingMigrations returns 0 after running', "count=" . count($pendingAfter));

    // ── Test 7: splitSql correctness ───────────────────────────────────
    $sql1 = "CREATE TABLE t1 (id INTEGER);\nINSERT INTO t1 VALUES (1);\n";
    $parts1 = \Pit\Cuixa\Backend\Services\MigrationRunner::splitSql($sql1);
    record(count($parts1) === 2, 'Test 7: splitSql splits on semicolon+newline', "parts=" . count($parts1));

    $sql2 = "-- comment\nCREATE TABLE t2 (id INTEGER);";
    $parts2 = \Pit\Cuixa\Backend\Services\MigrationRunner::splitSql($sql2);
    record(count($parts2) === 1, 'Test 7: splitSql strips comments', "parts=" . count($parts2));

    $sql3 = "CREATE TABLE t3 (id INTEGER)";
    $parts3 = \Pit\Cuixa\Backend\Services\MigrationRunner::splitSql($sql3);
    record(count($parts3) === 1, 'Test 7: splitSql handles single statement without trailing semicolon', "parts=" . count($parts3));

    // ── Test 8: API endpoint — 200 success with valid token ────────────
    resetEnv($dbPath, $migrationsDir, $lockDir);
    $pdo = initDb($dbPath);
    $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');
    $pdo = null;

    createMigration($migrationsDir, '001-api-test.sql', "CREATE TABLE api_test (id INTEGER PRIMARY KEY);");

    $prevAuth = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-service-token';

    ob_start();
    $migrateController = new \Pit\Cuixa\Backend\Api\Migrate($dbPath, $migrationsDir, $lockDir);
    $migrateController->handle();
    $output = ob_get_clean();

    $response = json_decode($output, true);
    record($response !== null, 'Test 8: API returns valid JSON');
    record(($response['success'] ?? false) === true, 'Test 8: API returns success=true');
    record(($response['applied'] ?? -1) === 1, 'Test 8: API reports 1 applied migration', "applied=" . ($response['applied'] ?? 'null'));
    record(($response['failed'] ?? -1) === 0, 'Test 8: API reports 0 failures', "failed=" . ($response['failed'] ?? 'null'));

    // ── Test 9: API endpoint — 401 unauthorized ────────────────────────
    unset($_SERVER['HTTP_AUTHORIZATION']);

    ob_start();
    $migrateController2 = new \Pit\Cuixa\Backend\Api\Migrate($dbPath, $migrationsDir, $lockDir);
    $migrateController2->handle();
    $output2 = ob_get_clean();

    $response2 = json_decode($output2, true);
    record($response2 !== null, 'Test 9: Unauthorized response is valid JSON');
    record(($response2['error'] ?? false) === true, 'Test 9: Unauthorized response has error=true');
    record(($response2['code'] ?? 0) === 401, 'Test 9: Unauthorized response has code=401', "code=" . ($response2['code'] ?? 'null'));

    // ── Test 10: API endpoint — 409 concurrent ─────────────────────────
    resetEnv($dbPath, $migrationsDir, $lockDir);
    $pdo = initDb($dbPath);
    $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');
    $pdo = null;

    createMigration($migrationsDir, '001-lock-test.sql', "CREATE TABLE lock_api_test (id INTEGER PRIMARY KEY);");

    if (!is_dir($lockDir)) {
        mkdir($lockDir, 0750, true);
    }
    $lockFile = $lockDir . '/.migrate.lock';
    $lockHandle = fopen($lockFile, 'c');
    flock($lockHandle, LOCK_EX);

    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-service-token';

    ob_start();
    $migrateController3 = new \Pit\Cuixa\Backend\Api\Migrate($dbPath, $migrationsDir, $lockDir);
    $migrateController3->handle();
    $output3 = ob_get_clean();

    $response3 = json_decode($output3, true);
    record($response3 !== null, 'Test 10: Concurrent response is valid JSON');
    record(($response3['error'] ?? false) === true, 'Test 10: Concurrent response has error=true');
    record(($response3['code'] ?? 0) === 409, 'Test 10: Concurrent response has code=409', "code=" . ($response3['code'] ?? 'null'));
    record(str_contains($response3['message'] ?? '', 'already in progress'), 'Test 10: Concurrent error message mentions lock');

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);

    // ── Test 11: API endpoint — partial failure with errors array ──────
    resetEnv($dbPath, $migrationsDir, $lockDir);
    $pdo = initDb($dbPath);
    $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');
    $pdo = null;

    createMigration($migrationsDir, '001-good.sql', "CREATE TABLE partial_good (id INTEGER PRIMARY KEY);");
    createMigration($migrationsDir, '002-bad.sql', "THIS IS NOT VALID SQL;");
    createMigration($migrationsDir, '003-good.sql', "CREATE TABLE partial_good2 (id INTEGER PRIMARY KEY);");

    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-service-token';

    ob_start();
    $migrateController4 = new \Pit\Cuixa\Backend\Api\Migrate($dbPath, $migrationsDir, $lockDir);
    $migrateController4->handle();
    $output4 = ob_get_clean();

    $response4 = json_decode($output4, true);
    record($response4 !== null, 'Test 11: Partial failure response is valid JSON');
    record(($response4['success'] ?? true) === false, 'Test 11: Partial failure has success=false');
    record(($response4['applied'] ?? -1) === 2, 'Test 11: Partial failure reports 2 applied', "applied=" . ($response4['applied'] ?? 'null'));
    record(($response4['failed'] ?? -1) === 1, 'Test 11: Partial failure reports 1 failed', "failed=" . ($response4['failed'] ?? 'null'));
    record(isset($response4['errors']) && is_array($response4['errors']), 'Test 11: Partial failure includes errors array');
    record(count($response4['errors'] ?? []) === 1, 'Test 11: Errors array has 1 entry', "count=" . count($response4['errors'] ?? []));

    // Restore auth header
    if ($prevAuth === null) {
        unset($_SERVER['HTTP_AUTHORIZATION']);
    } else {
        $_SERVER['HTTP_AUTHORIZATION'] = $prevAuth;
    }

} catch (\Throwable $e) {
    record(false, 'Unexpected exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
} finally {
    foreach ([$dbPath, $dbPath . '-wal', $dbPath . '-shm'] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    if (is_dir($migrationsDir)) {
        foreach (glob($migrationsDir . '/*.sql') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($migrationsDir);
    }
    $lockFile = $lockDir . '/.migrate.lock';
    if (is_file($lockFile)) {
        @unlink($lockFile);
    }
    if (is_dir($lockDir)) {
        @rmdir($lockDir);
    }
    status('!', 'Temp files removed');
}

echo "\n";
$summary = "{$results['pass']} passed, {$results['fail']} failed.";
status($results['fail'] > 0 ? '✗' : '✓', $summary);
exit($results['fail'] > 0 ? 1 : 0);
