<?php
/**
 * Pit o Cuixa — Menu Page Controller
 *
 * SSR: fetches all products grouped by category
 * and renders the menu template with filter state data.
 *
 * @package Pit\Cuixa\Backend\Pages
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Pages;

use Pit\Cuixa\Backend\Db\Repositories\Product;
use Pit\Cuixa\Backend\Db\Repositories\Category;

class Menu
{
    /**
     * Render the menu page with grouped products and category filter.
     */
    public static function render(): void
    {
        $catRepo  = new Category();
        $prodRepo = new Product();

        $categories = $catRepo->all();
        $products   = $prodRepo->all();
        $lang       = LANG;

        // Build grouped structure for the template
        $groups = [];
        foreach ($categories as $category) {
            $catProducts = array_values(
                array_filter(
                    $products,
                    fn(array $p): bool => (int) $p['category_id'] === (int) $category['id']
                )
            );

            if ($catProducts === []) {
                continue;
            }

            $groups[] = [
                'category' => $category,
                'products' => $catProducts,
            ];
        }

        // Flat list of categories for the filter bar (localised)
        $filterCategories = array_map(
            function (array $cat) use ($lang): array {
                return [
                    'id'    => (int) $cat['id'],
                    'slug'  => $cat['slug'],
                    'name'  => $cat["name_{$lang}"],
                ];
            },
            $categories
        );

        // ── Build Menu JSON-LD schema ────────────────────────────────
        $siteUrl = \Config::siteUrl();
        $menuItems = [];

        foreach ($groups as $group) {
            $menuSection = [
                '@type' => 'MenuSection',
                'name'  => $group['category']["name_{$lang}"],
                'description' => '',
            ];

            $sectionItems = [];
            foreach ($group['products'] as $product) {
                $sectionItems[] = [
                    '@type' => 'MenuItem',
                    'name'  => $product["name_{$lang}"],
                    'description' => $product["description_{$lang}"],
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => number_format((float) $product['price'], 2, '.', ''),
                        'priceCurrency' => 'EUR',
                    ],
                ];
            }

            $menuSection['hasMenuItem'] = $sectionItems;
            $menuItems[] = $menuSection;
        }

        $menuJsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Menu',
            'name' => __('menu.title'),
            'description' => __('menu.desc'),
            'provider' => [
                '@type' => 'Restaurant',
                '@id' => $siteUrl . '/#business',
                'name' => __('site.name'),
            ],
            'hasMenuSection' => $menuItems,
        ];

        $meta = [
            'title'       => __('menu.title'),
            'description' => __('menu.desc'),
            'canonical'   => $siteUrl . '/menu',
            'og_image'    => '/img/og-image.jpg',
            'langs'       => [
                'es' => $siteUrl . '/menu',
                'en' => $siteUrl . '/menu?lang=en',
            ],
            'jsonld'      => $menuJsonLd,
        ];

        $data = [
            'groups'            => $groups,
            'categories'        => $filterCategories,
            'locale'            => $lang,
        ];

        \renderPage('menu', $meta, $data);
    }
}
