<?php
/**
 * Pit o Cuixa — Cron Menu Sync Trigger
 *
 * HTTP endpoint for dinahosting's "Tareas programadas" scheduler.
 * Reads SERVICE_API_TOKEN from .env (one level above www/) and
 * POSTs to /api/update-menu. Token is never committed to code.
 *
 * Usage (from scheduler): wget -q -O /dev/null https://pitocuixa.es/cron-trigger.php
 *
 * @package Pit\Cuixa\Scripts
 */

declare(strict_types=1);

// Read .env from project root (one level above www/)
$envPath = dirname(__DIR__) . '/.env';
if (!is_file($envPath)) {
    http_response_code(500);
    echo "ERROR: .env not found\n";
    exit(1);
}

$token = '';
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    $parts = explode('=', $line, 2);
    if (count($parts) === 2 && trim($parts[0]) === 'SERVICE_API_TOKEN') {
        $token = trim($parts[1]);
        break;
    }
}

if ($token === '') {
    http_response_code(500);
    echo "ERROR: SERVICE_API_TOKEN not found in .env\n";
    exit(1);
}

// Read SITE_URL from .env (fallback to https://pitocuixa.es)
$siteUrl = 'https://pitocuixa.es';
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    $parts = explode('=', $line, 2);
    if (count($parts) === 2 && trim($parts[0]) === 'SITE_URL') {
        $siteUrl = trim($parts[1]);
        break;
    }
}

// POST to /api/update-menu with service token
$url = rtrim($siteUrl, '/') . '/api/update-menu';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 120,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
]);

$raw   = curl_exec($ch);
$http  = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$error = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);

if ($errno !== 0) {
    http_response_code(500);
    echo "ERROR: curl failed — {$error}\n";
    exit(1);
}

if ($http >= 200 && $http < 300) {
    echo "OK: HTTP {$http}\n";
    exit(0);
}

http_response_code(502);
echo "FAILED: HTTP {$http} — {$raw}\n";
exit(1);
