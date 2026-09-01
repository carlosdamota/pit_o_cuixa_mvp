/**
 * Floating Buttons — Footer Pinning
 *
 * Prevents the fixed floating buttons (WhatsApp, cookie settings, chat widget)
 * from overlapping the footer when it scrolls into view. When the footer enters
 * the viewport, the buttons are lifted to sit just above the footer's top edge.
 * When the footer is fully below the viewport, buttons return to their normal
 * CSS `bottom` values.
 *
 * Exits early on home/admin pages where no `footer.footer` exists.
 *
 * @module floating-buttons
 */

(function initFloatingButtons() {
  'use strict';

  const footer = document.querySelector('footer.footer');
  if (!footer) return;

  const whatsappBtn = document.querySelector('[data-contact-launcher]');
  const chatWidget = document.querySelector('[data-chat-widget]');
  const cookieBtn = document.querySelector('[data-cookie-settings]');

  if (!whatsappBtn && !cookieBtn && !chatWidget) return;

  // Default CSS bottom values (must match whatsapp-float.css & cookie-banner.css)
  const DESKTOP_WHATSAPP_BOTTOM = 80;
  const DESKTOP_COOKIE_BOTTOM = 80;
  const DESKTOP_CHAT_BOTTOM = 152; // 80 + 56(whatsapp height) + 16(gap)

  const MOBILE_WHATSAPP_BOTTOM = 16;
  const MOBILE_COOKIE_BOTTOM = 16;
  const MOBILE_CHAT_BOTTOM = 76; // 16 + 48(mobile whatsapp height) + 12(gap)

  // 767 to match whatsapp-float.css & chat-widget.css (both use max-width: 767px).
  // The cookie button CSS uses 768px, but 767 keeps all three JS/CSS in sync.
  const MOBILE_BREAKPOINT = 767;

  // Measure the whatsapp-float element height (56px desktop, 48px mobile).
  // We measure once and use CSS media query awareness via matchMedia.
  const mql = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT}px)`);

  function isMobile() {
    return mql.matches;
  }

  function updatePositions() {
    const rect = footer.getBoundingClientRect();

    // Footer is below the viewport — not overlapping anything yet
    if (rect.top >= window.innerHeight) {
      removeInlineStyles();
      return;
    }

    // Footer is fully above the viewport (scrolled past it) — let CSS handle it
    if (rect.bottom <= 0) {
      removeInlineStyles();
      return;
    }

    // Footer is partially visible — calculate lift. 12px subtracted so the
    // buttons sit slightly over the footer's top edge (per product feedback).
    const lift = window.innerHeight - rect.top - 12;
    const mobile = isMobile();

    if (whatsappBtn) {
      const base = mobile ? MOBILE_WHATSAPP_BOTTOM : DESKTOP_WHATSAPP_BOTTOM;
      whatsappBtn.style.bottom = `${base + lift}px`;
    }

    if (cookieBtn) {
      const base = mobile ? MOBILE_COOKIE_BOTTOM : DESKTOP_COOKIE_BOTTOM;
      cookieBtn.style.bottom = `${base + lift}px`;
    }

    // Chat widget: only adjust when closed (open = positionChrome handles it)
    if (chatWidget) {
      const chatOpen = chatWidget.getAttribute('aria-hidden') !== 'true';
      if (!chatOpen) {
        const base = mobile ? MOBILE_CHAT_BOTTOM : DESKTOP_CHAT_BOTTOM;
        chatWidget.style.bottom = `${base + lift}px`;
      }
    }
  }

  function removeInlineStyles() {
    if (whatsappBtn) whatsappBtn.style.bottom = '';
    if (cookieBtn) cookieBtn.style.bottom = '';
    if (chatWidget) chatWidget.style.bottom = '';
  }

  let ticking = false;

  function onScrollOrResize() {
    if (!ticking) {
      requestAnimationFrame(() => {
        updatePositions();
        ticking = false;
      });
      ticking = true;
    }
  }

  window.addEventListener('scroll', onScrollOrResize, { passive: true });
  window.addEventListener('resize', onScrollOrResize, { passive: true });

  // Initial check (handles pre-scrolled pages and back/forward cache)
  updatePositions();
})();
