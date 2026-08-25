<?php
/**
 * Pit o Cuixa — Onboarding Home Page Template
 *
 * Fullscreen fixed index landing with dominant yellow oval background arc
 * and interactive drag & drop mode selector with animated walking chicken logo using /img/icons/favicon.png.
 *
 * @package Pit\Cuixa\Frontend\Templates\Pages
 */

$baseUri = $_SERVER['REQUEST_URI'] ?? '/';
$baseUri = preg_replace('/[?&]lang=[a-z]{2}/', '', $baseUri);
$langSeparator = (strpos($baseUri, '?') !== false) ? '&' : '?';
$langSuffix = LANG === 'ca' ? '' : $langSeparator . 'lang=' . LANG;

$languages = [
    'ca' => ['label' => 'Català',     'flag' => 'favicon_CAT.webp'],
    'es' => ['label' => 'Castellano', 'flag' => 'favicon_ES.webp'],
    'en' => ['label' => 'English',    'flag' => 'favicon_UK.webp'],
    'uk' => ['label' => 'Українська', 'flag' => 'favicon_UKR.webp'],
];
$currentLang = LANG;
$currentFlag = $languages[$currentLang]['flag'] ?? 'favicon_CAT.webp';
?>
<!-- ============================================================
     Landing Onboarding (fullscreen fixed 100dvh)
     ============================================================ -->
<section class="landing landing--onboarding">
    <!-- Dominant yellow oval background arc (~75% height) -->
    <div class="onboarding__bg-yellow" aria-hidden="true">
        <svg viewBox="0 0 500 500" preserveAspectRatio="none">
            <path d="M 0,0 L 500,0 L 500,380 C 370,500 130,500 0,380 Z" fill="var(--color-primary)"/>
        </svg>
    </div>

    <div class="onboarding__container">

        <!-- ── Top Phone Call Button ──────────────────────────────────── -->
        <header class="onboarding__top">
            <a class="onboarding__call-btn" href="tel:<?= str_replace(' ', '', \Config::phone()) ?>"
               aria-label="<?= __('home.info.phone') ?>">
                <svg class="onboarding__call-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">
                    <path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24 11.36 11.36 0 003.58.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11.36 11.36 0 00.57 3.58 1 1 0 01-.25 1.01l-2.2 2.2z"/>
                </svg>
                <span class="onboarding__call-text"><?= __('home.call.cta') ?></span>
            </a>
        </header>

        <!-- ── Animated Brand Logo: Walking Chicken typing "PIT o CUIXA" ── -->
        <div class="onboarding__brand" aria-label="Pit o Cuixa">
            <div class="onboarding__anim-brand">
                <!-- Walking Chicken (Flipped scaleX(-1) to face right) -->
                <div class="onboarding__anim-chicken">
                    <img src="/img/icons/favicon.png" width="54" height="54" alt="Pollo Pit o Cuixa">
                </div>

                <!-- Gradient Letter-by-Letter Text -->
                <div class="onboarding__anim-text">
                    <span class="anim-letter letter-1">P</span>
                    <span class="anim-letter letter-2">I</span>
                    <span class="anim-letter letter-3">T</span>
                    <span class="anim-space">&nbsp;</span>
                    <span class="anim-letter letter-4 anim-letter--small">o</span>
                    <span class="anim-space">&nbsp;</span>
                    <span class="anim-letter letter-5">C</span>
                    <span class="anim-letter letter-6">U</span>
                    <span class="anim-letter letter-7">I</span>
                    <span class="anim-letter letter-8">X</span>
                    <span class="anim-letter letter-9">A</span>
                </div>
            </div>
        </div>

        <!-- ── Drag & Drop Interactive Section ───────────────────────── -->
        <div class="onboarding__drag-section" id="drag-section">

            <!-- Draggable Item Left: Cutlery ("en local") -->
            <div class="onboarding__drag-item onboarding__drag-item--left"
                 id="drag-local"
                 draggable="true"
                 data-mode="local"
                 role="button"
                 tabindex="0"
                 aria-grabbed="false"
                 aria-label="<?= __('home.onboarding.in_local') ?>">
                <div class="onboarding__drag-circle">
                    <img src="/img/icons/a_local_icon.webp" width="44" height="44" alt="<?= __('home.onboarding.in_local') ?>">
                </div>
                <span class="onboarding__drag-label"><?= __('home.onboarding.in_local') ?></span>
            </div>

            <!-- Draggable Item Right: Motorcycle ("a domicilio") -->
            <div class="onboarding__drag-item onboarding__drag-item--right"
                 id="drag-delivery"
                 draggable="true"
                 data-mode="delivery"
                 role="button"
                 tabindex="0"
                 aria-grabbed="false"
                 aria-label="<?= __('home.onboarding.delivery') ?>">
                <div class="onboarding__drag-circle">
                    <img src="/img/icons/a_domicilio_icon.webp" width="44" height="44" alt="<?= __('home.onboarding.delivery') ?>">
                </div>
                <span class="onboarding__drag-label"><?= __('home.onboarding.delivery') ?></span>
            </div>

            <!-- Animated Flowing Arrows SVG pointing down to lowered target -->
            <svg class="onboarding__funnel-svg" viewBox="0 0 300 240" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                    <marker id="arrowhead-left" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
                        <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="rgba(44, 24, 16, 0.75)" />
                    </marker>
                    <marker id="arrowhead-right" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
                        <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="rgba(44, 24, 16, 0.75)" />
                    </marker>

                    <linearGradient id="arrow-gradient-left" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="rgba(44, 24, 16, 0.3)" />
                        <stop offset="100%" stop-color="rgba(44, 24, 16, 0.85)" />
                    </linearGradient>
                    <linearGradient id="arrow-gradient-right" x1="100%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="rgba(44, 24, 16, 0.3)" />
                        <stop offset="100%" stop-color="rgba(44, 24, 16, 0.85)" />
                    </linearGradient>
                </defs>

                <!-- Left guide arrow pointing to lower target center (140, 170) -->
                <path class="onboarding__arrow-path onboarding__arrow-path--left"
                      d="M 60 30 Q 90 110 140 170"
                      stroke="url(#arrow-gradient-left)" stroke-width="3.5" fill="none"
                      marker-end="url(#arrowhead-left)" />

                <!-- Right guide arrow pointing to lower target center (160, 170) -->
                <path class="onboarding__arrow-path onboarding__arrow-path--right"
                      d="M 240 30 Q 210 110 160 170"
                      stroke="url(#arrow-gradient-right)" stroke-width="3.5" fill="none"
                      marker-end="url(#arrowhead-right)" />
            </svg>

            <!-- Drop Zone Target: Local Circle Icon -->
            <div class="onboarding__target" id="drop-target" aria-label="<?= __('site.name') ?>">
                <div class="onboarding__target-ring"></div>
                <div class="onboarding__target-inner">
                    <img src="/img/icons/el_local_icon.webp" width="64" height="64" alt="<?= __('site.name') ?>">
                </div>
                <span class="onboarding__target-label"><?= __('site.name') ?></span>
            </div>

            <!-- <p class="onboarding__drag-hint"><?= __('home.onboarding.drag_hint') ?></p> -->
        </div>

        <?php
        $quotesList = __('home.quotes');
        if (is_array($quotesList) && !empty($quotesList)):
        ?>
        <!-- ── Rotating Positive Quotes Card (White Section) ────────── -->
        <div class="onboarding__quote-card"
             id="home-quote-box"
             data-quotes="<?= htmlspecialchars(json_encode($quotesList, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
             aria-live="polite">
            <div class="onboarding__quote-watermark" aria-hidden="true">“</div>
            <div class="onboarding__quote-content">
                
                <p class="onboarding__quote-text" id="home-quote-text">“<?= htmlspecialchars($quotesList[0], ENT_QUOTES, 'UTF-8') ?>”</p>
                <div class="onboarding__quote-dots" id="home-quote-dots" aria-hidden="true">
                    <?php for ($i = 0; $i < count($quotesList); $i++): ?>
                        <span class="quote-dot<?= $i === 0 ? ' quote-dot--active' : '' ?>"></span>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Bottom Footer Area ─────────────────────────────────────── -->
        <footer class="onboarding__footer">
            <!-- FAQ Button (left) -->
            <a href="/faq<?= $langSuffix ?>" class="onboarding__faq-btn" aria-label="<?= __('nav.faq') ?>" title="<?= __('nav.faq') ?>">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span class="onboarding__faq-text"><?= __('nav.faq') ?></span>
            </a>
            
            <!-- Floating Cookie Settings Button -->
            <button
                id="cookie-settings-home"
                class="cookie-settings-button"
                data-cookie-settings
                hidden
                aria-label="Configuración de cookies"
                title="Configuración de cookies">

                <img
                    src="/img/icons/galleta.png"
                    alt=""
                    width="24"
                    height="24">

            </button>

            <!-- PWA Install CTA Button (centered in footer grid) -->
            <div id="pwa-install-container" class="onboarding__pwa-wrapper" hidden
                 data-ios-title="<?= __('pwa.ios.title') ?>"
                 data-ios-step1="<?= __('pwa.ios.step1') ?>"
                 data-ios-step2="<?= __('pwa.ios.step2') ?>"
                 data-ios-gotit="<?= __('pwa.ios.gotit') ?>">
                <button type="button" id="pwa-install-btn" class="onboarding__pwa-btn" aria-label="<?= __('pwa.install') ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <span><?= __('pwa.install') ?></span>
                </button>
            </div>

            <!-- Language Dropdown (right) -->
            <div class="onboarding__lang-dropdown" data-lang-dropdown>
                <button type="button" class="onboarding__lang-toggle"
                        aria-haspopup="listbox" aria-expanded="false"
                        aria-controls="footer-lang-menu" data-lang-toggle
                        aria-label="<?= __('lang.switch') ?>">
                    <img src="/img/icons/<?= $currentFlag ?>" alt="" class="onboarding__lang-flag">
                    <span class="onboarding__lang-arrow" aria-hidden="true">▼</span>
                </button>
                <ul class="onboarding__lang-menu" id="footer-lang-menu" data-lang-menu hidden>
                    <?php foreach ($languages as $code => $info): ?>
                        <li>
                            <a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=' . $code, ENT_QUOTES, 'UTF-8') ?>"
                               class="onboarding__lang-option"
                               <?= $code === $currentLang ? 'aria-current="true"' : '' ?>>
                                <img src="/img/icons/<?= $info['flag'] ?>" alt="" class="onboarding__lang-option-flag">
                                <span><?= $info['label'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </footer>

    </div>

    <?php require __DIR__ . '/../components/cookie-banner.php'; ?>
</section>
<script type="module" src="/js/home-onboarding.js<?= assetVersion('/js/home-onboarding.js') ?>"></script>
