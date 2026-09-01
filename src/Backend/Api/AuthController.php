<?php
/**
 * Pit o Cuixa — Auth API Controller
 *
 * POST /api/auth/login  — Verify password_hash, create session, return token
 * POST /api/auth/logout — Delete session (token from header or cookie)
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Db\Repositories\User as UserRepo;
use Pit\Cuixa\Backend\Db\Repositories\TwoFactorChallenge;
use Pit\Cuixa\Backend\Db\Repositories\BackupCode;
use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Auth\RateLimiter;
use Pit\Cuixa\Backend\Auth\Crypto;
use Pit\Cuixa\Backend\Auth\Totp;

class AuthController
{
    /**
     * POST /api/auth/login
     *
     * Expects JSON body: { "username": "...", "password": "..." }
     * Returns: { "token": "...", "user": { "id", "username", "display_name" } }
     */
    public static function login(): void
    {
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput ?: '', true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        if (!is_array($input) || empty($input)) {
            Response::error('Invalid JSON body', 400);
            return;
        }

        $username = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($username === '' || $password === '') {
            Response::error('Username and password are required', 400);
            return;
        }

        // ── Rate limiting (brute-force protection) ──────────────
        $limiter = new RateLimiter();
        $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Check IP rate limit: 10 attempts per minute
        $ipCheck = $limiter->check("login:ip:{$ip}", 10, 60);
        if (!$ipCheck['allowed']) {
            Response::json([
                'error'   => true,
                'message' => 'Too many login attempts. Try again in ' . $ipCheck['retryAfter'] . ' seconds.',
                'code'    => 429,
            ], 429);
            return;
        }

        // Check username rate limit: 5 attempts per 5 minutes
        $userCheck = $limiter->check("login:user:{$username}", 5, 300);
        if (!$userCheck['allowed']) {
            Response::json([
                'error'   => true,
                'message' => 'Account temporarily locked. Try again in ' . $userCheck['retryAfter'] . ' seconds.',
                'code'    => 429,
            ], 429);
            return;
        }

        $userRepo = new UserRepo();
        $user     = $userRepo->byUsername($username);

        if ($user === null) {
            $limiter->recordFailure("login:ip:{$ip}");
            $limiter->recordFailure("login:user:{$username}");
            Response::error('Invalid credentials', 401);
            return;
        }

        // Re-fetch with password hash (byUsername strips it via serialize)
        $pdo  = \Pit\Cuixa\Backend\Db\Connection::get();
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute([':id' => $user['id']]);
        $passwordRow = $stmt->fetch();

        if ($passwordRow === false || !Auth::verifyPassword($password, $passwordRow['password'])) {
            $limiter->recordFailure("login:ip:{$ip}");
            $limiter->recordFailure("login:user:{$username}");
            Response::error('Invalid credentials', 401);
            return;
        }

        // Success: reset rate limit counters
        $limiter->reset("login:ip:{$ip}");
        $limiter->reset("login:user:{$username}");

        // ── 2FA enrollment / verification branch ──────────────────────
        // If the admin already has 2FA enabled, do NOT create a session yet:
        // issue a short-lived challenge and let the client complete /api/auth/2fa-verify.
        if (!empty($user['totp_enabled'])) {
            $challengeRepo   = new TwoFactorChallenge();
            $challengeToken   = $challengeRepo->create($user['id']);

            Response::json([
                'error'              => false,
                'two_factor_required' => true,
                'challenge_token'    => $challengeToken,
            ]);
            return;
        }

        // Not yet enrolled (totp_enabled = 0): offer enrollment at the login
        // screen. Issue a short-lived enrollment challenge, return it, and let
        // the client drive /api/auth/2fa-enroll-start → /api/auth/2fa-enroll-confirm.
        // No session is created until enrollment is confirmed.
        $enrollRepo  = new TwoFactorChallenge();
        $enrollToken = $enrollRepo->create($user['id']);

        Response::json([
            'error'                     => false,
            'two_factor_enroll_required' => true,
            'enroll_token'              => $enrollToken,
            'user'                      => [
                'id'           => $user['id'],
                'username'     => $user['username'],
                'display_name' => $user['display_name'],
            ],
        ]);
        return;
    }

    /**
     * POST /api/auth/2fa-verify
     *
     * Completes the TOTP second factor. Expects JSON:
     *   { "challenge_token": "...", "code": "123456" | "<backup-code>" }
     *
     * The code is verified against the TOTP secret (decrypted) OR as a single-use
     * backup code. On success a session is created (same as normal login).
     */
    public static function twoFactorVerify(): void
    {
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput ?: '', true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        if (!is_array($input) || empty($input)) {
            Response::error('Invalid JSON body', 400);
            return;
        }

        $challengeToken = trim((string) ($input['challenge_token'] ?? ''));
        $code           = trim((string) ($input['code'] ?? ''));

        if ($challengeToken === '' || $code === '') {
            Response::error('Challenge token and code are required', 400);
            return;
        }

        $limiter = new RateLimiter();

        // Reuse RateLimiter for brute-force protection on the challenge itself.
        $limitCheck = $limiter->check("2fa:challenge:{$challengeToken}", 5, 300);
        if (!$limitCheck['allowed']) {
            Response::json([
                'error'   => true,
                'message' => 'Too many attempts. Try again in ' . $limitCheck['retryAfter'] . ' seconds.',
                'code'    => 429,
            ], 429);
            return;
        }

        $challengeRepo = new TwoFactorChallenge();
        $challenge     = $challengeRepo->find($challengeToken);

        if ($challenge === null) {
            Response::error('Invalid or expired challenge', 401);
            return;
        }

        // Increment persisted attempt counter; hard lockout after 5 attempts.
        $attempts = $challengeRepo->incrementAttempts($challengeToken);
        if ($attempts > 5) {
            $challengeRepo->delete($challengeToken);
            $limiter->reset("2fa:challenge:{$challengeToken}");
            Response::json([
                'error'   => true,
                'message' => 'Too many invalid codes. Challenge locked — please log in again.',
                'code'    => 429,
            ], 429);
            return;
        }

        $userId    = (int) $challenge['user_id'];
        $userRepo  = new UserRepo();
        $encrypted = $userRepo->getEncryptedTotpSecret($userId);

        $success = false;

        // 1) TOTP code (decrypt secret first)
        if ($encrypted !== null) {
            try {
                $secret = Crypto::decrypt($encrypted);
                if (Totp::verify($secret, $code)) {
                    $success = true;
                }
            } catch (\RuntimeException $e) {
                // Decryption failure — treat as invalid, fall through to backup code check
                error_log('2FA: TOTP secret decrypt failed for user ' . $userId . ' — ' . $e->getMessage());
                $success = false;
            }
        }

        // 2) Backup code (single-use)
        if (!$success) {
            $backupRepo = new BackupCode();
            $matchedId  = $backupRepo->verify($code, $userId);
            if ($matchedId !== null) {
                $backupRepo->markUsed($matchedId);
                $success = true;
            }
        }

        // 3) Mail code (single-use, 5-minute window)
        if (!$success) {
            $mailCode = $challengeRepo->getMailCode($challengeToken);
            if ($mailCode !== null && password_verify($code, $mailCode['hash'])) {
                $challengeRepo->clearMailCode($challengeToken);
                $success = true;
            }
        }

        if (!$success) {
            $limiter->recordFailure("2fa:challenge:{$challengeToken}");
            Response::error('Invalid code', 401);
            return;
        }

        // Success — consume the challenge and start a real session.
        $challengeRepo->delete($challengeToken);
        $limiter->reset("2fa:challenge:{$challengeToken}");

        $token = Auth::createSession($userId);

        $user = $userRepo->byId($userId);

        Response::json([
            'error' => false,
            'data'  => [
                'token' => $token,
                'user'  => [
                    'id'           => $user['id'],
                    'username'     => $user['username'],
                    'display_name' => $user['display_name'],
                ],
            ],
        ]);
    }

    /**
     * POST /api/auth/2fa-mail-code
     *
     * Request a 6-digit code sent to the admin's recovery email.
     * Body: { "challenge_token": "..." }
     */
    public static function twoFactorMailCode(): void
    {
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput ?: '', true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        $challengeToken = trim((string) ($input['challenge_token'] ?? ''));

        if ($challengeToken === '') {
            Response::error('Challenge token is required', 400);
            return;
        }

        $challengeRepo = new TwoFactorChallenge();
        $challenge     = $challengeRepo->find($challengeToken);

        if ($challenge === null) {
            Response::error('Invalid or expired challenge', 401);
            return;
        }

        $userId   = (int) $challenge['user_id'];
        $userRepo = new UserRepo();
        $user     = $userRepo->byId($userId);

        if ($user === null || empty($user['mail'])) {
            Response::error('No hay email de recuperación configurado', 400);
            return;
        }

        // Generate 6-digit code
        $code       = (string) random_int(100000, 999999);
        $hash       = password_hash($code, PASSWORD_DEFAULT);
        $expiresAt  = time() + 300; // 5 minutes

        $challengeRepo->storeMailCode($challengeToken, $hash, $expiresAt);

        // Send via native mail()
        $to      = $user['mail'];
        $subject = 'Pit o Cuixa — Tu código de acceso';
        $body    = "Tu código de verificación es: {$code}\n\nEste código caduca en 5 minutos.\nSi no solicitaste este código, ignora este mensaje.";
        $headers = "From: Pit o Cuixa <info@pitocuixa.es>\r\nContent-Type: text/plain; charset=UTF-8";

        $sent = mail($to, $subject, $body, $headers);

        if (!$sent) {
            $phpError = error_get_last();
            $detail   = $phpError['message'] ?? 'unknown';
            error_log("2FA mail-code: failed to send to {$to} — {$detail}");
            Response::json([
                'error'   => true,
                'message' => 'Error al enviar el email',
                'debug'   => $detail,
                'code'    => 500,
            ], 500);
            return;
        }

        Response::json([
            'error'   => false,
            'message' => 'Código enviado a ' . $to,
        ]);
    }

    /**
     * POST /api/auth/2fa-enroll-start
     *
     * Step 1 of enrollment-at-login. Body: { "enroll_token": "..." }
     * The enroll_token is the short-lived challenge issued by login() for an
     * admin with totp_enabled = 0 (no authenticated session yet).
     *
     * Looks up the challenge, generates a TOTP secret + backup codes, and
     * persists them in a PENDING state (totp_enabled stays 0 until confirm).
     * Returns ONLY the provisioning URI (client renders the QR to scan).
     * The base32 secret is persisted encrypted for the confirm step and the
     * backup codes are stored server-side (hashed) for recovery — but neither
     * is returned to the client, so nothing sensitive is shown on screen.
     */
    public static function twoFactorEnrollStart(): void
    {
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput ?: '', true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        if (!is_array($input) || empty($input)) {
            Response::error('Invalid JSON body', 400);
            return;
        }

        $enrollToken = trim((string) ($input['enroll_token'] ?? ''));

        if ($enrollToken === '') {
            Response::error('Enrollment token is required', 400);
            return;
        }

        $challengeRepo = new TwoFactorChallenge();
        $challenge     = $challengeRepo->find($enrollToken);

        if ($challenge === null) {
            Response::error('Invalid or expired enrollment', 401);
            return;
        }

        $userId   = (int) $challenge['user_id'];
        $userRepo = new UserRepo();
        $user     = $userRepo->byId($userId);

        if ($user === null) {
            Response::error('Invalid or expired enrollment', 401);
            return;
        }

        $secret = Totp::generateSecret();                       // base32 secret
        $email  = (string) $user['username'];                   // username IS the email
        $uri    = Totp::getProvisioningUri($secret, $email);

        // Generate ~10 backup codes (plaintext returned to admin, hash stored)
        $backupCodes  = self::generateBackupCodes(10);
        $backupHashes = array_map(
            static fn(string $c): string => password_hash($c, PASSWORD_BCRYPT),
            $backupCodes
        );

        // Persist the NEW secret in a PENDING (staged) state on the challenge
        // row — NOT on users.totp_secret. The active secret stays valid until
        // the new one is CONFIRMED, so an admin who aborts re-enrollment mid-flow
        // is never locked out. Backup codes are regenerated server-side below.
        $backupRepo = new BackupCode();
        $backupRepo->clearForUser($userId);
        $challengeRepo->storePendingSecret($enrollToken, Crypto::encrypt($secret));
        $backupRepo->storeMany($userId, $backupHashes);

        // NOTE: secret_base32 and backup_codes are intentionally NOT returned
        // to the client. The pending secret is staged encrypted on the challenge
        // row (above) for the confirm step, and backup codes are stored
        // server-side (hashed) so recovery keeps working — but neither is
        // displayed in the UI.
        Response::json([
            'error' => false,
            'data'  => [
                'provisioning_uri' => $uri,
            ],
        ]);
    }

    /**
     * POST /api/auth/2fa-enroll-confirm
     *
     * Step 2 of enrollment-at-login. Body: { "enroll_token": "...", "code": "123456" }
     * Verifies the TOTP code against the PENDING secret using the same rate
     * limiting as twoFactorVerify. On success enables 2FA, consumes the
     * challenge, and starts a real session (same success shape as twoFactorVerify).
     */
    public static function twoFactorEnrollConfirm(): void
    {
        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput ?: '', true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        if (!is_array($input) || empty($input)) {
            Response::error('Invalid JSON body', 400);
            return;
        }

        $enrollToken = trim((string) ($input['enroll_token'] ?? ''));
        $code        = trim((string) ($input['code'] ?? ''));

        if ($enrollToken === '' || $code === '') {
            Response::error('Enrollment token and code are required', 400);
            return;
        }

        $limiter = new RateLimiter();

        // Same brute-force protection as twoFactorVerify, keyed on the token.
        $limitCheck = $limiter->check("2fa:challenge:{$enrollToken}", 5, 300);
        if (!$limitCheck['allowed']) {
            Response::json([
                'error'   => true,
                'message' => 'Too many attempts. Try again in ' . $limitCheck['retryAfter'] . ' seconds.',
                'code'    => 429,
            ], 429);
            return;
        }

        $challengeRepo = new TwoFactorChallenge();
        $challenge     = $challengeRepo->find($enrollToken);

        if ($challenge === null) {
            Response::error('Invalid or expired enrollment', 401);
            return;
        }

        // Increment persisted attempt counter; hard lockout after 5 attempts.
        $attempts = $challengeRepo->incrementAttempts($enrollToken);
        if ($attempts > 5) {
            $challengeRepo->delete($enrollToken);
            $limiter->reset("2fa:challenge:{$enrollToken}");
            Response::json([
                'error'   => true,
                'message' => 'Too many invalid codes. Enrollment locked — please log in again.',
                'code'    => 429,
            ], 429);
            return;
        }

        $userId   = (int) $challenge['user_id'];
        $userRepo = new UserRepo();

        // Read the STAGED secret from the challenge row (not the active
        // users.totp_secret). If the admin aborted a previous re-enrollment the
        // staged value is NULL and we must not proceed.
        $encrypted = $challengeRepo->getPendingSecret($enrollToken);

        if ($encrypted === null) {
            $limiter->recordFailure("2fa:challenge:{$enrollToken}");
            Response::error('No pending enrollment found', 400);
            return;
        }

        $success = false;

        try {
            $secret = Crypto::decrypt($encrypted);
            if (Totp::verify($secret, $code)) {
                $success = true;
            }
        } catch (\RuntimeException $e) {
            // Decryption failure — treat as invalid.
            error_log('2FA: pending secret decrypt failed for user ' . $userId . ' — ' . $e->getMessage());
            $success = false;
        }

        if (!$success) {
            $limiter->recordFailure("2fa:challenge:{$enrollToken}");
            Response::error('Invalid code', 401);
            return;
        }

        // Success — swap the staged secret into the active slot, clear the
        // staging column, then enable 2FA and start a real session.
        // enableTotp is idempotent: it keeps totp_enabled = 1 for re-enroll and
        // sets it for first-time enrollment (and stamps totp_verified_at).
        $userRepo->saveTotpSecret($userId, $encrypted);
        $challengeRepo->clearPendingSecret($enrollToken);
        $userRepo->enableTotp($userId);
        $challengeRepo->delete($enrollToken);
        $limiter->reset("2fa:challenge:{$enrollToken}");

        $token = Auth::createSession($userId);
        $user  = $userRepo->byId($userId);

        Response::json([
            'error' => false,
            'data'  => [
                'token' => $token,
                'user'  => [
                    'id'           => $user['id'],
                    'username'     => $user['username'],
                    'display_name' => $user['display_name'],
                ],
            ],
        ]);
    }

    /**
     * Generate a batch of human-friendly backup codes.
     * Uses the unambiguous base32 alphabet (no 0/O/1/I/L) to avoid misreads.
     *
     * @return string[]
     */
    private static function generateBackupCodes(int $count): array
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $max      = strlen($alphabet) - 1;
        $codes    = [];

        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 10; $j++) {
                $code .= $alphabet[random_int(0, $max)];
            }
            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * POST /api/auth/logout
     *
     * Accepts token via Bearer header or session cookie.
     */
    public static function logout(): void
    {
        $token = Auth::extractBearerToken() ?? Auth::extractCookieToken();

        if ($token !== null) {
            Auth::destroySession($token);
        }

        Response::json([
            'error' => false,
            'data'  => ['message' => __('nav.logout')],
        ]);
    }
}
