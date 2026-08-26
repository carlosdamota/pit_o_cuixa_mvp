<?php
    declare(strict_types=1);

    namespace Pit\Cuixa\Backend\Auth;

    /**
     * TOTP (RFC 6238) — compatible con Google Authenticator / Authy / 1Password.
     *
     * ESQUELETO: los cuerpos de los métodos están comentados paso a paso para que
     * VOS implementes el algoritmo a mano (me dijiste que tenías ganas). Mientras
     * no lo hagas, el 2FA no funcionará: los métodos lanzan LogicException.
     *
     * Requisitos en el host: extensión `openssl` (para hash_hmac) y capacidad de
     * generar base32 (random_bytes viene con PHP).
     *
     * REFERENCIA: RFC 6238 (TOTP) y RFC 4226 (HOTP, base del paso).
     */

    final class Totp
    {
        public const DIGITS = 6;
        public const PERIOD = 30;          // segundos por código
        public const SECRET_BYTES = 20;     // 160 bits recomendados por el RFC

        /**
         * Genera un secreto aleatorio y lo devuelve en BASE32 (lo que espera la app del Authenticator).
         *
         * PASOS:
         *   1. $raw = random_bytes(self::SECRET_BYTES);  // binario crudo de 20 bytes
         *   2. Codificar $raw a BASE32 (RFC 4648, alfabeto A-Z2-7, sin padding o con '=').
         *      Podés implementar base32_encode($raw) vos, o usar base64 con un mapa propio.
         *   3. Devolver el string base32, p.ej. "JBSWY3DPEHPK3PXP".
         */

        public static function generateSecret(): string
        {
            // TODO(usuario): implementar los 3 pasos de arriba.
            throw new \LogicException('Totp::generateSecret() not implemented yet — implementá el algoritmo.');
        }

        /**
         * Construye el URI otpauth:// que se mete en el QR.
         *
         * Formato:
         *   otpauth://totp/{issuer}:{account}?secret={BASE32}&issuer={issuer}&period=30&digits=6
         *
         * PASOS:
         *   1. $account  = urlencode($accountName);   // p.ej. el email del admin
         *   2. $issuer   = urlencode($issuer);
         *   3. Devolver "otpauth://totp/{$issuer}:{$account}?secret=" . $secret
         *      . "&issuer={$issuer}&period=" . self::PERIOD . "&digits=" . self::DIGITS;
         */
        public static function getProvisioningUri(string $secret, string $accountName, string $issuer = 'PitOCuixa'): string
        {
            // TODO(usuario): armar el URI con urlencode como arriba.
            throw new \LogicException('Totp::getProvisioningUri() not implemented yet — implementá el algoritmo.');
        }

        /**
         * Verifica un código de self::DIGITS dígitos contra el secreto, con ventana de ±$window pasos.
         *
         * PASOS (RFC 6238):
         *   $counter = intdiv(time(), self::PERIOD);
         *   for ($i = -$window; $i <= $window; $i++) {
         *       $expected = self::hotp($secret, $counter + $i);
         *       if (hash_equals($expected, $code)) return true;   // hash_equals = comparación constante
         *   }
         *   return false;
         *
         * hotp(secret, counter):   ← RFC 4226
         *   a. $key = base32_decode($secret);                 // binario
         *   b. $msg = pack('J', $counter);                    // 8 bytes big-endian
         *   c. $hmac = hash_hmac('sha1', $msg, $key, true);   // 20 bytes
         *   d. $offset = ord($hmac[19]) & 0x0F;
         *   e. $bin = unpack('N', substr($hmac, $offset, 4))[1] & 0x7FFFFFFF;
         *   f. return str_pad((string) ($bin % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
         *
         * base32_decode: inverso del encode de generateSecret (mapeo A-Z2-7 → 5 bits).
         */
        public static function verify(string $secret, string $code, int $window = 1): bool
        {
            // TODO(usuario): implementar hotp() + el bucle de ventana de arriba.
            throw new \LogicException('Totp::verify() not implemented yet — implementá el algoritmo.');
        }
    }
