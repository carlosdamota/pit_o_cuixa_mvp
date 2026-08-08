<?php
/**
 * Pit o Cuixa — Admin Image Upload API Controller
 *
 * POST /api/admin/upload — Upload product image file, optimize & convert to WebP.
 * Requires admin token authentication and CSRF token.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Auth\Auth;
use Pit\Cuixa\Backend\Services\ImageOptimizer;

class AdminUpload
{
    /**
     * Handle POST /api/admin/upload
     */
    public static function uploadImage(): void
    {
        Auth::requireToken();
        Auth::validateCsrfToken();

        $file = $_FILES['image'] ?? $_FILES['file'] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::error('No se ha recibido ninguna imagen válida', 400);
            return;
        }

        // Validate file size (max 5 MB)
        $maxBytes = 5 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            Response::error('El archivo excede el tamaño máximo permitido (5 MB)', 422);
            return;
        }

        // Validate MIME type
        $tmpPath  = $file['tmp_name'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $tmpPath) : ($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/avif',
        ];

        if (!in_array(strtolower((string)$mimeType), $allowedMimeTypes, true)) {
            Response::error('Formato de imagen no permitido (solo JPG, PNG, WEBP, GIF, AVIF)', 422);
            return;
        }

        $publicDir = is_dir(__DIR__ . '/../../../public_html')
            ? __DIR__ . '/../../../public_html'
            : __DIR__ . '/../../../public';

        $destDir  = $publicDir . '/img/products';
        $baseName = 'prod_' . time() . '_' . bin2hex(random_bytes(4));

        try {
            $url = ImageOptimizer::process($tmpPath, $destDir, $baseName, 1200, 1200, 82);

            Response::json([
                'error' => false,
                'url'   => $url,
            ], 201);
        } catch (\Throwable $e) {
            error_log('[AdminUpload] Error optimizing image: ' . $e->getMessage());
            Response::error('Error al procesar la imagen', 500);
        }
    }
}
