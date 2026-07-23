<?php
/**
 * Pit o Cuixa — Navigation Partial
 *
 * Renders the main site navigation with language toggle.
 * Available variables: $locale (current language code), $pageName
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */

$currentPage = $pageName ?? 'home';
$otherLang   = LANG === 'es' ? 'en' : 'es';

// Build language-switch URL keeping the current path
$langUrl = $_SERVER['REQUEST_URI'];
$langUrl = preg_replace('/[?&]lang=[a-z]{2}/', '', $langUrl);
$separator = str_contains($langUrl, '?') ? '&' : '?';
$langUrl .= $separator . 'lang=' . $otherLang;
?>
<nav class="header__nav container" role="navigation" aria-label="<?= __('nav.home') ?>">
    <a href="/" class="header__logo" aria-label="<?= __('site.name') ?>">
        <?= __('site.name') ?>
    </a>

    <button class="header__menu-toggle" aria-label="<?= __('nav.home') ?>" aria-expanded="false" data-menu-toggle>
        <span class="header__menu-icon"></span>
    </button>

    <ul class="header__menu" data-menu>
        <li>
            <a href="/"
               class="header__link<?= $currentPage === 'home' ? ' header__link--active' : '' ?>">
                <?= __('nav.home') ?>
            </a>
        </li>
        <li>
            <a href="/menu"
               class="header__link<?= $currentPage === 'menu' ? ' header__link--active' : '' ?>">
                <?= __('nav.menu') ?>
            </a>
        </li>
        <li>
            <a href="<?= htmlspecialchars($langUrl, ENT_QUOTES, 'UTF-8') ?>"
               class="header__link header__lang"
               hreflang="<?= $otherLang ?>">
                <?= __('lang.switch') ?>
            </a>
        </li>
    </ul>
</nav>
