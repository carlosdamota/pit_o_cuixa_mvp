<?php
/**
 * Pit o Cuixa — WhatsApp Floating Button Partial
 *
 * Fixed-position CTA linking to the restaurant's WhatsApp number.
 * Appears on every page as a quick-order entry point.
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */
?>
<aside class="whatsapp-float" aria-label="¡Haz tu pedido!">
    <a class="whatsapp-float__link"
       href="https://wa.me/<?= str_replace(' ', '', \Config::phone()) ?>"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="¡Haz tu pedido!">
        <span class="whatsapp-float__icon" aria-hidden="true">
            <img src="/img/icons/whatsapp.png" alt="" width="56" height="56">
        </span>
        <span class="whatsapp-float__fallback" aria-hidden="true">WP</span>
        <span class="whatsapp-float__tooltip" role="tooltip">¡Haz tu pedido!</span>
    </a>
</aside>
