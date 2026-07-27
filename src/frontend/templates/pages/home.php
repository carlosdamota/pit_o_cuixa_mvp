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

        <!-- ── Call Button ───────────────────────────────────────── -->
        <a class="landing__call" href="tel:<?= str_replace(' ', '', \Config::phone()) ?>"
           aria-label="<?= __('home.info.phone') ?>">
            <svg class="landing__call-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">
                <path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24 11.36 11.36 0 003.58.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11.36 11.36 0 00.57 3.58 1 1 0 01-.25 1.01l-2.2 2.2z"/>
            </svg>
            <span class="landing__call-text"><?= \Config::phone() ?></span>
        </a>

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
