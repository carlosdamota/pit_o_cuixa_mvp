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

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? dirname(__DIR__, 3) . '/data/click-limits';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0750, true);
        }
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

        $file = $this->storageDir . '/' . md5($key) . '.json';
        $now  = time();

        $clicks = [];
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded) && isset($decoded['clicks']) && is_array($decoded['clicks'])) {
                $clicks = $decoded['clicks'];
            }
        }

        // Evict every timestamp that has fallen out of the sliding window.
        $cutoff        = $now - $window;
        $clicks        = array_values(array_filter(
            $clicks,
            static fn (int $timestamp): bool => $timestamp > $cutoff
        ));

        if (count($clicks) >= $max) {
            // Denied: the window is full. The request is NOT recorded.
            // retryAfter = seconds until the oldest click leaves the window.
            $oldest     = min($clicks);
            $retryAfter = max(1, $oldest - $cutoff);

            // Persist the eviction so repeated denials don't grow the file.
            file_put_contents($file, json_encode(['clicks' => $clicks]), LOCK_EX);

            return ['allowed' => false, 'retryAfter' => $retryAfter];
        }

        // Allowed: record this click and keep going.
        $clicks[] = $now;
        file_put_contents($file, json_encode(['clicks' => $clicks]), LOCK_EX);

        return ['allowed' => true, 'retryAfter' => 0];
    }
}