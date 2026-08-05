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
$langSeparator = (strpos($baseUri, '?') !== false) ? '&' : '?';

$languages = [
    'ca' => ['label' => 'Català',     'flag' => 'favicon_CAT.webp'],
    'es' => ['label' => 'Castellano', 'flag' => 'favicon_ES.webp'],
    'en' => ['label' => 'English',    'flag' => 'favicon_UK.webp'],
    'uk' => ['label' => 'Українська', 'flag' => 'favicon_UKR.webp'],
];
$currentLang = LANG;
$currentFlag = $languages[$currentLang]['flag'] ?? 'favicon_CAT.webp';
?>

<?php if ($currentPage === 'menu' || $currentPage === 'faq'): ?>
<nav class="header__nav container header__nav--menu-page" role="navigation" aria-label="<?= __('nav.menu') ?>">
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
<?php else: ?>
<nav class="header__nav container" role="navigation" aria-label="<?= __('nav.home') ?>">
    <a href="/" class="header__logo" aria-label="<?= __('site.name') ?>">
        <?= __('site.name') ?>
    </a>

    <button class="header__menu-toggle" aria-label="<?= __('nav.home') ?>" aria-expanded="false" data-menu-toggle>
        <span class="header__menu-icon"></span>
    </button>

    <ul class="header__menu" data-menu>
        <!-- ── Navigation links ────────────────────────────────── -->
        <li class="header__nav-item">
            <a href="/"
               class="header__mobile-link<?= $currentPage === 'home' ? ' header__mobile-link--active' : '' ?>">
                🏠 <?= __('nav.home') ?>
            </a>
        </li>
        <li class="header__desktop-item">
            <a href="/menu"
               class="header__mobile-link<?= $currentPage === 'menu' ? ' header__mobile-link--active' : '' ?>">
                📖 <?= __('nav.menu') ?>
            </a>
        </li>
        <li class="header__desktop-item">
            <a href="/faq"
               class="header__link<?= $currentPage === 'faq' ? ' header__link--active' : '' ?>">
                <?= __('nav.faq') ?>
            </a>
        </li>

        <!-- ── Desktop Language Selector Dropdown ──────────────── -->
        <li class="header__lang-item header__desktop-item">
            <form action="<?= htmlspecialchars($baseUri, ENT_QUOTES, 'UTF-8') ?>" method="get" class="header__lang-form">
                <select name="lang" class="header__lang-select" aria-label="<?= __('lang.switch') ?>" onchange="this.form.submit()">
                    <option value="ca"<?= LANG === 'ca' ? ' selected' : '' ?>>Català</option>
                    <option value="es"<?= LANG === 'es' ? ' selected' : '' ?>>Castellano</option>
                    <option value="en"<?= LANG === 'en' ? ' selected' : '' ?>>English</option>
                    <option value="uk"<?= LANG === 'uk' ? ' selected' : '' ?>>Українська</option>
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

        <!-- ── Mobile Language Segmented Buttons ────────────────── -->
        <li class="header__mobile-item header__mobile-lang-wrap">
            <span class="header__mobile-lang-label">🌐 <?= __('lang.switch') ?></span>
            <div class="header__mobile-lang">
                <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=ca', ENT_QUOTES, 'UTF-8') ?>"
                   class="header__mobile-lang-link<?= LANG === 'ca' ? ' header__mobile-lang-link--active' : '' ?>">Català (CA)</a>
                <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=es', ENT_QUOTES, 'UTF-8') ?>"
                   class="header__mobile-lang-link<?= LANG === 'es' ? ' header__mobile-lang-link--active' : '' ?>">Castellano (ES)</a>
                <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=en', ENT_QUOTES, 'UTF-8') ?>"
                   class="header__mobile-lang-link<?= LANG === 'en' ? ' header__mobile-lang-link--active' : '' ?>">English (EN)</a>
                <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=uk', ENT_QUOTES, 'UTF-8') ?>"
                   class="header__mobile-lang-link<?= LANG === 'uk' ? ' header__mobile-lang-link--active' : '' ?>">Українська (UK)</a>
            </div>
        </li>
    </ul>
</nav>
<?php endif; ?>
