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
use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Auth\RateLimiter;

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
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
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

        // Create session
        $token = Auth::createSession($user['id']);

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
