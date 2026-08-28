<?php
declare(strict_types=1);

namespace Pit\Cuixa\Backend\Auth;

/**
 * RFC 6238 TOTP (Google Authenticator compatible).
 *
 * Generates shared secrets, builds otpauth provisioning URIs, and verifies
 * codes with constant-time comparison inside a configurable drift window.
 * Pure PHP built-ins only — no external dependencies.
 */
final class Totp
{
    public const DIGIT = 6;
    public const PERIOD = 30;
    public const SECRET_BYTES = 20;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const ISSUER = 'Pit o Cuixa';

    /**
     * Generate a new random, base32-encoded shared secret.
     */
    public static function generateSecret(): string
    {
        $raw = random_bytes(self::SECRET_BYTES);

        return self::base32Encode($raw);
    }

    /**
     * Build a standard otpauth://totp/ provisioning URI.
     *
     * @param string $secret      Base32-encoded shared secret.
     * @param string $accountName Admin email / username (used as the label).
     */
    public static function getProvisioningUri(string $secret, string $accountName): string
    {
        $label = self::ISSUER . ':' . $accountName;

        $query = http_build_query([
            'secret' => $secret,
            'issuer' => self::ISSUER,
            'period' => self::PERIOD,
            'digits' => self::DIGIT,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/' . rawurlencode($label) . '?' . $query;
    }

    /**
     * Verify a TOTP code against the shared secret within a drift window.
     *
     * @param string $secret Base32-encoded shared secret.
     * @param string $code   User-supplied code (whitespace tolerated).
     * @param int    $drift  Allowed window steps before/after the current one.
     */
    public static function verify(string $secret, string $code, int $drift = 1): bool
    {
        $code = trim($code);

        if ($code === '' || !ctype_digit($code) || strlen($code) !== self::DIGIT) {
            return false;
        }

        $key = self::base32Decode($secret);
        $counter = (int) floor(time() / self::PERIOD);

        for ($step = -$drift; $step <= $drift; $step++) {
            $candidate = self::hotp($key, $counter + $step);

            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * HMAC-based one-time password for a given 64-bit time counter.
     */
    private static function hotp(string $key, int $counter): string
    {
        // pack('J') (unsigned 64-bit BE) throws ValueError on 32-bit PHP
        // builds (e.g. dinahosting's i586). Encode the 64-bit big-endian
        // counter as two unsigned 32-bit halves instead — identical bytes
        // on every PHP build. On 32-bit PHP the counter can never reach
        // 2^32, so the high word is always zero there.
        $hi = PHP_INT_SIZE === 8 ? ($counter >> 32) : 0;
        $lo = $counter & 0xFFFFFFFF;
        $counterBytes = pack('NN', $hi, $lo);

        $hmac = hash_hmac('sha1', $counterBytes, $key, true);

        $offset = ord($hmac[19]) & 0x0F;
        $binary = (unpack('N', substr($hmac, $offset, 4))[1]) & 0x7FFFFFFF;

        $code = $binary % (10 ** self::DIGIT);

        return str_pad((string) $code, self::DIGIT, '0', STR_PAD_LEFT);
    }

    /**
     * Base32-encode raw bytes (RFC 4648, no padding).
     */
    private static function base32Encode(string $raw): string
    {
        $alphabet = self::BASE32_ALPHABET;
        $output = '';
        $buffer = 0;
        $bits = 0;

        for ($i = 0, $len = strlen($raw); $i < $len; $i++) {
            $buffer = ($buffer << 8) | ord($raw[$i]);
            $bits += 8;

            while ($bits >= 5) {
                $bits -= 5;
                $output .= $alphabet[($buffer >> $bits) & 0x1F];
            }
        }

        if ($bits > 0) {
            $output .= $alphabet[($buffer << (5 - $bits)) & 0x1F];
        }

        return $output;
    }

    /**
     * Base32-decode to raw bytes (uppercase normalization, padding stripped).
     */
    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper(str_replace('=', '', $secret));
        $alphabet = self::BASE32_ALPHABET;
        $output = '';
        $buffer = 0;
        $bits = 0;

        for ($i = 0, $len = strlen($secret); $i < $len; $i++) {
            $pos = strpos($alphabet, $secret[$i]);

            if ($pos === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $pos;
            $bits += 5;

            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $output;
    }
}
