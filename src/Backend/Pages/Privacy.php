<?php
/**
 * Pit o Cuixa — Privacy Page Controller
 *
 * SSR: renders the Privacy Policy page.
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

class Privacy
{
    /**
     * Render the Privacy Policy page.
     */
    public static function render(): void
    {
        $siteUrl = \Config::siteUrl();

        $meta = [
            'title'       => __('privacy.title'),
            'description' => __('privacy.desc'),
            'canonical'   => $siteUrl . '/privacy',
            'og_image'    => '/img/og-image.jpg',
            'langs'       => [
                'es' => $siteUrl . '/privacy?lang=es',
                'en' => $siteUrl . '/privacy?lang=en',
                'uk' => $siteUrl . '/privacy?lang=uk',
            ],
        ];

        $data = [
            'locale' => LANG,
        ];

        \renderPage('privacy', $meta, $data);
    }
}