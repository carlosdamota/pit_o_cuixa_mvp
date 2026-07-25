<?php
/**
 * Pit o Cuixa — Home Page Template
 *
 * Fullscreen index landing: company logo + 3 big category buttons
 * linking to /menu with a preselected filter (?cat=).
 * Variables passed via $pageData from renderPage():
 *   - locale: current language code
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages
 */

// Explicit lang suffix so non-default locales survive the navigation
// (CA is the default locale and needs no param).
$langSuffix = LANG === 'ca' ? '' : '&amp;lang=' . LANG;
?>
<!-- ============================================================
     Landing Index (fullscreen)
     ============================================================ -->
<section class="landing">
    <div class="landing__inner">
        <img class="landing__logo"
             src="/img/apple-touch-icon.svg"
             width="180"
             height="180"
             alt="<?= __('site.name') ?>">

        <h1 class="visually-hidden"><?= __('home.landing.title') ?></h1>

        <nav class="landing__nav" aria-label="<?= __('home.landing.aria') ?>">
            <a class="landing__btn" data-animate href="/menu?cat=pollos<?= $langSuffix ?>">
                <?= __('home.landing.pollos') ?>
            </a>
            <a class="landing__btn landing__btn--accent" data-animate href="/menu?cat=menus<?= $langSuffix ?>">
                <?= __('home.landing.combinados') ?>
            </a>
            <a class="landing__btn" data-animate href="/menu?cat=picapica<?= $langSuffix ?>">
                <?= __('home.landing.picapica') ?>
            </a>
        </nav>
    </div>
</section>
