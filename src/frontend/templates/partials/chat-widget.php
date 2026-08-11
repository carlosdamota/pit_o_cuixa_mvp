<?php
/**
 * Pit o Cuixa — Embedded Chat Widget Partial
 *
 * Floating chat window embedded in the same page (no navigation, no new
 * tab). Hidden by default; opened from the contact launcher menu
 * (whatsapp-float.php) via [data-chat-open]. Uses the same structure/IDs
 * as the standalone assistant page (#messages, #userInput, #sendBtn) so
 * the JS in main.js (initChatWidget) is identical.
 *
 * @package Pit\Cuixa\Frontend\Templates\Partials
 */
?>
<div class="chat-widget" data-chat-widget aria-hidden="true">
    <div class="chat-widget__header">
        <span class="chat-widget__title">Chat IA</span>
        <button class="chat-widget__close" type="button" data-chat-close aria-label="Cerrar chat">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <div id="messages" class="chat-widget__messages">
        <div class="bot-message">
            ¡Hola! Bienvenido a <strong>Pit o Cuixa</strong>.<br><br>
            Tengo información sobre:<br>
            - Nuestro menú y combos<br>
            - Precios actualizados<br>
            - Productos más vendidos<br>
            - Horarios<br>
            - Zonas de reparto<br><br>
            Escribe tu pregunta abajo.
        </div>
    </div>

    <div class="chat-widget__input">
        <input type="text" id="userInput" placeholder="Escribe tu pregunta..." autocomplete="off">
        <button type="button" id="sendBtn">Enviar</button>
    </div>
</div>