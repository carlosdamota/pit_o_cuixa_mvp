<?php
/**
 * Pit o Cuixa — Image Optimizer Service
 *
 * Resizes and converts uploaded images to WebP format using PHP GD.
 * Includes graceful fallback if GD extension is unavailable.
 *
 * @package Pit\Cuixa\Backend\Services
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Services;

class ImageOptimizer
{
    /**
     * Optimize an image file (resize and convert to WebP).
     *
     * @param string $sourcePath Path to uploaded temporary file.
     * @param string $destinationDir Directory to save optimized image.
     * @param string $baseFilename Base name without extension.
     * @param int $maxWidth Max width in pixels (default: 1200).
     * @param int $maxHeight Max height in pixels (default: 1200).
     * @param int $quality WebP quality 0-100 (default: 82).
     * @return string Relative URL path to saved image (e.g. /img/products/file.webp).
     */
    public static function process(
        string $sourcePath,
        string $destinationDir,
        string $baseFilename,
        int $maxWidth = 1200,
        int $maxHeight = 1200,
        int $quality = 82
    ): string {
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $canUseGd = function_exists('imagecreatefromstring') && function_exists('imagewebp');

        if ($canUseGd) {
            $data = file_get_contents($sourcePath);
            if ($data !== false) {
                $srcImg = @imagecreatefromstring($data);
                if ($srcImg !== false) {
                    imagealphablending($srcImg, true);
                    imagesavealpha($srcImg, true);

                    $width  = imagesx($srcImg);
                    $height = imagesy($srcImg);

                    // Calculate target dimensions maintaining aspect ratio
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1.0);
                    $newWidth  = (int) round($width * $ratio);
                    $newHeight = (int) round($height * $ratio);

                    // Resize if larger than max bounds
                    if ($ratio < 1.0) {
                        $dstImg = imagecreatetruecolor($newWidth, $newHeight);
                        imagealphablending($dstImg, false);
                        imagesavealpha($dstImg, true);
                        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
                        imagefilledrectangle($dstImg, 0, 0, $newWidth, $newHeight, $transparent);

                        imagecopyresampled(
                            $dstImg, $srcImg,
                            0, 0, 0, 0,
                            $newWidth, $newHeight, $width, $height
                        );
                        imagedestroy($srcImg);
                        $srcImg = $dstImg;
                    }

                    $webpFilename = $baseFilename . '.webp';
                    $destPath     = rtrim($destinationDir, '/\\') . '/' . $webpFilename;

                    if (@imagewebp($srcImg, $destPath, $quality)) {
                        imagedestroy($srcImg);
                        return '/img/products/' . $webpFilename;
                    }
                    imagedestroy($srcImg);
                }
            }
        }

        // Fallback: move or copy file as original if GD is not present or failed
        $fallbackFilename = $baseFilename . '.jpg';
        $destPath = rtrim($destinationDir, '/\\') . '/' . $fallbackFilename;
        if (is_uploaded_file($sourcePath)) {
            move_uploaded_file($sourcePath, $destPath);
        } else {
            copy($sourcePath, $destPath);
        }

        return '/img/products/' . $fallbackFilename;
    }
}
