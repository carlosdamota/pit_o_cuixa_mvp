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

      // Update views
      channelViews.forEach((view) => {
        if (view.dataset.channelView === target) {
          view.hidden = false;
        } else {
          view.hidden = true;
        }
      });
    });
  });

  // ── Accordion List (Toggle Open / Close) ───────────────────────
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
      header.setAttribute('aria-expanded', 'true');
      content.hidden = false;
      item.classList.add('accordion-item--open');
    }
  });
});
