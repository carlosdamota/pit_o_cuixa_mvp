<?php
/**
 * Pit o Cuixa — Windowed Click Rate Limiter
 *
 * A lightweight sliding-window limit for product click counting. Distinct
 * from `RateLimiter` (the login brute-force guard): this class enforces a
 * per-key cap over a short window and deliberately has NO hard lock, so a
 * shared restaurant/cafe IP behind NAT is only throttled from click counting,
 * never locked out for 15 minutes.
 *
 * State is stored as one JSON file per key (timestamps of permitted clicks)
 * under data/click-limits/. Timestamps older than the window are evicted on
 * every check, so the limit naturally resets as old clicks fall out.
 *
 * @package Pit\Cuixa\Backend\Auth
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Auth;

class ClickRateLimiter
{
    private string $storageDir;
    private bool $storageOk = true;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? dirname(__DIR__, 3) . '/data/click-limits';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0750, true);
        }
        // Fail closed when storage is unusable: a silently self-disabling rate
        // limit is worse than no limit. read/write failures are caught in
        // allow() and reported back so the caller can deny the request.
        $this->storageOk = is_dir($this->storageDir) && is_writable($this->storageDir);
    }

    /**
     * Whether the backing storage is writable right now.
     *
     * @return bool
     */
    public function storageAvailable(): bool
    {
        return $this->storageOk;
    }

    /**
     * Decide whether a request belonging to a key is allowed within a sliding
     * window, and record it when allowed.
     *
     * @param  string $key    Unique key, e.g. "click:ip:1.2.3.4"
     * @param  int    $max    Max allowed requests in the window (default 20)
     * @param  int    $window Window length in seconds (default 60)
     * @return array{allowed: bool, retryAfter: int}
     */
    public function allow(string $key, int $max = 20, int $window = 60): array
    {
        $max    = max(1, $max);
        $window = max(1, $window);

        // Fail closed: if we cannot reliably enforce the limit, deny.
        if (!$this->storageOk) {
            return ['allowed' => false, 'retryAfter' => $window, 'storage' => 'unavailable'];
        }

        $file = $this->storageDir . '/' . md5($key) . '.json';
        $now  = time();
        $cutoff = $now - $window;

        // Acquire an exclusive lock around the ENTIRE read-modify-write so two
        // concurrent requests from the same key cannot both read the same state
        // and both be admitted (20/60s cap stays enforced under bursts).
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            return ['allowed' => false, 'retryAfter' => $window, 'storage' => 'unavailable'];
        }
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return ['allowed' => false, 'retryAfter' => $window, 'storage' => 'lock-failed'];
        }

        $clicks = [];
        $raw = stream_get_contents($handle);
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['clicks']) && is_array($decoded['clicks'])) {
                $clicks = $decoded['clicks'];
            }
        }

        // Evict every timestamp that has fallen out of the sliding window.
        $clicks = array_values(array_filter(
            $clicks,
            static fn (int $timestamp): bool => $timestamp > $cutoff
        ));

        $allowed       = count($clicks) < $max;
        $retryAfter    = 0;

        if ($allowed) {
            // Record this click.
            $clicks[] = $now;
        } elseif (count($clicks) > 0) {
            // Denied: window full. retryAfter = seconds until oldest leaves.
            $retryAfter = max(1, min($clicks) - $cutoff);
        } else {
            $retryAfter = $window;
        }

        // Rewind and write under the held lock, truncating leftover bytes.
        $payload = json_encode(['clicks' => $clicks]);
        if ($payload === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return ['allowed' => false, 'retryAfter' => $window, 'storage' => 'encode-failed'];
        }
        rewind($handle);
        $writeOk = (bool) ftruncate($handle, 0)
            && fwrite($handle, $payload) !== false;
        flock($handle, LOCK_UN);
        fclose($handle);

        // If writing failed, do not pretend we enforced anything: deny.
        if (!$writeOk) {
            return ['allowed' => false, 'retryAfter' => $window, 'storage' => 'write-failed'];
        }

        return ['allowed' => $allowed, 'retryAfter' => $retryAfter];
    }
}