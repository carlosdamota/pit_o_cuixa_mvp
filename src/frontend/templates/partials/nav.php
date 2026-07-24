<?php
/**
 * Pit o Cuixa — Navigation Partial
 *
 * Renders the main site navigation with locale dropdown.
 * Available variables: $locale (current language code), $pageName
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */

$currentPage = $pageName ?? 'home';

// Build current URI without lang param for the form action
$baseUri = $_SERVER['REQUEST_URI'];
$baseUri = preg_replace('/[?&]lang=[a-z]{2}/', '', $baseUri);
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
        <li class="header__lang-item">
            <form action="<?= htmlspecialchars($baseUri, ENT_QUOTES, 'UTF-8') ?>" method="get" class="header__lang-form">
                <select name="lang" class="header__lang-select" aria-label="<?= __('lang.switch') ?>" onchange="this.form.submit()">
                    <option value="ca"<?= LANG === 'ca' ? ' selected' : '' ?>>Català</option>
                    <option value="es"<?= LANG === 'es' ? ' selected' : '' ?>>Castellano</option>
                    <option value="en"<?= LANG === 'en' ? ' selected' : '' ?>>English</option>
                </select>
                <noscript><button type="submit" class="header__lang-btn"><?= __('lang.switch') ?></button></noscript>
            </form>
        </li>
    </ul>
</nav>
