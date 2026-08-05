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

$groups       = $pageData['groups']       ?? [];
$catList      = $pageData['categories']   ?? [];
$locale       = $pageData['locale']       ?? LANG;
$showSlider   = $pageData['show_slider']   ?? false;
$sliderImages = $pageData['slider_images'] ?? [];

// Mode requested from onboarding drag & drop: 'local' (dine_in) vs 'delivery'
$requestedMode  = $_GET['mode'] ?? '';
$isDeliveryMode = ($requestedMode === 'delivery' || $requestedMode === 'domicilio');
?>
<!-- ============================================================
     Page Header — Presentation Hero Banner
     ============================================================ -->
<section class="menu-hero menu-hero--presentation section">
    <div class="menu-hero__image-wrapper">
        <img src="/img/menu-slider/presentatione.webp"
             alt="<?= __('site.name') ?> — Presentation"
             class="menu-hero__img"
             loading="eager"
             decoding="async"
             width="1200"
             height="675">
    </div>
</section>


<?php
$dineInGroups = $pageData['dine_in_groups'] ?? [];
$dineInMenus  = $pageData['dine_in_menus']  ?? [];
?>

<!-- ============================================================
     Filter Bar (sticky channel switcher, search & category tabs)
     ============================================================ -->
<nav class="filter-bar" data-filter-bar aria-label="<?= __('menu.heading') ?>">
    <div class="filter-bar__inner container">
        <div class="filter-bar__top">
            <div class="filter-bar__search">
                <input type="search"
                       id="menu-search"
                       class="filter-bar__search-input"
                       data-menu-search
                       placeholder="<?= __('menu.search.placeholder') ?>">
            </div>

            <div class="channel-switcher" role="tablist" aria-label="<?= __('menu.channel.aria') ?>">
                <button class="channel-switcher__btn<?= !$isDeliveryMode ? ' channel-switcher__btn--active' : '' ?>"
                        data-channel-target="dine_in"
                        type="button"
                        role="tab"
                        aria-selected="<?= !$isDeliveryMode ? 'true' : 'false' ?>"
                        aria-pressed="<?= !$isDeliveryMode ? 'true' : 'false' ?>">
                    <span class="channel-switcher__icon">🍽️</span>
                    <span class="channel-switcher__label"><?= __('menu.channel.dine_in') ?></span>
                </button>
                <button class="channel-switcher__btn<?= $isDeliveryMode ? ' channel-switcher__btn--active' : '' ?>"
                        data-channel-target="delivery"
                        type="button"
                        role="tab"
                        aria-selected="<?= $isDeliveryMode ? 'true' : 'false' ?>"
                        aria-pressed="<?= $isDeliveryMode ? 'true' : 'false' ?>">
                    <span class="channel-switcher__icon">🛵</span>
                    <span class="channel-switcher__label"><?= __('menu.channel.takeaway') ?></span>
                </button>
            </div>
        </div>

        <div class="filter-bar__tabs" data-filter-tabs<?= $isDeliveryMode ? '' : ' hidden' ?>>
            <button class="filter-bar__tab filter-bar__tab--active"
                    data-filter="all"
                    type="button"
                    aria-pressed="true">
                <?= __('menu.filter.all') ?>
            </button>

            <button class="filter-bar__tab"
                    data-filter="popular"
                    type="button"
                    aria-pressed="false">
                <?= __('menu.filter.popular') ?>
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
     CHANNEL 1: Carta en Local (Restaurante) — Accordions
     ============================================================ -->
<div class="container section" data-channel-view="dine_in"<?= $isDeliveryMode ? ' hidden' : '' ?>>
    <?php if ($dineInMenus !== []): ?>
        <section style="margin-bottom:var(--space-xl, 32px);" data-category="all">
            <h2 class="section__title">
                Menús del Día y Promociones
            </h2>
            <div class="accordion-list">
                <?php foreach ($dineInMenus as $index => $m):
                    $mName = $m["name_{$locale}"] ?? $m['name_es'];
                    $mDesc = $m["description_{$locale}"] ?? $m['description_es'];
                    $mData = $m['menu_data'] ?? [];
                    $badge = $mData['badge'] ?? '';
                    $includes = $mData['includes'] ?? '';
                    $sections = $mData['sections'] ?? [];
                    $isOpen = ($index === 0);
                    $mSearchText = mb_strtolower($mName . ' ' . $mDesc . ' ' . $badge . ' ' . $includes);
                ?>
                    <article class="accordion-item accordion-item--featured <?= $isOpen ? 'accordion-item--open' : '' ?>"
                             data-search-text="<?= htmlspecialchars($mSearchText, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="accordion-header" data-accordion-toggle aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                            <div class="accordion-header__title-wrap">
                                <span class="accordion-header__title"><?= htmlspecialchars($mName, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($badge !== ''): ?>
                                    <span class="accordion-header__subtitle"><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php elseif ($mDesc !== ''): ?>
                                    <span class="accordion-header__subtitle"><?= htmlspecialchars($mDesc, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="accordion-header__meta">
                                <?php if ((float) $m['price'] > 0): ?>
                                    <span class="accordion-header__price"><?= number_format((float) $m['price'], 2) ?> €</span>
                                <?php endif; ?>
                                <svg class="accordion-header__icon" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z"/></svg>
                            </div>
                        </button>

                        <div class="accordion-content" <?= $isOpen ? '' : 'hidden' ?>>
                            <?php if (is_array($sections) && $sections !== []): ?>
                                <div class="menu-sections">
                                    <?php foreach ($sections as $sec):
                                        $secTitle = $sec["title_{$locale}"] ?? $sec['title_es'] ?? '';
                                        $secItems = $sec["items_{$locale}"] ?? $sec['items_es'] ?? [];
                                    ?>
                                        <div class="menu-section-block">
                                            <h4 class="menu-section-block__title"><?= htmlspecialchars($secTitle, ENT_QUOTES, 'UTF-8') ?></h4>
                                            <ul class="menu-section-block__list">
                                                <?php foreach ($secItems as $item): ?>
                                                    <?php if (is_array($item)):
                                                        $itemName = $item["name_{$locale}"] ?? $item['name_es'] ?? $item['name'] ?? '';
                                                        $itemPrice = (float) ($item['price'] ?? 0);
                                                    ?>
                                                        <li class="menu-section-block__item" style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;">
                                                            <span>• <?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') ?></span>
                                                            <?php if ($itemPrice > 0): ?>
                                                                <strong style="margin-left:12px;white-space:nowrap;color:var(--color-primary, #d97706);"><?= number_format($itemPrice, 2) ?> €</strong>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php else: ?>
                                                        <li class="menu-section-block__item">• <?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($includes !== ''): ?>
                                <div class="menu-includes-note">
                                    💡 <?= htmlspecialchars($includes, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Carta en local por Secciones / Categorías (ListView colapsable) -->
    <section>
        <h2 class="section__title">
            A la Carta en Restaurante
        </h2>
        <div class="accordion-list">
            <?php foreach ($dineInGroups as $catIndex => $group):
                $category = $group['category'];
                $catName  = $category["name_{$locale}"] ?? $category['name_es'];
                $catSlug  = $category['slug'] ?? '';
                $catProducts = $group['products'];
                $isOpenCat = ($catIndex === 0 && $dineInMenus === []);
            ?>
                <article class="accordion-item <?= $isOpenCat ? 'accordion-item--open' : '' ?>"
                         data-category="<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="accordion-header" data-accordion-toggle aria-expanded="<?= $isOpenCat ? 'true' : 'false' ?>">
                        <div class="accordion-header__title-wrap">
                            <span class="accordion-header__title"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="accordion-header__subtitle"><?= count($catProducts) ?> opciones</span>
                        </div>
                        <svg class="accordion-header__icon" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z"/></svg>
                    </button>

                    <div class="accordion-content" <?= $isOpenCat ? '' : 'hidden' ?>>
                        <div class="listview">
                            <?php foreach ($catProducts as $p):
                                $pName = $p["name_{$locale}"] ?? $p['name_es'];
                                $pDesc = $p["description_{$locale}"] ?? $p['description_es'];
                                $pSearchText = mb_strtolower($pName . ' ' . $pDesc);
                                // Same image fallback chain as product-card.php:
                                // scraped/Cloudinary URL → /img/fallback_img.webp.
                                // data-image-slug keeps the client-side chain in
                                // main.js working.
                                $imgUrl = !empty($p['image_url'])
                                    ? $p['image_url']
                                    : '/img/fallback_img.webp';
                            ?>
                                <div class="listview-item" data-search-text="<?= htmlspecialchars($pSearchText, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="listview-item__left">
                                        <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                             alt="<?= htmlspecialchars($pName, ENT_QUOTES, 'UTF-8') ?>"
                                             class="listview-item__img"
                                             <?php if (!empty($p['slug'])): ?>data-image-slug="<?= htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                             loading="lazy" width="52" height="52">
                                        <div class="listview-item__info">
                                            <span class="listview-item__name"><?= htmlspecialchars($pName, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if ($pDesc !== ''): ?>
                                                <span class="listview-item__desc"><?= htmlspecialchars($pDesc, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="listview-item__price"><?= number_format((float)$p['price'], 2) ?> €</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<!-- ============================================================
     CHANNEL 2: Para Llevar / Domicilio (Grid por Categorías)
     ============================================================ -->
<div data-channel-view="delivery"<?= $isDeliveryMode ? '' : ' hidden' ?>>
    <!-- Product Groups -->
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
</div>

<!-- ============================================================
     Delivery Area Map Section (Above Footer) & Local SEO JSON-LD
     ============================================================ -->
<section class="delivery-map-section" aria-labelledby="delivery-map-heading">
    <div class="container">
        <header class="delivery-map-section__header">
            <h2 id="delivery-map-heading" class="delivery-map-section__title">
                <?= __('menu.map.title') ?>
            </h2>
            <p class="delivery-map-section__subtitle">
                <?= __('menu.map.subtitle') ?>
            </p>
        </header>

        <div class="delivery-map-card">
            <!-- Leaflet Interactive Canvas Container -->
            <div id="delivery-map" class="delivery-map-container" role="region" aria-label="<?= __('menu.map.title') ?>"></div>

            <!-- Town Badges Bar -->
            <div class="delivery-map-towns">
                <p class="delivery-map-towns__label"><?= __('menu.map.towns_label') ?></p>
                <ul class="delivery-map-towns__list">
                    <li class="delivery-map-towns__tag delivery-map-towns__tag--hub">📍 Torredembarra</li>
                    <li class="delivery-map-towns__tag">🛵 Altafulla</li>
                    <li class="delivery-map-towns__tag">🛵 Creixell</li>
                    <li class="delivery-map-towns__tag">🛵 La Móra</li>
                    <li class="delivery-map-towns__tag">🛵 Pobla de Montornès</li>
                    <li class="delivery-map-towns__tag">🛵 La Riera de Gaià</li>
                </ul>
            </div>
        </div>

        <div class="delivery-map-note">
            <?= __('menu.map.delivery_note') ?>
        </div>
    </div>
</section>

<!-- ── Local SEO JSON-LD: FoodEstablishment Delivery Coverage ────────── -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FoodEstablishment",
  "name": "Pit o Cuixa",
  "telephone": "+34977642010",
  "areaServed": [
    {
      "@type": "AdministrativeArea",
      "name": "Torredembarra"
    },
    {
      "@type": "AdministrativeArea",
      "name": "Altafulla"
    },
    {
      "@type": "AdministrativeArea",
      "name": "Creixell"
    },
    {
      "@type": "AdministrativeArea",
      "name": "La Móra"
    },
    {
      "@type": "AdministrativeArea",
      "name": "Pobla de Montornès"
    },
    {
      "@type": "AdministrativeArea",
      "name": "La Riera de Gaià"
    }
  ]
}
</script>
