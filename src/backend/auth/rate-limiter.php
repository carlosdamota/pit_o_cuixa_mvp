<?php
/**
 * Pit o Cuixa — Rate Limiter
 *
 * Simple file-based rate limiter for brute-force protection.
 * Stores attempt timestamps in JSON files keyed by MD5 hash.
 *
 * @package Pit\Cuixa\Backend\Auth
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Auth;

class RateLimiter
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? dirname(__DIR__, 3) . '/data/rate-limits';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0750, true);
        }
    }

    /**
     * Check if an action is rate-limited.
     *
     * @param  string $key           Unique key (e.g., "login:ip:1.2.3.4")
     * @param  int    $maxAttempts   Maximum attempts allowed in window
     * @param  int    $windowSeconds Time window in seconds
     * @return array{allowed: bool, retryAfter: int}
     */
    public function check(string $key, int $maxAttempts = 5, int $windowSeconds = 60): array
    {
        $file = $this->storageDir . '/' . md5($key) . '.json';
        $now  = time();

        $data = ['attempts' => [], 'locked_until' => 0];
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
        }

        // Check lock
        if ($data['locked_until'] > $now) {
            return ['allowed' => false, 'retryAfter' => $data['locked_until'] - $now];
        }

        // Clean old attempts outside window
        $data['attempts'] = array_values(array_filter($data['attempts'], fn($t) => $t > $now - $windowSeconds));

        if (count($data['attempts']) >= $maxAttempts) {
            // Lock for 15 minutes
            $data['locked_until'] = $now + 900;
            file_put_contents($file, json_encode($data), LOCK_EX);
            return ['allowed' => false, 'retryAfter' => 900];
        }

        return ['allowed' => true, 'retryAfter' => 0];
    }

    /**
     * Record a failed attempt.
     */
    public function recordFailure(string $key): void
    {
        $file = $this->storageDir . '/' . md5($key) . '.json';
        $now  = time();

        $data = ['attempts' => [], 'locked_until' => 0];
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
        }

        $data['attempts'][] = $now;
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Reset attempts on successful login.
     */
    public function reset(string $key): void
    {
        $file = $this->storageDir . '/' . md5($key) . '.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
