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
        <!-- ── Desktop nav items ─────────────────────────────────── -->
        <li class="header__desktop-item">
            <a href="/"
               class="header__link<?= $currentPage === 'home' ? ' header__link--active' : '' ?>">
                <?= __('nav.home') ?>
            </a>
        </li>
        <li class="header__desktop-item">
            <a href="/menu"
               class="header__link<?= $currentPage === 'menu' ? ' header__link--active' : '' ?>">
                <?= __('nav.menu') ?>
            </a>
        </li>
        <li class="header__lang-item header__desktop-item">
            <form action="<?= htmlspecialchars($baseUri, ENT_QUOTES, 'UTF-8') ?>" method="get" class="header__lang-form">
                <select name="lang" class="header__lang-select" aria-label="<?= __('lang.switch') ?>" onchange="this.form.submit()">
                    <option value="ca"<?= LANG === 'ca' ? ' selected' : '' ?>>Català</option>
                    <option value="es"<?= LANG === 'es' ? ' selected' : '' ?>>Castellano</option>
                    <option value="en"<?= LANG === 'en' ? ' selected' : '' ?>>English</option>
                </select>
                <noscript><button type="submit" class="header__lang-btn"><?= __('lang.switch') ?></button></noscript>
            </form>
        </li>

        <!-- ── Mobile nav items (hidden on desktop) ──────────────── -->
        <li class="header__mobile-item">
            <a href="/#pollos" class="header__mobile-link"><?= __('home.landing.pollos') ?></a>
        </li>
        <li class="header__mobile-item">
            <a href="/#combinados" class="header__mobile-link"><?= __('home.landing.combinados') ?></a>
        </li>
        <li class="header__mobile-item">
            <a href="/#picapica" class="header__mobile-link"><?= __('home.landing.picapica') ?></a>
        </li>
        <li class="header__mobile-item">
            <a href="/faq" class="header__mobile-link"><?= __('nav.faq') ?></a>
        </li>
        <li class="header__mobile-item">
            <span class="header__mobile-lang-label"><?= __('lang.switch') ?>:</span>
            <div class="header__mobile-lang">
                <?php
                // Build language URLs preserving current path and query params
                $langSeparator = (strpos($baseUri, '?') !== false) ? '&' : '?';
                ?>
                <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=ca', ENT_QUOTES, 'UTF-8') ?>" class="header__mobile-lang-link<?= LANG === 'ca' ? ' header__mobile-lang-link--active' : '' ?>">CA</a>
                <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=es', ENT_QUOTES, 'UTF-8') ?>" class="header__mobile-lang-link<?= LANG === 'es' ? ' header__mobile-lang-link--active' : '' ?>">ES</a>
                <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=en', ENT_QUOTES, 'UTF-8') ?>" class="header__mobile-lang-link<?= LANG === 'en' ? ' header__mobile-lang-link--active' : '' ?>">EN</a>
            </div>
        </li>
    </ul>
</nav>
