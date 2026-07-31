/**
 * Pit o Cuixa — Accordion & Channel Switcher Module
 */

document.addEventListener('DOMContentLoaded', () => {
  // ── Channel Switcher (Local vs Delivery) ───────────────────────
  const channelBtns = document.querySelectorAll('[data-channel-target]');
  const channelViews = document.querySelectorAll('[data-channel-view]');

  channelBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.channelTarget;

      // Update button states
      channelBtns.forEach((b) => {
        b.classList.remove('channel-switcher__btn--active');
        b.setAttribute('aria-pressed', 'false');
      });
      btn.classList.add('channel-switcher__btn--active');
      btn.setAttribute('aria-pressed', 'true');

      // Update views and filter tabs
      const filterTabs = document.querySelector('[data-filter-tabs]');

      channelViews.forEach((view) => {
        if (view.dataset.channelView === target) {
          view.hidden = false;
        } else {
          view.hidden = true;
        }
      });

      if (filterTabs) {
        // Show category tabs only for Delivery channel, hide for Local
        filterTabs.hidden = (target !== 'delivery');
      }
    });
  });

  // ── Auto-activate Channel from URL parameter (?mode=...) or SessionStorage ──
  const urlParams = new URLSearchParams(window.location.search);
  const modeParam = urlParams.get('mode') || sessionStorage.getItem('pitocuixa_order_mode');

  if (modeParam) {
    const isDelivery = (modeParam === 'delivery' || modeParam === 'domicilio');
    const targetChannel = isDelivery ? 'delivery' : 'dine_in';
    const targetBtn = document.querySelector(`[data-channel-target="${targetChannel}"]`);

    if (targetBtn && !targetBtn.classList.contains('channel-switcher__btn--active')) {
      targetBtn.click();
    }
  }

  // ── Accordion List (Toggle Open / Close — Single Open Mode) ────
  document.addEventListener('click', (e) => {
    const header = e.target.closest('[data-accordion-toggle]');
    if (!header) return;

    const item = header.closest('.accordion-item');
    if (!item) return;

    const content = item.querySelector('.accordion-content');
    if (!content) return;

    const isExpanded = header.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
      header.setAttribute('aria-expanded', 'false');
      content.hidden = true;
      item.classList.remove('accordion-item--open');
    } else {
      // Close all other open accordion items in the channel view / page
      const container = item.closest('[data-channel-view]') || document;
      container.querySelectorAll('.accordion-item--open').forEach((openItem) => {
        if (openItem !== item) {
          const openHeader = openItem.querySelector('[data-accordion-toggle]');
          const openContent = openItem.querySelector('.accordion-content');
          if (openHeader) openHeader.setAttribute('aria-expanded', 'false');
          if (openContent) openContent.hidden = true;
          openItem.classList.remove('accordion-item--open');
        }
      });

      header.setAttribute('aria-expanded', 'true');
      content.hidden = false;
      item.classList.add('accordion-item--open');
    }
  });
});

