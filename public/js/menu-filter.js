/**
 * Pit o Cuixa — Menu Category Filter
 *
 * ESM module: category filter show/hide with "All" reset.
 * Progressive enhancement: server HTML renders without JS.
 *
 * Data attributes:
 *   [data-filter-bar]       — container with category tab buttons
 *   [data-filter]           — category slug on each button ('all' for reset)
 *   [data-menu-products]    — container holding all product-group sections
 *   [data-category]         — category slug on each product-group section
 *
 * @module menu-filter
 */

/**
 * Initialise the menu category filter.
 * Call after DOM is ready.
 */
export function initMenuFilter() {
  const filterBar = document.querySelector('[data-filter-bar]');
  const productsContainer = document.querySelector('[data-menu-products]');

  if (!filterBar || !productsContainer) {
    return; // Not on the menu page — skip
  }

  const tabs = filterBar.querySelectorAll('[data-filter]');
  const groups = productsContainer.querySelectorAll('[data-category]');

  if (tabs.length === 0 || groups.length === 0) {
    return;
  }

  /**
   * Show all product groups (reset filter).
   */
  function showAll() {
    groups.forEach((group) => {
      group.style.display = '';
    });
  }

  /**
   * Show only groups matching a specific category slug.
   *
   * @param {string} slug  Category slug to filter by
   */
  function filterByCategory(slug) {
    groups.forEach((group) => {
      const category = group.getAttribute('data-category');

      if (category === slug) {
        group.style.display = '';
      } else {
        group.style.display = 'none';
      }
    });
  }

  /**
   * Activate a tab and deactivate others.
   *
   * @param {HTMLElement} activeTab  The tab button to activate
   */
  function setActiveTab(activeTab) {
    tabs.forEach((tab) => {
      tab.classList.remove('filter-bar__tab--active');
      tab.setAttribute('aria-pressed', 'false');
    });

    activeTab.classList.add('filter-bar__tab--active');
    activeTab.setAttribute('aria-pressed', 'true');
  }

  /**
   * Handle tab click.
   *
   * @param {MouseEvent} event
   */
  function handleTabClick(event) {
    const tab = event.currentTarget;
    const filter = tab.getAttribute('data-filter');

    if (!filter) {
      return;
    }

    setActiveTab(tab);

    if (filter === 'all') {
      showAll();
    } else {
      filterByCategory(filter);
    }
  }

  // Attach click handlers
  tabs.forEach((tab) => {
    tab.addEventListener('click', handleTabClick);
  });

  // ── Keyboard navigation ────────────────────────────────────
  filterBar.addEventListener('keydown', (event) => {
    const current = document.activeElement;
    if (!current || !current.hasAttribute('data-filter')) {
      return;
    }

    const tabArray = Array.from(tabs);
    const index = tabArray.indexOf(current);

    if (index === -1) {
      return;
    }

    let nextIndex;

    switch (event.key) {
      case 'ArrowRight':
        event.preventDefault();
        nextIndex = (index + 1) % tabArray.length;
        break;
      case 'ArrowLeft':
        event.preventDefault();
        nextIndex = (index - 1 + tabArray.length) % tabArray.length;
        break;
      default:
        return;
    }

    tabArray[nextIndex].focus();
  });
}
