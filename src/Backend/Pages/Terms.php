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

        // URL canónica según el idioma
        $canonicalPath = LANG === 'ca' ? '/terms' : '/' . LANG . '/terms';

        $meta = [
            'title'       => __('terms.title'),
            'description' => __('terms.desc'),
            'canonical'   => $siteUrl . $canonicalPath,
            'og_image'    => '/img/og-image.jpg',
            'langs'       => [
                'ca' => $siteUrl . '/terms',
                'es' => $siteUrl . '/es/terms',
                'en' => $siteUrl . '/en/terms',
            ],
        ];

        $data = [
            'locale' => LANG,
        ];

        \renderPage('terms', $meta, $data);
    }
}