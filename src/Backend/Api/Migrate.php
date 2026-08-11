<?php

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Services\MigrationRunner;

final class Migrate
{
    private ?string $dbPath;
    private ?string $migrationsDir;
    private ?string $lockDir;

    public function __construct(?string $dbPath = null, ?string $migrationsDir = null, ?string $lockDir = null)
    {
        $this->dbPath = $dbPath;
        $this->migrationsDir = $migrationsDir;
        $this->lockDir = $lockDir;
    }

    public function handle(): void
    {
        $authorized = false;

        $configured = \Config::serviceApiToken();
        $bearerToken = Auth::extractBearerToken();

        if (Auth::serviceTokenMatches($configured, $bearerToken)) {
            $authorized = true;
        }

        if (!$authorized) {
            $sessionToken = $bearerToken ?? Auth::extractCookieToken();
            if ($sessionToken !== null) {
                $user = Auth::validateToken($sessionToken);
                if ($user !== null) {
                    $role = $user['role'] ?? 'admin';
                    if ($role === 'admin' || $role === 'superadmin') {
                        $authorized = true;
                    }
                }
            }
        }

        if (!$authorized) {
            Response::json([
                'error'   => true,
                'message' => 'Unauthorized',
                'code'    => 401,
            ], 401);
            return;
        }

        $runner = new MigrationRunner($this->dbPath, $this->migrationsDir, $this->lockDir);

        if ($runner->isLocked()) {
            Response::json([
                'error'   => true,
                'message' => 'Migration already in progress',
                'code'    => 409,
            ], 409);
            return;
        }

        try {
            $result = $runner->run();

            if ($result['locked']) {
                Response::json([
                    'error'   => true,
                    'message' => 'Migration already in progress',
                    'code'    => 409,
                ], 409);
                return;
            }

            $response = [
                'success' => $result['failed'] === 0,
                'applied' => $result['applied'],
                'failed'  => $result['failed'],
            ];

            if ($result['failed'] > 0) {
                $response['errors'] = $result['errors'];
            }

            Response::json($response, 200);
        } catch (\Throwable $e) {
            error_log('Migration failed: ' . $e->getMessage());
            Response::json([
                'error'   => true,
                'message' => 'Migration failed',
                'code'    => 500,
            ], 500);
        }
    }
}
