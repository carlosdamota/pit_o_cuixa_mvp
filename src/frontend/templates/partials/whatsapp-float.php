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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#fff">
                <path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24 11.36 11.36 0 003.58.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11.36 11.36 0 00.57 3.58 1 1 0 01-.25 1.01l-2.2 2.2z"/>
            </svg>
        </span>
        <span class="whatsapp-float__fallback" aria-hidden="true">WP</span>
        <span class="whatsapp-float__tooltip" role="tooltip">¡Haz tu pedido!</span>
    </a>
</aside>
