<?php
/**
 * Pit o Cuixa — Home Page Controller
 *
 * SSR: fetches featured products and business info,
 * then renders the home template.
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

use Pit\Cuixa\Backend\Db\Repositories\Product;

class Home
{
    /**
     * Render the home page with hero, featured products, and business info.
     */
    public static function render(): void
    {
        $repo       = new Product();
        $allActive  = $repo->all();

        // Pick up to 6 featured products
        $featured = array_values(
            array_filter($allActive, fn(array $p): bool => $p['is_featured'])
        );

        // Fallback: if fewer than 3 featured, take the first active products
        if (count($featured) < 3) {
            $featured = array_slice($allActive, 0, 6);
        }

        $featured = array_slice($featured, 0, 6);
        $lang     = LANG;

        $meta = [
            'title'       => __('home.title'),
            'description' => __('home.desc'),
            'canonical'   => \Config::siteUrl() . '/',
            'og_image'    => '/img/og-image.jpg',
            'langs'       => [
                'es' => \Config::siteUrl() . '/',
                'en' => \Config::siteUrl() . '/?lang=en',
            ],
        ];

        $data = [
            'featured_products' => $featured,
            'locale'            => $lang,
        ];

        \renderPage('home', $meta, $data);
    }
}
