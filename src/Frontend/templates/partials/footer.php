<?php
/**
 * Pit o Cuixa — Footer Partial
 *
 * Site footer: brand, tagline, hours, copyright.
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */
?>
<footer class="footer" role="contentinfo">
    <div class="footer__inner container">
        <div class="footer__info">
            <p class="footer__brand"><?= __('site.name') ?></p>
            <p class="footer__tagline"><?= __('site.tagline') ?></p>
            <p class="footer__hours"><?= __('footer.hours') ?></p>
            <p class="footer__address"><?= __('home.info.address') ?></p>
            <p class="footer__phone">
                <a href="tel:<?= str_replace(' ', '', \Config::phone()) ?>"><?= __('home.info.phone') ?></a>
            </p>
        </div>
        <div class="footer__copy">
            &copy; <?= date('Y') ?> <?= __('site.name') ?>. <?= __('footer.rights') ?>
        </div>
    </div>
</footer>
