<?php
/**
 * Pit o Cuixa — Footer Partial
 *
 * Site footer: brand, tagline, hours, copyright.
 * Address and phone come from admin settings, with i18n/Config fallbacks.
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */

use Pit\Cuixa\Backend\Db\Repositories\Settings;

$companyAddress = Settings::companyAddress();
$companyPhone   = Settings::companyPhone();

// Admin values win; fall back to the original i18n/Config sources when unset.
$addressDisplay = $companyAddress !== '' ? $companyAddress : __('home.info.address');
$phoneDisplay   = $companyPhone !== '' ? $companyPhone : __('home.info.phone');
?>
<footer class="footer" role="contentinfo">
    <div class="footer__inner container">
        <div class="footer__info">
            <p class="footer__brand"><?= __('site.name') ?></p>
            <p class="footer__tagline"><?= __('site.tagline') ?></p>
            <p class="footer__hours"><?= __('footer.hours') ?></p>
            <p class="footer__address"><?= htmlspecialchars($addressDisplay, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="footer__phone">
                <a href="tel:<?= str_replace(' ', '', $companyPhone) ?>"><?= htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8') ?></a>
            </p>
            <p class="footer__nav">
                <a href="/faq" class="footer__link"><?= __('nav.faq') ?></a>
            </p>
        </div>
        <div class="footer__copy">
            <nav class="footer__legals">
                <a href="/privacy" class="footer__link">
                    <?= __('footer.privacy') ?>
                </a>

                <a href="/cookies" class="footer__link">
                    <?= __('footer.cookies') ?>
                </a>

                <a href="/terms" class="footer__link">
                    <?= __('footer.terms') ?>
                </a>
            </nav>
            
            &copy; <?= date('Y') ?> <?= __('site.name') ?>. <?= __('footer.rights') ?>
        </div>
    </div>
</footer>
