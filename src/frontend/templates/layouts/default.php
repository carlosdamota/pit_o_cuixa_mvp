<!DOCTYPE html>
<?php
/**
 * Pit o Cuixa — Default HTML Layout
 *
 * Wraps all SSR pages with the HTML shell.
 * Receives from renderPage():
 *   $pageName  — current page identifier (home, menu, 404, etc.)
 *   $metaData  — SEO/meta array (title, description, canonical, og_image, langs)
 *   $pageData  — page-specific content variables
 *   $content   — rendered page template HTML (captured via ob_*)
 *
 * @package Pit\Cuixa\Frontend\Templates
 */

// Defaults if not set
$pageName ??= 'home';
$metaData ??= [];
$pageData ??= [];

$title       = $metaData['title']       ?? __('site.name');
$description = $metaData['description'] ?? __('site.description');
$canonical   = $metaData['canonical']   ?? \Config::siteUrl() . '/';
$ogImage     = $metaData['og_image']    ?? '/img/og-image.jpg';
$langs       = $metaData['langs']       ?? [];
$locale      = $pageData['locale']      ?? LANG;

$siteUrl = \Config::siteUrl();
?>
<html lang="<?= $locale ?>" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <!-- ── Title & Description ──────────────────────────────────────── -->
    <!-- SG-001: Unique title + meta description per page -->
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">

    <!-- ── Canonical ────────────────────────────────────────────────── -->
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">

    <!-- ── Open Graph (SG-002) ──────────────────────────────────────── -->
    <meta property="og:title"       content="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image"       content="<?= htmlspecialchars($siteUrl . $ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url"         content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type"        content="website">
    <meta property="og:locale"      content="<?= $locale === 'en' ? 'en_US' : 'es_ES' ?>">
    <meta property="og:site_name"   content="<?= __('site.name') ?>">

    <!-- ── Twitter Card ─────────────────────────────────────────────── -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image"       content="<?= htmlspecialchars($siteUrl . $ogImage, ENT_QUOTES, 'UTF-8') ?>">

    <!-- ── Hreflang Bilingual Links (SG-005) ────────────────────────── -->
    <?php foreach ($langs as $code => $url): ?>
    <link rel="alternate" hreflang="<?= $code ?>" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>

    <!-- x-default → Spanish (default locale) -->
    <?php if (isset($langs['es'])): ?>
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($langs['es'], ENT_QUOTES, 'UTF-8') ?>">
    <?php elseif (isset($langs[LANG])): ?>
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($langs[LANG], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <!-- ── Geographic Meta Tags (SG-006) ────────────────────────────── -->
    <meta name="geo.region"     content="ES-T">
    <meta name="geo.placename"  content="Torredembarra, Tarragona">
    <meta name="geo.position"   content="41.1412;1.3939">
    <meta name="ICBM"          content="41.1412, 1.3939">

    <!-- ── PWA / Theme ──────────────────────────────────────────────── -->
    <meta name="theme-color" content="#f7e721">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">

    <!-- ── Favicon ──────────────────────────────────────────────────── -->
    <link rel="icon" type="image/png" href="/img/favicon.png">
    <link rel="apple-touch-icon" href="/img/apple-touch-icon.svg">

    <!-- ── CSS ──────────────────────────────────────────────────────── -->
    <link rel="stylesheet" href="/css/tokens.css">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/layouts/header.css">
    <link rel="stylesheet" href="/css/layouts/footer.css">
    <link rel="stylesheet" href="/css/components/product-card.css">
    <link rel="stylesheet" href="/css/components/filter-bar.css">
    <link rel="stylesheet" href="/css/pages/error.css">

    <!-- Admin CSS (only on admin pages) -->
    <?php if (str_starts_with($pageName, 'admin/')): ?>
    <link rel="stylesheet" href="/css/pages/admin.css">
    <?php
    // Expose CSRF token as meta tag for admin JS AJAX calls
    $csrfMeta = $pageData['csrf_token'] ?? '';
    ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfMeta, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <!-- ── JSON-LD: LocalBusiness (SG-003) ──────────────────────────── -->
    <?php
    $localBusinessJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => ['Restaurant', 'LocalBusiness'],
        '@id' => $siteUrl . '/#business',
        'name' => __('site.name'),
        'url' => $siteUrl,
        'image' => $siteUrl . '/img/og-image.jpg',
        'telephone' => '+34977641805',
        'email' => 'fertasis@gmail.com',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Carrer Hort de l\'Oca, 12',
            'addressLocality' => 'Torredembarra',
            'addressRegion' => 'Tarragona',
            'postalCode' => '43830',
            'addressCountry' => 'ES'
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => '41.1413',
            'longitude' => '1.3894'
        ],
        'openingHoursSpecification' => [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'opens' => '11:00',
            'closes' => '23:00'
        ]
    ];
    ?>
    <script type="application/ld+json"><?= json_encode($localBusinessJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

    <!-- ── Page-specific JSON-LD slot (overridden in page templates) ── -->
    <?php if (!empty($metaData['jsonld'])): ?>
    <script type="application/ld+json"><?= json_encode($metaData['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php endif; ?>

    <!-- Self-hosted Quicksand font -->
    <!-- <link rel="preload" href="/fonts/quicksand-v1.woff2" as="font" type="font/woff2" crossorigin> -->
</head>
<body>

    <!-- ── Header ──────────────────────────────────────────────────── -->
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <!-- ── Main Content ─────────────────────────────────────────────── -->
    <main class="main" role="main">
        <?= $content ?? '' ?>
    </main>

    <!-- ── Footer ──────────────────────────────────────────────────── -->
    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <!-- ── JS ───────────────────────────────────────────────────────── -->
    <script type="module" src="/js/main.js"></script>
</body>
</html>
