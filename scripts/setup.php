<?php
/**
 * Pit o Cuixa — Setup Script
 *
 * CLI script to initialise the database and create the first admin user.
 * Run from project root: php scripts/setup.php [options]
 *
 * Options:
 *   -f, --fresh      Wipe and recreate SQLite database from scratch
 *   -s, --scrape     Run web scraper to populate products from external menu
 *   -t, --translate  Run DeepL batch translator for missing fields (CA, EN, UK)
 *   -h, --help       Show help message
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

// ── Parse CLI flags ──────────────────────────────────────────────────────
$options = getopt('fsth', ['fresh', 'scrape', 'translate', 'help']);

$flagFresh     = isset($options['f']) || isset($options['fresh']);
$flagScrape    = isset($options['s']) || isset($options['scrape']);
$flagTranslate = isset($options['t']) || isset($options['translate']);
$flagHelp      = isset($options['h']) || isset($options['help']);

if ($flagHelp) {
    echo "\n";
    echo "  ╔══════════════════════════════════╗\n";
    echo "  ║     Pit o Cuixa — Setup Help     ║\n";
    echo "  ╚══════════════════════════════════╝\n\n";
    echo "Usage:\n";
    echo "  php scripts/setup.php [options]\n\n";
    echo "Options:\n";
    echo "  -f, --fresh      Wipe and recreate SQLite database from scratch\n";
    echo "  -s, --scrape     Run web scraper to populate products from external menu\n";
    echo "  -t, --translate  Run DeepL batch translator for missing fields (CA, EN, UK)\n";
    echo "  -h, --help       Show this help message\n\n";
    exit(0);
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
    $line = fgets($handle);
    fclose($handle);
    return $line === false ? '' : trim($line);
}

// ── Helper: Print coloured status ────────────────────────────────────────
function status(string $label, string $message): void
{
    $green  = "\033[32m";
    $yellow = "\033[33m";
    $red    = "\033[31m";
    $reset  = "\033[0m";
    $color  = match ($label) {
        '✓' => $green,
        '!' => $yellow,
        '✗' => $red,
        default => '',
    };
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

// ── 2. Handle --fresh ────────────────────────────────────────────────────
if ($flagFresh && is_file($dbPath)) {
    if (unlink($dbPath)) {
        status('✓', 'Removed existing database (--fresh).');
    } else {
        status('✗', "Failed to remove database: {$dbPath}");
        exit(1);
    }
}

// ── 3. Create data/ directory ────────────────────────────────────────────
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

// ── 4. Check if DB already exists ────────────────────────────────────────
$isNewDb = !is_file($dbPath);

if ($isNewDb) {
    echo "Creating new database...\n";
} else {
    echo "Database already exists. Schema will be applied (CREATE IF NOT EXISTS).\n";
}

// ── 5. Validate schema file ──────────────────────────────────────────────
if (!is_file($schemaPath)) {
    status('✗', "Schema file not found: {$schemaPath}");
    exit(1);
}

// ── 6. Open SQLite connection ────────────────────────────────────────────
try {
    $pdo = new \PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    status('✓', 'Database connection opened.');
} catch (\PDOException $e) {
    status('✗', "Cannot open database: " . $e->getMessage());
    exit(1);
}

// ── 7. Run schema.sql ────────────────────────────────────────────────────
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
    
    // Remove comment lines
    $lines = explode("\n", $stmt);
    $codeLines = [];
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        if ($trimmedLine !== '' && !str_starts_with($trimmedLine, '--')) {
            $codeLines[] = $line;
        }
    }
    $stmt = trim(implode("\n", $codeLines));
    
    if ($stmt === '') {
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

// ── 8. Check existing admin users ────────────────────────────────────────
$adminCount = 0;
try {
    $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM users WHERE role = \'admin\'');
    $adminCount = (int) $stmt->fetch()['cnt'];
} catch (\PDOException $e) {
    $adminCount = 0;
}

if ($adminCount > 0) {
    echo "\n";
    status('✓', "Admin user already exists ({$adminCount} admin user(s) found).");
    $createAnother = strtolower(trim(prompt('Create another admin user? (y/N): ')));

    if ($createAnother !== 'y' && $createAnother !== 'yes') {
        echo "\n";
        status('✓', 'Existing admin user(s) preserved.');
    }
} else {
    // ── 9. Get admin credentials ─────────────────────────────────────────────
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
}

// ── 10. Optional Scrape & Translate ──────────────────────────────────────
if ($flagScrape || $flagTranslate) {
    require_once $projectRoot . '/src/shared/bootstrap.php';
}

if ($flagScrape) {
    echo "\n── Running Web Scraper ──\n\n";
    try {
        $scraper  = new \Pit\Cuixa\Backend\Api\WebScraper();
        $repo     = new \Pit\Cuixa\Backend\Db\Repositories\Product();
        $products = $scraper->scraper();
        $repo->sync($products);
        status('✓', 'Web Scraper executed successfully (' . count($products) . ' products synced).');
    } catch (\Throwable $e) {
        status('✗', 'Web Scraper failed: ' . $e->getMessage());
    }
}

if ($flagTranslate) {
    echo "\n── Running DeepL Translator ──\n\n";
    $apiKey = getenv('DEEPL_API_KEY');
    if (empty($apiKey)) {
        status('!', 'DEEPL_API_KEY is not defined in your .env file.');
        echo "  Please add your DeepL API key to your local .env file:\n";
        echo "  DEEPL_API_KEY=your_key_here:fx\n\n";
    } else {
        try {
            $translator = new \Pit\Cuixa\Backend\Services\MenuTranslator($apiKey);
            status('!', 'Checking categories and products for missing translations...');
            $stats = $translator->translateMissing();
            status('✓', "Translation complete: {$stats['categories']} category fields and {$stats['products']} product fields translated.");
        } catch (\Throwable $e) {
            status('✗', 'Translation error: ' . $e->getMessage());
        }
    }
}

// ── 11. Success message ──────────────────────────────────────────────────
echo "\n";
echo "  ╔══════════════════════════════════════════╗\n";
echo "  ║         Setup Complete!                  ║\n";
echo "  ╚══════════════════════════════════════════╝\n";
echo "\n";
echo "  Database:  {$dbPath}\n";
echo "\n";
echo "  Admin URL: https://your-domain/admin/\n";
echo "\n";
echo "  IMPORTANT:\n";
echo "  - Copy .env.example to .env and configure your settings.\n";
echo "  - Ensure the data/ directory is writable by the web server.\n";
echo "  - The public/ directory is the web root.\n";
echo "\n";
