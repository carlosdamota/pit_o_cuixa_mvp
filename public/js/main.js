/**
 * Pit o Cuixa — Main Entry Point
 *
 * ESM module: imports and initialises all frontend modules.
 * Progressive enhancement: all features degrade gracefully.
 *
 * @module main
 */

import { initMenuFilter } from './menu-filter.js';

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
 * Initialise all modules when DOM is ready.
 */
function init() {
  initMenuFilter();
  registerServiceWorker();
}

// ── Wait for DOM ────────────────────────────────────────────────
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
