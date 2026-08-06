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
 */
function initLangDropdown() {
  const instances = [];

  document.querySelectorAll('[data-lang-dropdown]').forEach((dropdown) => {
    const toggle = dropdown.querySelector('[data-lang-toggle]');
    const menu = dropdown.querySelector('[data-lang-menu]');
    if (!toggle || !menu) {
      return;
    }

    const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';
    const close = () => {
      toggle.setAttribute('aria-expanded', 'false');
      menu.setAttribute('hidden', '');
    };

    instances.push({ dropdown, toggle, isOpen, close });

    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      if (isOpen()) {
        close();
      } else {
        toggle.setAttribute('aria-expanded', 'true');
        menu.removeAttribute('hidden');
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
 * participate; logos, slider and admin images are left untouched.
 */
function initImageFallback() {
  document.addEventListener('error', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLImageElement)) {
      return;
    }
    if (!target.classList.contains('product-card__image') && !target.classList.contains('listview-item__img')) {
      return;
    }
    handleProductImageError(target);
  }, true);
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
  registerServiceWorker();
}

// ── Wait for DOM ────────────────────────────────────────────────
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

