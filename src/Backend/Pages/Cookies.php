<?php
/**
 * Pit o Cuixa — Cookies Page Controller
 *
 * SSR: renders the Cookies Policy page.
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

class Cookies
{
    /**
     * Render the Cookies Policy page.
     */
    public static function render(): void
    {
        $siteUrl = \Config::siteUrl();

        $meta = [
            'title'       => __('cookies.title'),
            'description' => __('cookies.desc'),
            'canonical'   => $siteUrl . '/cookies',
            'og_image'    => '/img/og-image.jpg',
            'langs'       => [
                'ca' => $siteUrl . '/cookies',
                'es' => $siteUrl . '/cookies?lang=es',
                'en' => $siteUrl . '/cookies?lang=en',
                'uk' => $siteUrl . '/cookies?lang=uk',
            ],
        ];

        $data = [
            'locale' => LANG,
        ];

        \renderPage('cookies', $meta, $data);
    }
}