<?php
/**
 * Pit o Cuixa — Crypto helpers
 *
 * Encryption of sensitive values at rest (currently the TOTP shared secret).
 * Uses OpenSSL AES-256-GCM. The key comes from Config::totpEncryptionKey()
 * (a 32-byte hex string -> 32 raw bytes).
 *
 * Wire format (base64): [12-byte IV][16-byte GCM tag][ciphertext]
 *
 * @package Pit\Cuixa\Backend\Auth
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Auth;

use Config;

final class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_BYTES = 12;   // Recommended IV length for GCM
    private const TAG_BYTES = 16;  // Default GCM auth tag length

    /**
     * Encrypt a plaintext string (e.g. a base32 TOTP secret).
     *
     * @throws \RuntimeException If encryption fails (e.g. missing openssl)
     */
    public static function encrypt(string $plaintext): string
    {
        $key = self::key();
        $iv  = random_bytes(self::IV_BYTES);

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false || $tag === null) {
            throw new \RuntimeException('Crypto::encrypt() failed — openssl unavailable or key invalid.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a value produced by self::encrypt().
     *
     * @throws \RuntimeException If the payload is malformed or decryption fails
     */
    public static function decrypt(string $payload): string
    {
        $key = self::key();
        $raw = base64_decode($payload, true);

        $minLen = self::IV_BYTES + self::TAG_BYTES;
        if ($raw === false || strlen($raw) < $minLen) {
            throw new \RuntimeException('Crypto::decrypt() — malformed payload.');
        }

        $iv          = substr($raw, 0, self::IV_BYTES);
        $tag         = substr($raw, self::IV_BYTES, self::TAG_BYTES);
        $ciphertext  = substr($raw, $minLen);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Crypto::decrypt() — authentication failed (wrong key/tampered data).');
        }

        return $plaintext;
    }

    /**
     * Resolve and validate the 32-byte raw key.
     */
    private static function key(): string
    {
        $hex = Config::totpEncryptionKey();
        $key = hex2bin($hex);

        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException('Config::totpEncryptionKey() must be a 64-char hex string (32 bytes).');
        }

        return $key;
    }
}
