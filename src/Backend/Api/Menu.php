<?php
/**
 * Pit o Cuixa — Menu API Controller
 *
 * GET /api/menu — returns all products grouped by category.
 * Frontend JS consumes this for dynamic filtering.
 *
 * @package Pit\Cuixa\Backend\Api
 */

declare(strict_types=1);

namespace Pit\Cuixa\Backend\Api;

use Pit\Cuixa\Backend\Http\Response;
use Pit\Cuixa\Backend\Db\Repositories\Product as ProductRepo;
use Pit\Cuixa\Backend\Db\Repositories\Category as CategoryRepo;

class Menu
{
    /**
     * GET /api/menu — grouped by category, localised.
     *
     * Delegates the grouping to groups() (the single source of truth) so the
     * same localized section data powers both this endpoint and the chatbot's
     * section-aware replies.
     */
    public static function grouped(): void
    {
        Response::json([
            'data'  => self::groups(LANG),
            'error' => false,
        ]);
    }

    /**
     * Build the localized section groups consumed by both the /api/menu
     * endpoint and the chatbot's section-aware menu replies.
     *
     * Each group: { slug, name, items: [{ name, price (float), description }] }.
     * Empty categories are skipped.
     *
     * @return array<int, array{slug:string, name:string, items:array<int, array{name:string, price:float, description:string}>}>
     */
    public static function groups(string $lang): array
    {
        $catRepo  = new CategoryRepo();
        $prodRepo = new ProductRepo();

        $categories = $catRepo->all();
        $products   = $prodRepo->all();

        $groups = [];

        foreach ($categories as $category) {
            // Filter products belonging to this category
            $catProducts = array_values(
                array_filter(
                    $products,
                    fn(array $p): bool => (int) $p['category_id'] === (int) $category['id']
                )
            );

            // Skip empty categories
            if ($catProducts === []) {
                continue;
            }

            $groups[] = [
                'slug'  => (string) $category['slug'],
                'name'  => self::localized($category, 'name', $lang),
                'items' => array_map(
                    function (array $p) use ($lang): array {
                        return [
                            'name'        => self::localized($p, 'name', $lang),
                            'price'       => (float) $p['price'],
                            'description' => self::localized($p, 'description', $lang),
                        ];
                    },
                    $catProducts
                ),
            ];
        }

        return $groups;
    }

    /**
     * Localized field lookup with empty-string fallback, mirroring
     * Pit\Cuixa\Backend\Pages\Menu::translateField(). Falls back through the
     * requested locale → es → en so a missing/empty translation never
     * yields a blank name.
     *
     * @param  array<string, mixed> $row
     */
    private static function localized(array $row, string $field, string $lang): string
    {
        $keys = [
            "{$field}_{$lang}",
            "{$field}_es",
            "{$field}_en",
        ];

        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return (string) $row[$key];
            }
        }

        return '';
    }
}
