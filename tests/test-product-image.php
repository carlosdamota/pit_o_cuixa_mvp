<?php
/**
 * Pit o Cuixa — ProductImage Helper Tests
 *
 * Pure unit tests for the image delivery URL helper. No DB, no network.
 *
 * Usage: php tests/test-product-image.php
 * Exit code: 0 when every check passes, 1 otherwise.
 *
 * @package Pit\Cuixa\Tests
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run from CLI.\n";
    exit(1);
}

require_once __DIR__ . '/../src/Backend/Services/ProductImage.php';

use Pit\Cuixa\Backend\Services\ProductImage;

$pass = 0;
$fail = 0;

/**
 * Record one check result.
 */
function record(bool $ok, string $name, string $detail = ''): void
{
    global $pass, $fail;

    if ($ok) {
        $pass++;
        echo "  [PASS] {$name}\n";
        return;
    }

    $fail++;
    echo "  [FAIL] {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

echo "ProductImage::displayUrl()\n";

// ── 1. Bare scraper URL gets the default card chain injected ──────────────
$in  = 'https://res.cloudinary.com/lastpos/image/upload/qw7qvwyyjtfrghuwtcx1';
$exp = 'https://res.cloudinary.com/lastpos/image/upload/f_auto,q_auto,w_600/qw7qvwyyjtfrghuwtcx1';
record(ProductImage::displayUrl($in) === $exp, 'Card width injected into bare scraper URL', 'got ' . ProductImage::displayUrl($in));

// ── 2. Thumbnail width variant ────────────────────────────────────────────
$exp2 = 'https://res.cloudinary.com/lastpos/image/upload/f_auto,q_auto,w_112/qw7qvwyyjtfrghuwtcx1';
record(ProductImage::displayUrl($in, ProductImage::THUMB_WIDTH) === $exp2, 'Thumb width injected');

// ── 3. Already-transformed URL is never re-chained ────────────────────────
$transformed = 'https://res.cloudinary.com/lastpos/image/upload/f_auto,q_auto,w_300/qw7qvwyyjtfrghuwtcx1';
record(ProductImage::displayUrl($transformed) === $transformed, 'Existing transformation chain left untouched');

// ── 4. Versioned URL: transform goes before the version segment ───────────
$versioned = 'https://res.cloudinary.com/lastpos/image/upload/v1717000000/qw7qvwyyjtfrghuwtcx1';
$expV      = 'https://res.cloudinary.com/lastpos/image/upload/f_auto,q_auto,w_600/v1717000000/qw7qvwyyjtfrghuwtcx1';
record(ProductImage::displayUrl($versioned) === $expV, 'Versioned URL keeps version after transforms', 'got ' . ProductImage::displayUrl($versioned));

// ── 5. Versioned URL that already has a chain is untouched ────────────────
$versionedT = 'https://res.cloudinary.com/lastpos/image/upload/v1717000000/f_auto,q_auto,w_300/qw7qvwyyjtfrghuwtcx1';
record(ProductImage::displayUrl($versionedT) === $versionedT, 'Versioned + transformed URL untouched');

// ── 6. Local paths, other hosts, data URIs pass through ───────────────────
record(ProductImage::displayUrl('/img/fallback_img.webp') === '/img/fallback_img.webp', 'Local path untouched');
record(ProductImage::displayUrl('https://example.com/img/foo.jpg') === 'https://example.com/img/foo.jpg', 'Other host untouched');
record(ProductImage::displayUrl('data:image/png;base64,AAAA') === 'data:image/png;base64,AAAA', 'Data URI untouched');

// ── 7. Empty input → null ─────────────────────────────────────────────────
record(ProductImage::displayUrl(null) === null, 'null → null');
record(ProductImage::displayUrl('') === null, 'Empty string → null');
record(ProductImage::displayUrl('   ') === null, 'Whitespace-only → null');

// ── 8. Admin-style URL with subfolder public id ───────────────────────────
$foldered = 'https://res.cloudinary.com/demo/image/upload/products/pollo-asado.jpg';
$expF     = 'https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,w_600/products/pollo-asado.jpg';
record(ProductImage::displayUrl($foldered) === $expF, 'Subfolder public id gets transforms', 'got ' . ProductImage::displayUrl($foldered));

// ── 9. Underscored public id (no comma) is not mistaken for a chain ───────
$underscored = 'https://res.cloudinary.com/lastpos/image/upload/my_image.jpg';
$expU        = 'https://res.cloudinary.com/lastpos/image/upload/f_auto,q_auto,w_600/my_image.jpg';
record(ProductImage::displayUrl($underscored) === $expU, 'Underscored public id still gets transforms', 'got ' . ProductImage::displayUrl($underscored));

// ── 10. Comma segment that is not a valid chain → still inject ────────────
$commaId = 'https://res.cloudinary.com/lastpos/image/upload/foo,bar/baz.jpg';
$expC    = 'https://res.cloudinary.com/lastpos/image/upload/f_auto,q_auto,w_600/foo,bar/baz.jpg';
record(ProductImage::displayUrl($commaId) === $expC, 'Non-transformation comma segment not treated as chain', 'got ' . ProductImage::displayUrl($commaId));

// ── 11. Width clamping ────────────────────────────────────────────────────
$out = ProductImage::displayUrl($in, 0);
record(str_contains((string) $out, 'w_1/'), 'Width clamped to minimum 1', 'got ' . $out);

// ── Summary ───────────────────────────────────────────────────────────────
echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
