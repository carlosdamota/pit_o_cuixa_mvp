<?php
/**
 * Pit o Cuixa — Authentication System
 *
 * Token-based auth with bcrypt password verification.
 * - Session tokens stored in `sessions` table, expire after 8 hours.
 * - API auth via `Authorization: Bearer <token>` header.
 * - HTML admin auth via session cookie.
 * - CSRF token generation and validation.
 *
 * @package Pit\Cuixa\Backend\Auth
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Auth;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Db\Repositories\Session as SessionRepo;
use Pit\Cuixa\Backend\Db\Repositories\User as UserRepo;

class Auth
{
    private const TOKEN_BYTES = 32;        // random_bytes(32) → 64 hex chars
    private const COOKIE_NAME = 'pit_session';
    private const CSRF_KEY   = 'pit_csrf_token';

    /**
     * Generate a secure random token.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_BYTES));
    }

    /**
     * Verify plaintext password against bcrypt hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Create a session for a user: generate token, store in DB, set cookie.
     *
     * @param  int    $userId
     * @return string The session token
     */
    public static function createSession(int $userId): string
    {
        // Invalidate all previous sessions for this user
        $sessionRepo = new SessionRepo();
        $sessionRepo->deleteByUser($userId);

        $token    = self::generateToken();
        $lifetime = \Config::sessionLifetime(); // default 28800 (8h)
        $expires  = date('Y-m-d H:i:s', time() + $lifetime);

        $sessionRepo->create($userId, $token, $expires);

        // Set httpOnly, SameSite=Lax cookie
        setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires'  => time() + $lifetime,
                'path'     => '/',
                'secure'   => !\Config::isDev(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        return $token;
    }

    /**
     * Destroy a session: delete from DB, clear cookie.
     */
    public static function destroySession(string $token): void
    {
        $sessionRepo = new SessionRepo();
        $sessionRepo->deleteByToken($token);

        setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires'  => 1,
                'path'     => '/',
                'secure'   => !\Config::isDev(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Extract Bearer token from the Authorization header.
     *
     * @return string|null Token string or null if missing
     */
    public static function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract session token from cookie.
     */
    public static function extractCookieToken(): ?string
    {
        return $_COOKIE[self::COOKIE_NAME] ?? null;
    }

    /**
     * Validate a token: lookup in sessions table, check expiry.
     *
     * @param  string $token
     * @return array|null User row if valid, null if invalid/expired
     */
    public static function validateToken(string $token): ?array
    {
        $sessionRepo = new SessionRepo();
        $session     = $sessionRepo->findByToken($token);

        if ($session === null) {
            return null;
        }

        // Check expiration
        if (strtotime($session['expires_at']) < time()) {
            $sessionRepo->deleteByToken($token);
            return null;
        }

        // Touch the session: extend expiry on use
        $lifetime = \Config::sessionLifetime();
        $newExpires = date('Y-m-d H:i:s', time() + $lifetime);
        $sessionRepo->extendExpiry($token, $newExpires);

        // Load user data
        $userRepo = new UserRepo();
        return $userRepo->byId((int) $session['user_id']);
    }

    /**
     * Require a valid Bearer token for API access.
     * Sends 401 JSON and exits on failure.
     *
     * @return array Authenticated user row
     */
    public static function requireToken(): array
    {
        $token = self::extractBearerToken() ?? self::extractCookieToken();

        if ($token === null) {
            Response::json([
                'error'   => true,
                'message' => __('error.401'),
                'code'    => 401,
            ], 401);
            exit;
        }

        $user = self::validateToken($token);

        if ($user === null) {
            Response::json([
                'error'   => true,
                'message' => __('error.401'),
                'code'    => 401,
            ], 401);
            exit;
        }

        // Role check: only admin/superadmin can access API endpoints
        $role = $user['role'] ?? 'admin';
        if ($role !== 'admin' && $role !== 'superadmin') {
            Response::json([
                'error'   => true,
                'message' => 'Insufficient privileges',
                'code'    => 403,
            ], 403);
            exit;
        }

        return $user;
    }

    /**
     * Require a valid session cookie for admin HTML pages.
     * Redirects to /admin/login on failure.
     *
     * @return array Authenticated user row
     */
    public static function requireSession(): array
    {
        $token = self::extractCookieToken();

        if ($token === null) {
            Response::redirect('/admin/login');
            exit;
        }

        $user = self::validateToken($token);

        if ($user === null) {
            Response::redirect('/admin/login');
            exit;
        }

        // Role check: only admin/superadmin can access admin pages
        $role = $user['role'] ?? 'admin';
        if ($role !== 'admin' && $role !== 'superadmin') {
            Response::redirect('/admin/login');
            exit;
        }

        return $user;
    }

    /**
     * Get or generate a CSRF token for the current session.
     * Stored in a session cookie (not PHP sessions — we use token-based).
     *
     * @return string
     */
    public static function getCsrfToken(): string
    {
        if (!isset($_COOKIE[self::CSRF_KEY])) {
            $token = self::generateToken();
            setcookie(
                self::CSRF_KEY,
                $token,
                [
                    'expires'  => 0, // Session cookie
                    'path'     => '/',
                    'secure'   => !\Config::isDev(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
            $_COOKIE[self::CSRF_KEY] = $token;
        }

        return $_COOKIE[self::CSRF_KEY];
    }

    /**
     * Validate a submitted CSRF token against the cookie.
     * Reads from X-CSRF-Token header (API) or POST body (forms).
     * Sends 403 JSON and exits on failure — safe to call as API guard.
     */
    public static function validateCsrfToken(): void
    {
        $token       = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        $cookieToken = $_COOKIE[self::CSRF_KEY] ?? null;

        if (!$token || !$cookieToken || !hash_equals($cookieToken, $token)) {
            Response::json([
                'error'   => true,
                'message' => 'Invalid CSRF token',
                'code'    => 403,
            ], 403);
            exit;
        }
    }
}
