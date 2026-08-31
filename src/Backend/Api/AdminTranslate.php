<?php

/**
 * Pit o Cuixa — Admin Translate API Controller
 *
 * POST /api/pitocuixa/translate — Fills missing CA/EN/UK name/description
 * fields for categories and products via DeepL. Only NULL/empty columns are
 * filled (idempotent), so running it repeatedly is safe.
 *
 * Requires admin session auth (Bearer token).
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Services\MenuTranslator;

class AdminTranslate
{
    /**
     * POST /api/pitocuixa/translate — Translate missing fields via DeepL.
     *
     * On failure the exception message is returned to the client (HTTP 500)
     * so the admin UI can show WHY DeepL failed (missing key, quota, timeout).
     */
    public static function run(): void
    {
        Auth::requireToken();

        try {
            $stats = (new MenuTranslator())->translateMissing();

            Response::json([
                'error'      => false,
                'status'     => 'ok',
                'translated' => $stats,
            ]);
        } catch (\Throwable $e) {
            error_log('[AdminTranslate] ' . $e->getMessage());

            Response::json([
                'error'   => true,
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
