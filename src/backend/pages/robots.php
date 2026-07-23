<?php
/**
 * Pit o Cuixa — Dynamic Robots.txt
 *
 * Generates robots.txt allowing all crawlers access to public pages
 * while disallowing admin area. References the XML sitemap.
 *
 * Route: GET /robots.txt
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

use Pit\Cuixa\Backend\Http\Response;

class Robots
{
    /**
     * Generate and output the robots.txt content.
     */
    public static function render(): void
    {
        $siteUrl = \Config::siteUrl();

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /api/admin/',
            '',
            'Sitemap: ' . $siteUrl . '/sitemap.xml',
            '',
        ];

        Response::text(implode("\n", $lines));
    }
}
