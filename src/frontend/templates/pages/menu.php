<?php
/**
 * Pit o Cuixa — Menu Page Template
 *
 * Filter bar, product groups with category headings.
 * Variables passed via $pageData from renderPage():
 *   - groups: array of [category, products] pairs (raw bilingual rows)
 *   - categories: array of [id, slug, name] — localised for filter bar
 *   - locale: current language code
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages
 */

$groups     = $pageData['groups']     ?? [];
$catList    = $pageData['categories'] ?? [];
$locale     = $pageData['locale']     ?? LANG;
?>
<!-- ============================================================
     Page Header
     ============================================================ -->
<section class="menu-hero section">
    <div class="container">
        <h1 class="section__title"><?= __('menu.heading') ?></h1>
        <p class="section__subtitle"><?= __('menu.subtitle') ?></p>
    </div>
</section>

<!-- ============================================================
     Filter Bar (sticky category tabs)
     ============================================================ -->
<nav class="filter-bar" data-filter-bar aria-label="<?= __('menu.heading') ?>">
    <div class="filter-bar__inner container">
        <button class="filter-bar__tab filter-bar__tab--active"
                data-filter="all"
                type="button"
                aria-pressed="true">
            <?= __('menu.filter.all') ?>
        </button>

        <?php foreach ($catList as $cat): ?>
            <button class="filter-bar__tab"
                    data-filter="<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?>"
                    type="button"
                    aria-pressed="false">
                <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
            </button>
        <?php endforeach; ?>
    </div>
</nav>

<!-- ============================================================
     Product Groups
     ============================================================ -->
<div class="menu-products section" data-menu-products>
    <?php foreach ($groups as $group):
        $category = $group['category'];
        $lang     = $locale;
        $catName  = $category["name_{$lang}"] ?? '';
        $catSlug  = $category['slug'] ?? '';
    ?>
        <section class="product-group" data-category="<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>">
            <div class="container">
                <h2 class="product-group__title"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></h2>

                <div class="product-group__grid">
                    <?php foreach ($group['products'] as $product): ?>
                        <?php require __DIR__ . '/../partials/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if ($groups === []): ?>
        <div class="container">
            <p class="menu-products__empty"><?= __('menu.no_products') ?></p>
        </div>
    <?php endif; ?>
</div>
