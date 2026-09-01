/**
 * Pit o Cuixa — Main Entry Point
 *
 * ESM module: imports and initialises all frontend modules.
 * Progressive enhancement: all features degrade gracefully.
 *
 * @module main
 */

import { initMenuFilter } from './menu-filter.js';
import { initMenuSlider } from './menu-slider.js';
import { initCookieBanner, getCookieBanner } from './cookie-banner.js';
import { initAnimatedFavicon } from './animated-favicon.js';

/**
 * Initialise mobile menu toggle.
 * Adds click handler to [data-menu-toggle] button to toggle [data-menu] visibility.
 */
function initMobileMenu() {
  const toggle = document.querySelector('[data-menu-toggle]');
  const menu = document.querySelector('[data-menu]');

  if (!toggle || !menu) {
    return;
  }

  toggle.addEventListener('click', () => {
    const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', !isExpanded);
    menu.classList.toggle('header__menu--open');
  });

  // Close menu when clicking outside
  document.addEventListener('click', (event) => {
    if (!toggle.contains(event.target) && !menu.contains(event.target)) {
      toggle.setAttribute('aria-expanded', 'false');
      menu.classList.remove('header__menu--open');
    }
  });

  // Close menu on escape key
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menu.classList.contains('header__menu--open')) {
      toggle.setAttribute('aria-expanded', 'false');
      menu.classList.remove('header__menu--open');
      toggle.focus();
    }
  });
}

/**
 * Initialise custom language selector dropdown.
 * Supports multiple [data-lang-dropdown] instances (header + footer): each
 * instance runs in its own closure; the single document-level outside-click
 * and Escape handlers only act on whichever instance is currently open.
 *
 * Header/footer instances are positioned purely by CSS (absolute within their
 * toggle). The ONBOARDING instance, however, lives inside the landing stacking
 * context and would be trapped below the fixed chat/WhatsApp launchers, so it
 * is lifted to <body> with position:fixed + z-index:99999 when opened (and
 * restored to its parent on close).
 */
function initLangDropdown() {
  const instances = [];

  document.querySelectorAll('[data-lang-dropdown]').forEach((dropdown) => {
    const toggle = dropdown.querySelector('[data-lang-toggle]');
    const menu = dropdown.querySelector('[data-lang-menu]');
    if (!toggle || !menu) {
      return;
    }

    // The onboarding instance sits inside the landing stacking context, which
    // traps an absolutely-positioned menu below the fixed chat/WhatsApp
    // launchers. Lift it out to <body> with a fixed position + high z-index so
    // it renders above those floating buttons. Header/footer instances rely on
    // CSS alone.
    const isOnboarding = dropdown.classList.contains('onboarding__lang-dropdown');

    const positionMenu = () => {
      if (!isOnboarding) return;
      const rect = toggle.getBoundingClientRect();
      document.body.appendChild(menu);
      menu.style.position = 'fixed';
      menu.style.bottom = `${window.innerHeight - rect.top + 6}px`;
      menu.style.right = `${window.innerWidth - rect.right}px`;
      menu.style.zIndex = '99999'; // above chat input (99998) and whatsapp (9999)
    };

    const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';

    const close = () => {
      toggle.setAttribute('aria-expanded', 'false');
      menu.setAttribute('hidden', '');
      if (isOnboarding && menu.parentNode !== dropdown) {
        dropdown.appendChild(menu);
        menu.style.position = '';
        menu.style.bottom = '';
        menu.style.right = '';
        menu.style.zIndex = '';
      }
    };

    instances.push({ dropdown, toggle, isOpen, close });

    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      if (isOpen()) {
        close();
      } else {
        toggle.setAttribute('aria-expanded', 'true');
        menu.removeAttribute('hidden');
        positionMenu(); // lift onboarding menu to <body> above chat (99998) and onboarding (9999)
      }
    });
  });

  if (instances.length === 0) {
    return;
  }

  document.addEventListener('click', (e) => {
    instances.forEach((instance) => {
      if (instance.isOpen() && !instance.dropdown.contains(e.target)) {
        instance.close();
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') {
      return;
    }
    instances.forEach((instance) => {
      if (instance.isOpen()) {
        instance.close();
        instance.toggle.focus();
      }
    });
  });
}

/**
 * Register the service worker for PWA offline support.
 * Only registers over HTTPS or localhost.
 */
function registerServiceWorker() {
  if (!('serviceWorker' in navigator)) {
    return; // SW not supported — degrade gracefully
  }

  const isLocalhost = window.location.hostname === 'localhost'
    || window.location.hostname === '127.0.0.1'
    || window.location.hostname === '[::1]';

  if (!isLocalhost && window.location.protocol !== 'https:') {
    return; // SW requires HTTPS (except localhost)
  }

  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').then((reg) => {
      if (reg.active) {
        // SW registered and active
      }
    }).catch(() => {
      // SW registration failed — non-critical, degrade gracefully
    });
  });
}

/**
 * Product image fallback chain.
 *
 * Product images come from the scraper (Cloudinary) and can break at any time
 * (removed, renamed, network). A broken product image falls back to the
 * generic local placeholder:
 *
 *   Cloudinary URL → /img/fallback_img.webp
 *
 * Implemented with event delegation in the CAPTURE phase because the `error`
 * event does NOT bubble — but it does pass through capture, so a
 * document-level listener catches every broken <img>. This also keeps the
 * fallback out of the templates, which the Content-Security-Policy
 * (script-src 'self') would otherwise block with inline onerror handlers.
 */
const GENERIC_IMAGE_FALLBACKS = ['/img/fallback_img.webp'];

/**
 * Swap a broken product image to the next fallback in its chain, or hide it
 * once every fallback is exhausted.
 * @param {HTMLImageElement} img
 */
function handleProductImageError(img) {
  const currentPath = new URL(img.src).pathname;
  const fallbacks = GENERIC_IMAGE_FALLBACKS;
  const currentIndex = fallbacks.indexOf(currentPath);
  const nextIndex = currentIndex + 1;

  if (nextIndex < fallbacks.length) {
    img.src = fallbacks[nextIndex];
    return;
  }

  // All fallbacks exhausted — hide the broken image instead of showing the
  // browser's broken-image icon. The layout keeps its shape via the wrapper.
  img.style.visibility = 'hidden';
}

/**
 * Attach the image fallback listener. Only product images (scraped sources)
 * and featured accordion content images participate; logos, slider and admin
 * images are left untouched.
 */
function initImageFallback() {
  document.addEventListener('error', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLImageElement)) {
      return;
    }
    if (!target.classList.contains('product-card__image')
        && !target.classList.contains('listview-item__img')
        && !target.classList.contains('accordion-content__img')) {
      return;
    }
    handleProductImageError(target);
  }, true);
}

/**
 * Initialise the contact launcher (floating WhatsApp button → expandable
 * menu). The toggle expands an upward popover with two options: WhatsApp
 * (new tab) or the embedded chat widget (chat-widget.php). The menu closes
 * on outside click and on Escape.
 */
// Shared handle so the launcher can close the chat widget when the user
// clicks the floating button while the chat is open.
let chatController = null;

function initContactLauncher() {
  const launcher = document.querySelector('[data-contact-launcher]');
  if (!launcher) {
    return;
  }

  const toggle = launcher.querySelector('[data-contact-toggle]');
  const menu = launcher.querySelector('[data-contact-menu]');
  if (!toggle || !menu) {
    return;
  }

  const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';

  const close = () => {
    toggle.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-hidden', 'true');
  };

  const open = () => {
    toggle.setAttribute('aria-expanded', 'true');
    menu.setAttribute('aria-hidden', 'false');
  };

  toggle.addEventListener('click', (event) => {
    event.stopPropagation();

    // If the chat widget is open, the launcher button closes the chat
    // instead of toggling the menu (keeps launcher and chat exclusive).
    if (chatController && chatController.isOpen()) {
      chatController.close();
      return;
    }

    if (isOpen()) {
      close();
    } else {
      open();
    }
  });

  // Close the menu once an option is chosen (chat opens the widget).
  menu.addEventListener('click', () => {
    if (isOpen()) {
      close();
    }
  });

  // Close on outside click
  document.addEventListener('click', (event) => {
    if (isOpen() && !launcher.contains(event.target)) {
      close();
    }
  });

  // Close on Escape
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) {
      close();
      toggle.focus();
    }
  });
}

/**
 * Escape HTML metacharacters so untrusted text is rendered as text, never
 * markup. Applied BEFORE the markdown-to-HTML conversion so only the tags we
 * generate (<strong>, <br>) reach innerHTML.
 */
function escapeHtml(str) {
  return str.replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[ch]);
}

/**
 * Initialise the embedded chat widget (chat-widget.php).
 *
 * The widget is hidden by default and opened from the launcher menu
 * ([data-chat-open]). It POSTs { message } to /api/chat and renders the
 * reply with simple markdown (bold / line breaks).
 */
function initChatWidget() {
  const widget = document.querySelector('[data-chat-widget]');
  if (!widget) {
    return;
  }

  const userInput = widget.querySelector('#userInput');
  const sendBtn = widget.querySelector('#sendBtn');
  const messagesContainer = widget.querySelector('#messages');
  const closeBtn = widget.querySelector('[data-chat-close]');
  const openTrigger = document.querySelector('[data-chat-open]');

  // Capture the three repositionable chrome nodes up front. Keeping direct
  // references lets restore/sweep always find them — even if a previous
  // open/close was interrupted and left one floating in <body> with a fixed
  // position (which would otherwise block clicks on the catalogue).
  const headerChrome = widget.querySelector('.chat-widget__header');
  const inputChrome = widget.querySelector('.chat-widget__input');
  const chromeNodes = [headerChrome, messagesContainer, inputChrome].filter(Boolean);

  if (!userInput || !sendBtn || !messagesContainer || !closeBtn) {
    return;
  }

  const isOpen = () => widget.getAttribute('aria-hidden') !== 'true';

  // Lift the widget chrome (header, message body, input) out of its
  // container and pin each piece to the viewport, so the whole chat sits
  // above the onboarding overlay (z-index 10000) but below the lang menu
  // (99999). Read every rect BEFORE moving any node — reparenting one
  // shifts the layout of the others.
  const positionChrome = () => {
    const header = widget.querySelector('.chat-widget__header');
    const body = widget.querySelector('#messages');
    const input = widget.querySelector('.chat-widget__input');

    const headerRect = header ? header.getBoundingClientRect() : null;
    const bodyRect = body ? body.getBoundingClientRect() : null;
    const inputRect = input ? input.getBoundingClientRect() : null;

    const lift = (el, rect, role) => {
      if (!el || !rect) return;
      if (!el.__chatParent) el.__chatParent = el.parentNode;
      document.body.appendChild(el);
      el.style.position = 'fixed';
      el.style.left = `${rect.left}px`;
      el.style.width = `${rect.width}px`;
      el.style.zIndex = '99998'; // above onboarding (10000), below lang (99999)
      el.style.boxShadow = 'var(--shadow-lg)';
      if (role === 'input') {
        el.style.bottom = `${window.innerHeight - rect.bottom}px`;
        el.style.top = '';
        el.style.height = '';
        el.style.borderRadius = '0 0 var(--radius) var(--radius)';
      } else if (role === 'header') {
        el.style.top = `${rect.top}px`;
        el.style.bottom = '';
        el.style.height = '';
        el.style.borderRadius = 'var(--radius) var(--radius) 0 0';
      } else {
        el.style.top = `${rect.top}px`;
        el.style.bottom = '';
        el.style.height = `${rect.height}px`;
        el.style.borderRadius = '0';
      }
    };

    lift(header, headerRect, 'header');
    lift(body, bodyRect, 'body');
    lift(input, inputRect, 'input');

    widget.style.display = 'none'; // hide the now-empty shell
  };

  const restoreChrome = () => {
    // Restore every chrome node back into the widget shell and clear all the
    // inline styles applied by positionChrome(). Uses the captured node
    // references (never a fresh document lookup) and guards each node
    // separately so a single missing/broken node can't abort the cleanup of
    // the others. Any node left floating in <body> with position:fixed would
    // block clicks over the catalogue, so this must run to completion.
    chromeNodes.forEach((el) => {
      try {
        const parent = el.__chatParent || widget;
        parent.appendChild(el);
        el.style.position = '';
        el.style.top = '';
        el.style.left = '';
        el.style.width = '';
        el.style.height = '';
        el.style.bottom = '';
        el.style.zIndex = '';
        el.style.boxShadow = '';
        el.style.borderRadius = '';
        el.__chatParent = null;
      } catch (err) {
        // Keep cleaning the remaining nodes — a single failure must not leave
        // the rest orphaned.
        console.error('Failed to restore chat chrome node:', err);
      }
    });
    // Always let the CSS govern the shell; never leave an inline display
    // override behind after a close.
    widget.style.display = '';
  };

  // Belt-and-suspenders: if the chat is closed but any chrome node is still
  // floating in <body> (left behind by an interrupted open/close or a JS error
  // mid-transition), force it back into the shell so it can never capture
  // clicks. Runs at init and after every close.
  const sweepChromeless = () => {
    if (isOpen()) {
      return;
    }
    let stray = false;
    chromeNodes.forEach((el) => {
      // A node is "stray" when it was reparented out of the shell and left
      // floating in <body> with a fixed position — precisely the state that
      // blocks clicks on the catalogue while the chat is closed.
      if (el.parentNode === document.body && el.style.position === 'fixed') {
        stray = true;
        const parent = el.__chatParent || widget;
        parent.appendChild(el);
        el.style.position = '';
        el.style.top = '';
        el.style.left = '';
        el.style.width = '';
        el.style.height = '';
        el.style.bottom = '';
        el.style.zIndex = '';
        el.style.boxShadow = '';
        el.style.borderRadius = '';
        el.__chatParent = null;
      }
    });
    if (stray) {
      widget.style.display = ''; // let CSS hide the shell again
    }
  };

  const open = () => {
    widget.setAttribute('aria-hidden', 'false');
    positionChrome();
    userInput.focus();
  };

  const close = () => {
    widget.setAttribute('aria-hidden', 'true');
    restoreChrome();
    sweepChromeless(); // guarantee no orphaned chrome remains after closing
  };

  if (openTrigger) {
    openTrigger.addEventListener('click', open);
  }

  closeBtn.addEventListener('click', close);

  // Expose state to the launcher so its toggle can close the chat when open.
  chatController = { isOpen, close };

  // On load, clear any chrome that a previous session left orphaned in <body>
  // (see sweepChromeless). The chat starts closed, so nothing should be
  // floating at this point.
  sweepChromeless();

  // Escape also closes the chat widget (open state only)
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) {
      close();
    }
  });

  async function sendMessage() {
    const text = userInput.value.trim();
    if (!text) {
      return;
    }

    // 1. Render user message
    const userDiv = document.createElement('div');
    userDiv.className = 'user-message';
    userDiv.textContent = text;
    messagesContainer.appendChild(userDiv);

    userInput.value = '';
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    // 2. Render temporary loading indicator
    const botDiv = document.createElement('div');
    botDiv.className = 'bot-message';
    botDiv.textContent = 'Escribiendo...';
    messagesContainer.appendChild(botDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    try {
      // 3. Request /api/chat (POST — message goes in the body)
      const response = await fetch('/api/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ message: text })
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const data = await response.json();
      let textReply = data.reply || data.message || 'Lo siento, no pude procesar la solicitud.';

      // Escape untrusted text first, then apply simple Markdown bolding and
      // line breaks. Only our generated tags reach innerHTML.
      textReply = escapeHtml(textReply)
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');

      botDiv.innerHTML = textReply;
    } catch (error) {
      console.error('Fetch error details:', error);
      botDiv.textContent = 'Error al conectar con el servidor.';
    }

    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  sendBtn.addEventListener('click', sendMessage);

  userInput.addEventListener('keypress', (event) => {
    if (event.key === 'Enter') {
      sendMessage();
    }
  });
}

/**
 * Initialise all modules when DOM is ready.
 */
function init() {
  initMobileMenu();
  initLangDropdown();
  initMenuFilter();
  initMenuSlider();
  initImageFallback();
  initAnimatedFavicon();
  initContactLauncher();
  initChatWidget();
  registerServiceWorker();
  initCookieBanner();
}

// ── Wait for DOM ────────────────────────────────────────────────
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

