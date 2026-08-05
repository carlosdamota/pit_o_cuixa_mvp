<?php
/**
 * Pit o Cuixa — Cron Menu Sync CLI Script
 *
 * Session-free, credential-based menu sync for unattended cron runs
 * (CRON-3 / AUTH-2). Instead of running the sync logic locally it POSTs to
 * the running site's `/api/update-menu` endpoint, presenting the
 * SERVICE_API_TOKEN as a Bearer credential. This keeps a single
 * authentication path (the HTTP route gate) and never depends on an admin
 * session.
 *
 *   - Reads the service token + site URL from Config (src/shared/config.php).
 *   - POSTs to {siteUrl}/api/update-menu with `Authorization: Bearer <token>`.
 *   - Logs the outcome to data/cron-sync.log.
 *   - Exits 0 on success (HTTP 2xx with status ok), 1 otherwise.
 *
 * Fail-closed (AUTH-2): if SERVICE_API_TOKEN is unset/empty, the sync is NOT
 * attempted and the script exits 1 — a blank credential can never authorize
 * a sync.
 *
 * Intended to run:
 *   - twice daily at 00:00 and 12:00 via cron (scripts/install-cron.sh)
 *   - manually: php scripts/cron-sync.php
 *
 * @package Pit\Cuixa\Scripts
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../src/shared/bootstrap.php';

/**
 * Append a timestamped line to the cron-sync log (data/cron-sync.log).
 *
 * @param string $message Log line
 * @return void
 */
function cronLog(string $message): void
{
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($dir . '/cron-sync.log', $line, FILE_APPEND);
}

/**
 * Perform the credential-based menu sync against the live site.
 *
 * @param string $siteUrl Base site URL (no trailing slash)
 * @param string $token   Service API token (non-empty, fail-closed upstream)
 * @return array{http: int, body: array<string,mixed>, error: string}
 */
function cronSync(string $siteUrl, string $token): array
{
    $url = rtrim($siteUrl, '/') . '/api/update-menu';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    $raw   = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $http  = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        return ['http' => 0, 'body' => [], 'error' => "curl error {$errno}: {$error}"];
    }

    $decoded = json_decode((string) $raw, true);
    return [
        'http'  => $http,
        'body'  => is_array($decoded) ? $decoded : [],
        'error' => '',
    ];
}

// ── Run ─────────────────────────────────────────────────────────────────

$token = Config::serviceApiToken();

if ($token === '') {
    cronLog('ABORT: SERVICE_API_TOKEN is unset/empty — sync refused (fail-closed, AUTH-2).');
    fwrite(STDERR, "[cron-sync] ABORT: SERVICE_API_TOKEN is unset/empty.\n");
    exit(1);
}

$siteUrl = Config::siteUrl();
cronLog("Starting menu sync against {$siteUrl}/api/update-menu");

$result = cronSync($siteUrl, $token);

if ($result['error'] !== '' || $result['http'] === 0) {
    $error = $result['error'] !== '' ? $result['error'] : "no HTTP response ({$result['http']})";
    cronLog("FAILED: {$error}");
    fwrite(STDERR, "[cron-sync] FAILED: {$error}\n");
    exit(1);
}

$status = $result['body']['status'] ?? null;
$isOk   = $result['http'] >= 200 && $result['http'] < 300 && $status === 'ok';

if ($isOk) {
    $translated = $result['body']['translated'] ?? [];
    cronLog("OK: HTTP {$result['http']}, status=ok, translated=" . json_encode($translated));
    echo "[cron-sync] OK: HTTP {$result['http']}, status=ok\n";
    exit(0);
}

$error = json_encode($result['body']) ?: 'unparseable response';
cronLog("FAILED: HTTP {$result['http']}, body={$error}");
fwrite(STDERR, "[cron-sync] FAILED: HTTP {$result['http']}, body={$error}\n");
exit(1);