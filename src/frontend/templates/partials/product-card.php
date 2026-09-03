<?php
/**
 * Pit o Cuixa — Product Card Partial
 *
 * Renders a single product card.
 *
 * Expected $product array:
 *   - slug, name_{LANG}, description_{LANG}, price, image_url, last_shop_url
 *   - Or pre-localised: name, description
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */

$lang = LANG;

// Support pre-localised, language-specific, and fallback data
$name = !empty($product['name'])
    ? $product['name']
    : (!empty($product["name_{$lang}"])
        ? $product["name_{$lang}"]
        : (!empty($product['name_es'])
            ? $product['name_es']
            : ($product['name_en'] ?? '')));

$description = !empty($product['description'])
    ? $product['description']
    : (!empty($product["description_{$lang}"])
        ? $product["description_{$lang}"]
        : (!empty($product['description_es'])
            ? $product['description_es']
            : ($product['description_en'] ?? '')));

$price       = (float) ($product['price'] ?? 0);
$priceFmt    = $product['price_formatted'] ?? number_format($price, 2, ',', '.') . ' €';
$slug        = $product['slug']        ?? '';
// Image fallback chain: scraped/Cloudinary URL (display-optimized via
// ProductImage::displayUrl — f_auto,q_auto,w_600) → generic /img/fallback_img.webp.
// Mirrors the client-side chain in public/js/main.js (which uses data-image-slug).
$imageUrl = \Pit\Cuixa\Backend\Services\ProductImage::displayUrl($product['image_url'] ?? null)
    ?? '/img/fallback_img.webp';
// Build the order URL: empty → disabled card; absolute http(s) → use as-is
// (admin may have saved a full URL); anything else is a shop-relative path,
// so prefix it with the shop domain from URL_PRODUCT.
$rawOrderUrl = $product['last_shop_url'] ?? '';
if ($rawOrderUrl === '') {
    $orderUrl = '#';
} elseif (str_starts_with($rawOrderUrl, 'http://') || str_starts_with($rawOrderUrl, 'https://')) {
    $orderUrl = $rawOrderUrl;
} else {
    $orderUrl = \Config::productUrl() . '/' . ltrim($rawOrderUrl, '/');
}
$productId   = (int) ($product['id']   ?? 0);

// Build search corpus from both locales (lowercased, space-separated)
$searchText = strtolower(
    ($product['name_es']        ?? '') . ' ' .
    ($product['name_en']        ?? '') . ' ' .
    ($product['description_es'] ?? '') . ' ' .
    ($product['description_en'] ?? '')
);
?>
<article class="product-card"
         data-product-id="<?= $productId ?>"
         data-product-slug="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
         data-search-text="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">

    <?php if ($orderUrl && $orderUrl !== '#'): ?>
        <a href="<?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>"
           class="product-card__link"
           data-track-click
           data-product-id="<?= $productId ?>"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
        </a>
    <?php endif; ?>

    <div class="product-card__image-wrap">
        <?php if ($imageUrl): ?>
            <img class="product-card__image"
                 src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                 loading="lazy"
                 width="300"
                 height="200">
        <?php else: ?>
            <div class="product-card__image-placeholder" aria-hidden="true">
                <span class="product-card__placeholder-icon">🍗</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="product-card__body">
        <h3 class="product-card__title"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>

        <?php if ($description): ?>
            <p class="product-card__desc"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="product-card__footer">
            <span class="product-card__price"><?= htmlspecialchars($priceFmt, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</article>
