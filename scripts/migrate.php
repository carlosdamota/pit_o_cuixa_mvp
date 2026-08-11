<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/shared/bootstrap.php';

use Pit\Cuixa\Backend\Services\MigrationRunner;

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
echo "Migrations: " . realpath(__DIR__ . '/../db/migrations') . "\n";
echo "\n";

if (!is_file($dbPath)) {
    status('✗', "Database not found at {$dbPath}. Run `php scripts/setup.php` first.");
    exit(1);
}

$runner = new MigrationRunner();

if ($runner->isLocked()) {
    status('✗', 'Migration already in progress (lock held).');
    exit(1);
}

$result = $runner->run();

$applied = $result['applied'];
$failed  = $result['failed'];
$errors  = $result['errors'];

foreach ($errors as $error) {
    status('✗', $error);
}

echo "\n";
if ($applied === 0 && $failed === 0) {
    status('✓', 'Nothing to migrate. All migrations already applied.');
} else {
    status('✓', "{$applied} migration(s) applied.");
    if ($failed > 0) {
        status('✗', "{$failed} migration(s) failed.");
    }
}
echo "\n";

exit($failed > 0 ? 1 : 0);
