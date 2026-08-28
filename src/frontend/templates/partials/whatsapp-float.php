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

use Pit\Cuixa\Backend\Db\Repositories\Settings;

// Secondary WhatsApp mobile when set, otherwise the primary phone.
$waPhone = str_replace(' ', '', Settings::companyWhatsapp());
?>
<aside class="whatsapp-float" data-contact-launcher aria-label="Contacta con nosotros">
    <div class="whatsapp-float__menu" id="contact-menu" data-contact-menu aria-hidden="true">
        <a class="whatsapp-float__item" href="https://wa.me/<?= $waPhone ?>"
           target="_blank" rel="noopener noreferrer">
            <span class="whatsapp-float__item-icon" aria-hidden="true">
                <img src="/img/icons/whatsapp.png" alt="" width="20" height="20">
            </span>
            <span>WhatsApp</span>
        </a>
        <button class="whatsapp-float__item" type="button" data-chat-open>
            <span class="whatsapp-float__item-icon" aria-hidden="true">
                <img src="/img/icons/chat_icon.webp" alt="" width="20" height="20">
            </span>
            <span>Chatea con nosotros</span>
        </button>
    </div>

    <button class="whatsapp-float__toggle" type="button" data-contact-toggle
            aria-expanded="false" aria-controls="contact-menu"
            aria-label="Contacta con nosotros">
        <span class="whatsapp-float__icon" aria-hidden="true">
            <img src="/img/icons/chat_icon.webp" alt="" width="56" height="56">
        </span>
        <span class="whatsapp-float__fallback" aria-hidden="true">Chat</span>
        <span class="whatsapp-float__tooltip" role="tooltip">Contacta con nosotros</span>
    </button>
</aside>