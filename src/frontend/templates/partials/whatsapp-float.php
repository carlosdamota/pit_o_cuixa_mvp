<?php
/**
 * Pit o Cuixa — Contact Launcher Partial
 *
 * Fixed-position floating launcher in the bottom-right corner. The main
 * toggle (WhatsApp brand button) expands an upward popover with two options:
 * open WhatsApp in a new tab, or open the embedded chat widget
 * (chat-widget.php) in the same page.
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */
?>
<aside class="whatsapp-float" data-contact-launcher aria-label="Contacta con nosotros">
    <div class="whatsapp-float__menu" id="contact-menu" data-contact-menu aria-hidden="true">
        <a class="whatsapp-float__item" href="https://wa.me/<?= str_replace(' ', '', \Config::phone()) ?>"
           target="_blank" rel="noopener noreferrer">
            <span class="whatsapp-float__item-icon" aria-hidden="true">
                <img src="/img/icons/whatsapp.png" alt="" width="20" height="20">
            </span>
            <span>WhatsApp</span>
        </a>
        <button class="whatsapp-float__item" type="button" data-chat-open>
            <span class="whatsapp-float__item-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </span>
            <span>Chatea con nosotros</span>
        </button>
    </div>

    <button class="whatsapp-float__toggle" type="button" data-contact-toggle
            aria-expanded="false" aria-controls="contact-menu"
            aria-label="Contacta con nosotros">
        <span class="whatsapp-float__icon" aria-hidden="true">
            <img src="/img/icons/whatsapp.png" alt="" width="56" height="56">
        </span>
        <span class="whatsapp-float__fallback" aria-hidden="true">WP</span>
        <span class="whatsapp-float__tooltip" role="tooltip">Contacta con nosotros</span>
    </button>
</aside>