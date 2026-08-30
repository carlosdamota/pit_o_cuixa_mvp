<?php
/**
 * Pit o Cuixa — Product Image Delivery Helper
 *
 * Stored `products.image_url` values are canonical Cloudinary upload URLs
 * without transformations (as produced by the scraper), so browsers used to
 * download full-size originals (~1.8 MB PNGs per product). This helper
 * builds the display URL by injecting delivery transformations
 * (f_auto,q_auto,w_N) so clients get lightweight, auto-formatted derivatives
 * served from Cloudinary's CDN with long-lived browser caching.
 *
 * The database is never rewritten: the transformation is display-only and
 * can evolve without migrations.
 *
 * @package Pit\Cuixa\Backend\Services
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Services;

final class ProductImage
{
    /**
     * Width for card/hero images (2x the 300px product-card slot).
     */
    public const CARD_WIDTH = 600;

    /**
     * Width for small thumbnails (2x the 52-56px menu slots). One width for
     * every small slot keeps browser cache hits high on the menu page.
     */
    public const THUMB_WIDTH = 112;

    /**
     * Build the display URL for a stored image reference.
     *
     * - Cloudinary "upload" delivery URLs get auto-format / auto-quality /
     *   width transformations injected (skipped when a transformation chain
     *   already exists, so chains are never stacked).
     * - Local paths (/img/...), other hosts, data: URIs, and empty/null
     *   values pass through unchanged (null for empty input).
     *
     * @param  mixed $url    Stored image reference (canonical URL or null)
     * @param  int   $width  Delivery width in pixels
     * @return string|null Display URL, or null when there is no image
     */
    public static function displayUrl(mixed $url, int $width = self::CARD_WIDTH): ?string
    {
        $url = trim((string) ($url ?? ''));

        if ($url === '') {
            return null;
        }

        // Only rewrite Cloudinary "upload" delivery URLs. Anything else is
        // returned untouched so admin-entered/local URLs keep working.
        if (preg_match('#^(https?://res\.cloudinary\.com/[^/]+/image/upload/)(.+)$#', $url, $m) !== 1) {
            return $url;
        }

        $rest = $m[2];

        // An existing transformation chain means the URL is already
        // optimized — never stack a second chain.
        if (self::hasTransformationChain($rest)) {
            return $url;
        }

        $transform = sprintf('f_auto,q_auto,w_%d', max(1, $width));

        // Transformations precede the optional version segment:
        // upload/<transforms>/v<version>/<public_id>
        return $m[1] . $transform . '/' . $rest;
    }

    /**
     * Detect whether the segment after upload/ (or after the version) starts
     * with a transformation chain, e.g. "f_auto,q_auto,w_600/<public_id>".
     *
     * A chain is a comma-separated list where every token matches a
     * "key_value" transformation (f_auto, q_auto, w_600, c_fill, ...). Bare
     * public ids produced by the scraper are single hashes without commas,
     * so they never match and always get transformations injected.
     */
    private static function hasTransformationChain(string $rest): bool
    {
        // Skip an optional version segment: v1234567890/<tail>
        if (preg_match('#^v\d+/(.+)$#', $rest, $v) === 1) {
            $rest = $v[1];
        }

        $first = (string) strtok($rest, '/');

        if (!str_contains($first, ',')) {
            return false;
        }

        foreach (explode(',', $first) as $token) {
            if (preg_match('#^[a-z]{1,3}_[^,/]+$#', $token) !== 1) {
                return false;
            }
        }

        return true;
    }
}
