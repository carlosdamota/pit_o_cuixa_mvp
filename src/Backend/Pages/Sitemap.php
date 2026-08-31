<?php
/**
 * Pit o Cuixa — Dynamic XML Sitemap
 *
 * Generates a valid XML sitemap listing all public pages with
 * hreflang alternate annotations for bilingual content.
 *
 * Route: GET /sitemap.xml
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

use Pit\Cuixa\Backend\Http\Response;

class Sitemap
{
    /**
     * Generate and output the XML sitemap.
     */
    public static function render(): void
    {
        $siteUrl = \Config::siteUrl();

        // Collect all URLs with their hreflang variants
        $pages = self::getPages($siteUrl);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($pages as $page) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . self::escapeXml($page['loc']) . "</loc>\n";

            foreach ($page['alternates'] as $alt) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . self::escapeXml($alt['hreflang']) . '" href="' . self::escapeXml($alt['href']) . '" />' . "\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>' . "\n";

        Response::xml($xml);
    }

    /**
     * Collect all public URLs with their alternate language variants.
     *
     * @param  string $siteUrl
     * @return array<int, array{loc: string, alternates: array<int, array{hreflang: string, href: string}>}>
     */
    private static function getPages(string $siteUrl): array
    {
        $pages = [];

        // ── Home page ────────────────────────────────────────────────
        $pages[] = [
            'loc' => $siteUrl . '/',
            'alternates' => [
                ['hreflang' => 'ca', 'href' => $siteUrl . '/'],
                ['hreflang' => 'es', 'href' => $siteUrl . '/?lang=es'],
                ['hreflang' => 'en', 'href' => $siteUrl . '/?lang=en'],
                ['hreflang' => 'uk', 'href' => $siteUrl . '/?lang=uk'],
                ['hreflang' => 'x-default', 'href' => $siteUrl . '/'],
            ],
        ];

        // ── Menu page ────────────────────────────────────────────────
        $pages[] = [
            'loc' => $siteUrl . '/menu',
            'alternates' => [
                ['hreflang' => 'ca', 'href' => $siteUrl . '/menu'],
                ['hreflang' => 'es', 'href' => $siteUrl . '/menu?lang=es'],
                ['hreflang' => 'en', 'href' => $siteUrl . '/menu?lang=en'],
                ['hreflang' => 'uk', 'href' => $siteUrl . '/menu?lang=uk'],
                ['hreflang' => 'x-default', 'href' => $siteUrl . '/menu'],
            ],
        ];

        // ── FAQ page ────────────────────────────────────────────────
        $pages[] = [
            'loc' => $siteUrl . '/faq',
            'alternates' => [
                ['hreflang' => 'ca', 'href' => $siteUrl . '/faq'],
                ['hreflang' => 'es', 'href' => $siteUrl . '/faq?lang=es'],
                ['hreflang' => 'en', 'href' => $siteUrl . '/faq?lang=en'],
                ['hreflang' => 'uk', 'href' => $siteUrl . '/faq?lang=uk'],
                ['hreflang' => 'x-default', 'href' => $siteUrl . '/faq'],
            ],
        ];

        // ── Privacy page ─────────────────────────────────────────────
        $pages[] = [
            'loc' => $siteUrl . '/privacy',
            'alternates' => [
                ['hreflang' => 'ca', 'href' => $siteUrl . '/privacy'],
                ['hreflang' => 'es', 'href' => $siteUrl . '/privacy?lang=es'],
                ['hreflang' => 'en', 'href' => $siteUrl . '/privacy?lang=en'],
                ['hreflang' => 'uk', 'href' => $siteUrl . '/privacy?lang=uk'],
                ['hreflang' => 'x-default', 'href' => $siteUrl . '/privacy'],
            ],
        ];

        // ── Cookies page ─────────────────────────────────────────────
        $pages[] = [
            'loc' => $siteUrl . '/cookies',
            'alternates' => [
                ['hreflang' => 'ca', 'href' => $siteUrl . '/cookies'],
                ['hreflang' => 'es', 'href' => $siteUrl . '/cookies?lang=es'],
                ['hreflang' => 'en', 'href' => $siteUrl . '/cookies?lang=en'],
                ['hreflang' => 'uk', 'href' => $siteUrl . '/cookies?lang=uk'],
                ['hreflang' => 'x-default', 'href' => $siteUrl . '/cookies'],
            ],
        ];

        return $pages;
    }

    /**
     * Escape a string for safe XML output.
     *
     * @param  string $value
     * @return string
     */
    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
