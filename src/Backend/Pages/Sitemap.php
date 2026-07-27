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
use Pit\Cuixa\Backend\Db\Repositories\Product;

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
                ['hreflang' => 'x-default', 'href' => $siteUrl . '/menu'],
            ],
        ];

        // ── Active products (if they have individual URLs) ────────────
        try {
            $repo = new Product();
            $products = $repo->all();

            foreach ($products as $product) {
                if (empty($product['slug'])) {
                    continue;
                }

                $caUrl = $siteUrl . '/producte/' . $product['slug'];
                $esUrl = $siteUrl . '/producto/' . $product['slug'];
                $enUrl = $siteUrl . '/product/' . $product['slug'];

                $pages[] = [
                    'loc' => $caUrl,
                    'alternates' => [
                        ['hreflang' => 'ca', 'href' => $caUrl],
                        ['hreflang' => 'es', 'href' => $esUrl],
                        ['hreflang' => 'en', 'href' => $enUrl],
                        ['hreflang' => 'x-default', 'href' => $caUrl],
                    ],
                ];
            }
        } catch (\Throwable $e) {
            // DB not available yet — omit product URLs gracefully
        }

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
