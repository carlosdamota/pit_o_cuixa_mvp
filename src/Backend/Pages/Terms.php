<?php
/**
 * Pit o Cuixa — Terms and Conditions Page Controller
 *
 * SSR: renders the Terms and Conditions page.
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

class Terms
{
    /**
     * Render the Terms and Conditions page.
     */
    public static function render(): void
    {
        $siteUrl = \Config::siteUrl();

        $meta = [
            'title'       => __('terms.title'),
            'description' => __('terms.desc'),
            'canonical'   => $siteUrl . '/terms',
            'og_image'    => '/img/og-image.jpg',
            'langs'       => [
                'ca' => $siteUrl . '/terms',
                'es' => $siteUrl . '/terms?lang=es',
                'en' => $siteUrl . '/terms?lang=en',
                'uk' => $siteUrl . '/terms?lang=uk',
            ],
        ];

        $data = [
            'locale' => LANG,
        ];

        \renderPage('terms', $meta, $data);
    }
}