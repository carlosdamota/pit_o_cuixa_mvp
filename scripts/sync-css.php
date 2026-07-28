<?php
/**
 * Pit o Cuixa — Sync CSS Assets
 *
 * Mirrors src/frontend/css/ → public/css/ so the web server
 * always serves the latest stylesheets.
 *
 * Run from project root: php scripts/sync-css.php
 *
 * @package Pit\Cuixa\Scripts
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from the command line.\n";
    exit(1);
}

$projectRoot = dirname(__DIR__);
$sourceDir   = $projectRoot . '/src/frontend/css';
$targetDir   = $projectRoot . '/public/css';

if (!is_dir($sourceDir)) {
    echo "[ERROR] Source directory not found: {$sourceDir}\n";
    exit(1);
}
if (!is_dir($targetDir)) {
    echo "[ERROR] Target directory not found: {$targetDir}\n";
    exit(1);
}

// ── Coloured output ─────────────────────────────────────────────────────
function ok(string $msg): void { echo "\033[32m[✓]\033[0m {$msg}\n"; }
function info(string $msg): void { echo "\033[36m[~]\033[0m {$msg}\n"; }
function fail(string $msg): void { echo "\033[31m[✗]\033[0m {$msg}\n"; }

// ── Recursive directory iterator — exclude hidden files ─────────────────
$iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS)
);

$copied   = 0;
$skipped  = 0;
$failed   = 0;

info("Syncing {$sourceDir} → {$targetDir}\n");

foreach ($iterator as $file) {
    /** @var \SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }

    $relativePath = \str_replace($sourceDir . \DIRECTORY_SEPARATOR, '', $file->getPathname());
    $targetFile   = $targetDir . \DIRECTORY_SEPARATOR . $relativePath;
    $targetPath   = \dirname($targetFile);

    // Ensure target subdirectory exists
    if (!is_dir($targetPath)) {
        if (!mkdir($targetPath, 0755, true)) {
            fail("Cannot create directory: {$targetPath}");
            $failed++;
            continue;
        }
    }

    // Copy if source is newer or target doesn't exist
    if (!is_file($targetFile) || filemtime($file->getPathname()) > filemtime($targetFile)) {
        if (copy($file->getPathname(), $targetFile)) {
            ok("{$relativePath}");
            $copied++;
        } else {
            fail("Failed to copy: {$relativePath}");
            $failed++;
        }
    } else {
        $skipped++;
    }
}

echo "\n";
ok("{$copied} file(s) copied.");
if ($skipped > 0) {
    info("{$skipped} file(s) up to date — skipped.");
}
if ($failed > 0) {
    fail("{$failed} file(s) failed.");
}

exit($failed > 0 ? 1 : 0);
