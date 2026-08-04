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
use Pit\Cuixa\Backend\Db\Repositories\Settings;

class Menu
{
    /**
     * Render the menu page with grouped products and category filter.
     */
    public static function render(): void
    {
        $catRepo  = new Category();
        $prodRepo = new Product();

        $categories       = $catRepo->all();
        $dineInProducts   = $prodRepo->all(null, 200, 0, 'dine_in');
        $deliveryProducts = $prodRepo->all(null, 200, 0, 'delivery');
        $lang             = LANG;

        // ── Slider logic ─────────────────────────────────────────────
        Settings::ensureSchema();
        $sliderEnabled = Settings::get('menu_slider_enabled', '0');
        $sliderImages  = [];

        if ($sliderEnabled === '1') {
            $sliderImages = self::discoverSliderImages();
        }

        $showSlider = ($sliderEnabled === '1') && count($sliderImages) > 0;

        // Extract Dine-In Menus & Cartas (type = 'menu' || type = 'carta')
        $dineInMenus = array_values(
            array_filter(
                $dineInProducts,
                fn(array $p): bool => in_array($p['type'] ?? 'simple', ['menu', 'carta'], true)
            )
        );

        // Build Delivery grouped structure for template
        $groups = [];
        foreach ($categories as $category) {
            $catProducts = array_values(
                array_filter(
                    $deliveryProducts,
                    fn(array $p): bool => (int) $p['category_id'] === (int) $category['id'] && !in_array($p['type'] ?? 'simple', ['menu', 'carta'], true)
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

        // Build Dine-In grouped structure for template (simple items)
        $dineInGroups = [];
        foreach ($categories as $category) {
            $catProducts = array_values(
                array_filter(
                    $dineInProducts,
                    fn(array $p): bool => (int) $p['category_id'] === (int) $category['id'] && !in_array($p['type'] ?? 'simple', ['menu', 'carta'], true)
                )
            );

            if ($catProducts === []) {
                continue;
            }

            $dineInGroups[] = [
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
                    'name'  => self::translateField($cat, 'name', $lang),
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
                'name'  => self::translateField($group['category'], 'name', $lang),
                'description' => '',
            ];

            $sectionItems = [];
            foreach ($group['products'] as $product) {
                $sectionItems[] = [
                    '@type' => 'MenuItem',
                    'name'  => self::translateField($product, 'name', $lang),
                    'description' => self::translateField($product, 'description', $lang),
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
                'ca' => $siteUrl . '/menu',
                'es' => $siteUrl . '/menu?lang=es',
                'en' => $siteUrl . '/menu?lang=en',
            ],
            'jsonld'      => $menuJsonLd,
        ];

        $data = [
            'groups'            => $groups,
            'dine_in_groups'    => $dineInGroups,
            'dine_in_menus'     => $dineInMenus,
            'categories'        => $filterCategories,
            'locale'            => $lang,
            'show_slider'       => $showSlider,
            'slider_images'     => $sliderImages,
        ];

        \renderPage('menu', $meta, $data);
    }

    /**
     * Discover and sort images in /img/menu-slider/.
     * Only includes .jpg, .jpeg, .png, .webp files.
     *
     * @return array<int, string> Sorted list of public URL paths
     */
    private static function discoverSliderImages(): array
    {
        $pattern = __DIR__ . '/../../../public/img/menu-slider/*.{jpg,jpeg,png,webp}';
        $files   = glob($pattern, GLOB_BRACE);

        if (!is_array($files) || $files === []) {
            return [];
        }

        sort($files, SORT_STRING);

        return array_map(
            static fn(string $path): string => '/img/menu-slider/' . rawurlencode(basename($path)),
            $files
        );
    }

    private static function translateField(array $row, string $field, string $lang): string
    {
        $keys = [
            "{$field}_{$lang}",
            "{$field}_es",
            "{$field}_en",
            "{$field}_ca",
        ];

        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return (string) $row[$key];
            }
        }

        return '';
    }
}
