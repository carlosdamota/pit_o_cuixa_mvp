<?php
/**
 * Pit o Cuixa — Home Page Template
 *
 * Hero section, featured products grid, and business info.
 * Variables passed via $pageData from renderPage():
 *   - featured_products: array of raw product rows
 *   - locale: current language code
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages
 */

$featured = $pageData['featured_products'] ?? [];
?>
<!-- ============================================================
     Hero Section
     ============================================================ -->
<section class="hero">
    <div class="hero__bg"></div>
    <div class="hero__content container">
        <h1 class="hero__title"><?= __('home.hero.title') ?></h1>
        <p class="hero__subtitle"><?= __('home.hero.subtitle') ?></p>
        <a href="/menu" class="hero__cta"><?= __('home.hero.cta') ?></a>
    </div>
</section>

<!-- ============================================================
     Featured Products
     ============================================================ -->
<section class="featured section">
    <div class="container">
        <h2 class="section__title"><?= __('home.featured') ?></h2>
        <p class="section__subtitle"><?= __('home.featured.subtitle') ?></p>

        <?php if ($featured !== []): ?>
            <div class="featured__grid">
                <?php foreach ($featured as $product): ?>
                    <?php require __DIR__ . '/../partials/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="featured__empty"><?= __('menu.no_products') ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================
     Business Info
     ============================================================ -->
<section class="info section">
    <div class="container">
        <h2 class="section__title"><?= __('home.info.title') ?></h2>

        <div class="info__grid">
            <div class="info__card">
                <div class="info__icon" aria-hidden="true">📍</div>
                <h3 class="info__label"><?= __('home.info.address') ?></h3>
                <p class="info__text">Carrer Major, 25<br>43800 Torredembarra<br>Tarragona</p>
            </div>

            <div class="info__card">
                <div class="info__icon" aria-hidden="true">📞</div>
                <h3 class="info__label"><?= __('home.info.phone') ?></h3>
                <p class="info__text">
                    <a href="tel:<?= str_replace(' ', '', \Config::phone()) ?>"><?= __('home.info.phone') ?></a>
                </p>
            </div>

            <div class="info__card">
                <div class="info__icon" aria-hidden="true">🕐</div>
                <h3 class="info__label"><?= __('home.info.hours') ?></h3>
                <p class="info__text"><?= __('home.info.hours') ?></p>
            </div>
        </div>
    </div>
</section>
