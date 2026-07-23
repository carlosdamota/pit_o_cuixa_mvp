<?php
/**
 * Pit o Cuixa — Setup Script
 *
 * CLI script to initialise the database and create the first admin user.
 * Run from project root: php scripts/setup.php
 *
 * Steps:
 *   1. Create data/ directory if not exists
 *   2. Create SQLite database file
 *   3. Run schema.sql (creates tables + seeds initial data)
 *   4. Prompt for admin password (or generate random one)
 *   5. Create admin user with password_hash()
 *   6. Print success message with admin credentials
 *
 * @package Pit\Cuixa\Scripts
 */

declare(strict_types=1);

// ── Prevent web access ───────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from the command line.\n";
    exit(1);
}

// ── Helper: Read a line from stdin ───────────────────────────────────────
function prompt(string $message): string
{
    echo $message;
    $handle = fopen('php://stdin', 'r');
    if ($handle === false) {
        echo "\n[ERROR] Cannot read from stdin.\n";
        exit(1);
    }
    $line = trim(fgets($handle));
    fclose($handle);
    return $line;
}

// ── Helper: Print coloured status ────────────────────────────────────────
function status(string $label, string $message): void
{
    $green = "\033[32m";
    $red   = "\033[31m";
    $reset = "\033[0m";
    $color = str_starts_with($label, '✓') ? $green : ($label === '✗' ? $red : '');
    echo "{$color}[{$label}]{$reset} {$message}\n";
}

// ── Banner ───────────────────────────────────────────────────────────────
echo "\n";
echo "  ╔══════════════════════════════════╗\n";
echo "  ║     Pit o Cuixa — Setup          ║\n";
echo "  ║     Pollería y rostería           ║\n";
echo "  ║     Torredembarra, Tarragona     ║\n";
echo "  ╚══════════════════════════════════╝\n";
echo "\n";

// ── 1. Determine paths ───────────────────────────────────────────────────
$projectRoot = dirname(__DIR__);
$dataDir     = $projectRoot . '/data';
$dbPath      = $dataDir . '/pitocuixa.db';
$schemaPath  = $projectRoot . '/db/schema.sql';

echo "Project root: {$projectRoot}\n";
echo "Data dir:    {$dataDir}\n";
echo "Database:    {$dbPath}\n";
echo "Schema:      {$schemaPath}\n";
echo "\n";

// ── 2. Create data/ directory ────────────────────────────────────────────
if (is_dir($dataDir)) {
    status('✓', 'Data directory already exists.');
} else {
    if (mkdir($dataDir, 0750, true)) {
        status('✓', 'Created data/ directory.');
    } else {
        status('✗', "Failed to create data/ directory: {$dataDir}");
        exit(1);
    }
}

// ── 3. Check if DB already exists ────────────────────────────────────────
$isNewDb = !is_file($dbPath);

if ($isNewDb) {
    echo "Creating new database...\n";
} else {
    echo "Database already exists. Schema will be applied (CREATE IF NOT EXISTS).\n";
}

// ── 4. Validate schema file ──────────────────────────────────────────────
if (!is_file($schemaPath)) {
    status('✗', "Schema file not found: {$schemaPath}");
    exit(1);
}

// ── 5. Open SQLite connection ────────────────────────────────────────────
try {
    $pdo = new \PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    status('✓', 'Database connection opened.');
} catch (\PDOException $e) {
    status('✗', "Cannot open database: " . $e->getMessage());
    exit(1);
}

// ── 6. Run schema.sql ────────────────────────────────────────────────────
echo "\nRunning schema...\n";

$schemaSql = file_get_contents($schemaPath);

if ($schemaSql === false) {
    status('✗', "Cannot read schema file: {$schemaPath}");
    exit(1);
}

// Split by semicolons and execute each statement separately
$statements = explode(';', $schemaSql);
$executed   = 0;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '' || str_starts_with($stmt, '--')) {
        continue;
    }

    try {
        $pdo->exec($stmt);
        $executed++;
    } catch (\PDOException $e) {
        // Ignore "already exists" errors for tables and indexes
        $msg = $e->getMessage();
        if (str_contains($msg, 'already exists')) {
            $executed++;
            continue;
        }
        status('✗', "SQL error: " . $e->getMessage());
        echo "  Statement: " . substr($stmt, 0, 80) . "...\n";
        exit(1);
    }
}

status('✓', "Schema executed ({$executed} statements).");

if ($isNewDb) {
    status('✓', 'Seed data inserted (categories + products).');
} else {
    echo "Existing database: seed data already present (INSERT OR IGNORE not used — duplicates may be skipped).\n";
}

// ── 7. Check existing admin users ────────────────────────────────────────
$stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM users WHERE role = \'admin\'');
$adminCount = (int) $stmt->fetch()['cnt'];

if ($adminCount > 0) {
    echo "\n";
    status('✓', "Admin user already exists ({$adminCount} admin user(s) found).");
    $createAnother = strtolower(trim(prompt('Create another admin user? (y/N): ')));

    if ($createAnother !== 'y' && $createAnother !== 'yes') {
        echo "\n";
        status('✓', 'Setup complete. Existing admin user(s) can log in.');
        echo "\n";
        exit(0);
    }
}

// ── 8. Get admin credentials ─────────────────────────────────────────────
echo "\n── Admin User Creation ──\n\n";

$username = prompt('Username [admin]: ');
$username = $username === '' ? 'admin' : $username;

$displayName = prompt('Display name [Admin]: ');
$displayName = $displayName === '' ? 'Admin' : $displayName;

$password = '';
$confirm  = '';

echo "\nPassword (leave empty to generate a random one):\n";
$password = prompt('Password: ');

if ($password === '') {
    // Generate a random 16-character password
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
    $password = '';
    for ($i = 0; $i < 16; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    echo "Generated password: {$password}\n";
    echo "⚠  COPY THIS PASSWORD NOW — it will not be shown again.\n";
} else {
    $confirm = prompt('Confirm password: ');
    if ($password !== $confirm) {
        status('✗', 'Passwords do not match.');
        exit(1);
    }
}

// ── 9. Create admin user ─────────────────────────────────────────────────
try {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password, display_name, role, is_active) VALUES (:u, :p, :d, :r, 1)'
    );
    $stmt->execute([
        ':u' => $username,
        ':p' => $hash,
        ':d' => $displayName,
        ':r' => 'admin',
    ]);

    status('✓', "Admin user '{$username}' created successfully.");
} catch (\PDOException $e) {
    if (str_contains($e->getMessage(), 'UNIQUE constraint')) {
        status('✗', "User '{$username}' already exists.");
    } else {
        status('✗', "Error creating user: " . $e->getMessage());
    }
    exit(1);
}

// ── 10. Success message ──────────────────────────────────────────────────
echo "\n";
echo "  ╔══════════════════════════════════════════╗\n";
echo "  ║         Setup Complete!                   ║\n";
echo "  ╚══════════════════════════════════════════╝\n";
echo "\n";
echo "  Database:  {$dbPath}\n";
echo "  Username:  {$username}\n";
echo "  Password:  (see above — generated or entered)\n";
echo "\n";
echo "  Admin URL: https://your-domain/admin/\n";
echo "\n";
echo "  IMPORTANT:\n";
echo "  - Copy .env.example to .env and configure your settings.\n";
echo "  - Ensure the data/ directory is writable by the web server.\n";
echo "  - The public/ directory is the web root.\n";
echo "\n";
