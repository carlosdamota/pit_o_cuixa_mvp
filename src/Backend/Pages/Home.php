<?php
/**
 * Pit o Cuixa — Home Page Controller
 *
 * SSR: renders the fullscreen index landing
 * (logo + 3 category buttons linking to /menu?cat=).
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

class Home
{
    /**
     * Render the home page landing.
     */
    public static function render(): void
    {
        $meta = [
            'title'       => __('home.title'),
            'description' => __('home.desc'),
            'canonical'   => \Config::siteUrl() . '/',
            'og_image'    => '/img/og-image.jpg',
            'langs'       => [
                'ca' => \Config::siteUrl() . '/',
                'es' => \Config::siteUrl() . '/?lang=es',
                'en' => \Config::siteUrl() . '/?lang=en',
            ],
        ];

        $data = [
            'locale' => LANG,
        ];

        \renderPage('home', $meta, $data);
    }
}
