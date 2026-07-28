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

$groups      = $pageData['groups']      ?? [];
$catList     = $pageData['categories']  ?? [];
$locale      = $pageData['locale']      ?? LANG;
$showSlider  = $pageData['show_slider']  ?? false;
$sliderImages = $pageData['slider_images'] ?? [];
?>
<!-- ============================================================
     Page Header — Slider or Fallback Hero
     ============================================================ -->
<?php if ($showSlider): ?>
<section class="menu-slider section" data-menu-slider
    role="region" aria-roledescription="carousel"
    aria-label="<?= __('menu.slider.aria') ?>">
    <div class="menu-slider__viewport" tabindex="0">
        <div class="menu-slider__track">
            <?php foreach ($sliderImages as $i => $img): ?>
                <figure class="menu-slider__slide" role="group"
                        aria-roledescription="slide"
                        aria-label="<?= __('menu.slider.slide_n', [$i + 1, count($sliderImages)]) ?>">
                    <img src="<?= htmlspecialchars($img, ENT_QUOTES) ?>"
                         alt="<?= __('menu.slider.image_alt', [$i + 1]) ?>"
                         loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                         decoding="async" width="1200" height="675">
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="menu-slider__controls">
        <button class="menu-slider__btn menu-slider__btn--prev"
                data-slider-prev
                type="button"
                aria-label="<?= __('menu.slider.prev') ?>">
            <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
        </button>
        <button class="menu-slider__btn menu-slider__btn--next"
                data-slider-next
                type="button"
                aria-label="<?= __('menu.slider.next') ?>">
            <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M8.59 16.59L10 18l6-6-6-6-1.41 1.41L13.17 12z"/></svg>
        </button>
    </div>
    <ol class="menu-slider__dots" aria-label="<?= __('menu.slider.dots') ?>">
        <?php foreach ($sliderImages as $i => $_): ?>
            <li class="menu-slider__dot<?= $i === 0 ? ' menu-slider__dot--active' : '' ?>">
                <button type="button"
                        data-slider-dot="<?= $i ?>"
                        aria-label="<?= __('menu.slider.slide_n', [$i + 1, count($sliderImages)]) ?>"
                        <?= $i === 0 ? 'aria-current="true"' : '' ?>></button>
            </li>
        <?php endforeach; ?>
    </ol>
</section>
<?php else: ?>
<section class="menu-hero menu-hero--fallback section">
    <div class="container">
        <h1 class="section__title"><?= __('menu.heading') ?></h1>
        <p class="section__subtitle"><?= __('menu.subtitle') ?></p>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     Filter Bar (sticky category tabs)
     ============================================================ -->
<nav class="filter-bar" data-filter-bar aria-label="<?= __('menu.heading') ?>">
    <div class="filter-bar__inner container">
        <div class="filter-bar__search">
            <label for="menu-search" class="visually-hidden"><?= __('menu.search.label') ?></label>
            <input type="search"
                   id="menu-search"
                   class="filter-bar__search-input"
                   data-menu-search
                   placeholder="<?= __('menu.search.placeholder') ?>">
        </div>

        <div class="filter-bar__tabs">
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
    </div>
</nav>

<!-- ============================================================
     Product Groups
     ============================================================ -->
<div class="menu-products section" data-menu-products>
    <?php foreach ($groups as $group):
        $category = $group['category'];
        $lang     = $locale;
        $catName  = !empty($category["name_{$lang}"])
            ? $category["name_{$lang}"]
            : (!empty($category['name_ca'])
                ? $category['name_ca']
                : (!empty($category['name_es'])
                    ? $category['name_es']
                    : ($category['name_en'] ?? '')));
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

    <p id="search-no-results"
       class="visually-hidden"
       aria-live="polite"
       role="status"><?= __('menu.search.no_results') ?></p>

    <?php if ($groups === []): ?>
        <div class="container">
            <p class="menu-products__empty"><?= __('menu.no_products') ?></p>
        </div>
    <?php endif; ?>
</div>
