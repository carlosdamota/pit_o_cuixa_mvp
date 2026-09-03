<?php
/**
 * Pit o Cuixa — Navigation Partial
 *
 * Renders the site header navigation: back button, logo and locale dropdown.
 * Used by every page that shows the site header (menu, faq, legal, error).
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */

// Build current URI without lang param for the links
$baseUri = $_SERVER['REQUEST_URI'];
$baseUri = preg_replace('/[?&]lang=[a-z]{2}/', '', $baseUri);
$langSeparator = (strpos($baseUri, '?') !== false) ? '&' : '?';

$languages = [
    'es' => ['label' => 'Castellano', 'flag' => 'favicon_ES.webp'],
    'en' => ['label' => 'English',    'flag' => 'favicon_UK.webp'],
    'uk' => ['label' => 'Українська', 'flag' => 'favicon_UKR.webp'],
];
$currentLang = LANG;
$currentFlag = $languages[$currentLang]['flag'] ?? 'favicon_ES.webp';
?>

<nav class="header__nav container" role="navigation" aria-label="<?= __('nav.menu') ?>">
    <div class="header__nav-left">
        <a href="/" class="header__back-btn" aria-label="<?= __('nav.back') ?? 'Volver' ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
    </div>

    <a href="/" class="header__logo" aria-label="<?= __('site.name') ?>">
        <?= __('site.name') ?>
    </a>

    <div class="header__nav-right">
        <!-- ── Custom Language Selector Dropdown with Flags ──────────────── -->
        <div class="header__lang-dropdown" data-lang-dropdown>
            <button type="button" class="header__lang-toggle" aria-haspopup="true" aria-expanded="false" data-lang-toggle aria-label="<?= __('lang.switch') ?>">
                <img src="/img/icons/<?= $currentFlag ?>" alt="<?= strtoupper($currentLang) ?>" class="header__lang-flag">
                <span class="header__lang-arrow">▼</span>
            </button>
            <ul class="header__lang-menu" data-lang-menu hidden>
                <?php foreach ($languages as $code => $info): ?>
                    <li>
                        <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=' . $code, ENT_QUOTES, 'UTF-8') ?>"
                           class="header__lang-option<?= $code === $currentLang ? ' header__lang-option--active' : '' ?>">
                            <img src="/img/icons/<?= $info['flag'] ?>" alt="<?= $info['label'] ?>" class="header__lang-option-flag">
                            <span><?= $info['label'] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
