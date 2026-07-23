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

// Support both pre-localised and raw bilingual product data
$name        = $product['name']        ?? $product["name_{$lang}"]        ?? '';
$description = $product['description'] ?? $product["description_{$lang}"] ?? '';
$price       = (float) ($product['price'] ?? 0);
$priceFmt    = $product['price_formatted'] ?? number_format($price, 2, ',', '.') . ' €';
$imageUrl    = $product['image_url']   ?? null;
$orderUrl    = $product['last_shop_url'] ?? '#';
$slug        = $product['slug']        ?? '';
?>
<article class="product-card" data-product-slug="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
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

            <?php if ($orderUrl && $orderUrl !== '#'): ?>
                <a href="<?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>"
                   class="product-card__cta"
                   target="_blank"
                   rel="noopener noreferrer">
                    <?= __('menu.order.cta') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</article>
