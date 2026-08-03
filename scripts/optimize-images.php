<?php
/**
 * Pit o Cuixa — Batch Image Optimizer & WebP Converter Script
 *
 * Scans public/img/products for non-WebP or oversized images,
 * resizes them to max 1200px, converts them to WebP format with 82% quality,
 * and updates database product image URLs if matching.
 *
 * Usage: php scripts/optimize-images.php
 *
 * @package Pit\Cuixa\Scripts
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/shared/bootstrap.php';

use Pit\Cuixa\Backend\Services\ImageOptimizer;
use Pit\Cuixa\Backend\Db\Connection;

echo "=== Pit o Cuixa — Optimización de Imágenes y Conversión WebP ===\n\n";

$productsDir = __DIR__ . '/../public/img/products';

if (!is_dir($productsDir)) {
    mkdir($productsDir, 0755, true);
    echo "Directorio creado: {$productsDir}\n";
}

$files = scandir($productsDir) ?: [];
$processed = 0;
$skipped   = 0;
$bytesSaved = 0;

$pdo = Connection::get();

foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }

    $filePath = $productsDir . '/' . $file;
    if (!is_file($filePath)) {
        continue;
    }

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // If it's already a webp, check if it needs resizing
    if ($ext === 'webp') {
        $skipped++;
        continue;
    }

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'avif'], true)) {
        continue;
    }

    $origSize = filesize($filePath) ?: 0;
    $baseName = pathinfo($file, PATHINFO_FILENAME);

    echo "Procesando: {$file} ({$origSize} bytes)... ";

    $newUrl = ImageOptimizer::process(
        $filePath,
        $productsDir,
        $baseName,
        1200,
        1200,
        82
    );

    $newFilename = basename($newUrl);
    $newFilePath = $productsDir . '/' . $newFilename;

    if (is_file($newFilePath)) {
        $newSize = filesize($newFilePath) ?: 0;
        $diff = $origSize - $newSize;
        if ($diff > 0) {
            $bytesSaved += $diff;
        }

        echo "Convertido -> {$newFilename} ({$newSize} bytes)\n";
        $processed++;

        // Update DB image_url references if pointing to old file
        $oldUrl = '/img/products/' . $file;
        $stmt = $pdo->prepare('UPDATE products SET image_url = :newUrl WHERE image_url = :oldUrl');
        $stmt->execute([':newUrl' => $newUrl, ':oldUrl' => $oldUrl]);

        // Remove old non-webp file if new webp was created
        if ($newFilename !== $file && is_file($filePath)) {
            @unlink($filePath);
        }
    } else {
        echo "Omitido / Error\n";
    }
}

echo "\n=======================================================\n";
echo "Proceso finalizado.\n";
echo "Imágenes optimizadas: {$processed}\n";
echo "Imágenes omitidas: {$skipped}\n";
if ($bytesSaved > 0) {
    $kb = round($bytesSaved / 1024, 2);
    echo "Espacio ahorrado: {$kb} KB\n";
}
echo "=======================================================\n";
