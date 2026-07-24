/**
 * Pit o Cuixa — Menu Category Filter + Search
 *
 * ESM module: unified filter (category + search text) with "All" reset.
 * Progressive enhancement: server HTML renders without JS.
 *
 * Data attributes:
 *   [data-filter-bar]       — container with category tab buttons + search
 *   [data-filter]           — category slug on each tab button ('all' for reset)
 *   [data-menu-search]      — search input
 *   [data-menu-products]    — container holding all product-group sections
 *   [data-category]         — category slug on each product-group section
 *   [data-product-slug]     — product slug on each card
 *   [data-search-text]      — lowercased bilingual search corpus on each card
 *
 * @module menu-filter
 */

/**
 * Initialise the menu filter and search.
 * Call after DOM is ready.
 */
export function initMenuFilter() {
  const filterBar = document.querySelector('[data-filter-bar]');
  const productsContainer = document.querySelector('[data-menu-products]');
  const searchInput = document.querySelector('[data-menu-search]');
  const noResults = document.getElementById('search-no-results');

  if (!filterBar || !productsContainer) {
    return; // Not on the menu page — skip
  }

  const tabs = filterBar.querySelectorAll('[data-filter]');
  const groups = productsContainer.querySelectorAll('[data-category]');

  if (tabs.length === 0 || groups.length === 0) {
    return;
  }

  // ── Filter state ────────────────────────────────────────────
  let activeCategory = 'all';  // 'all' | category slug
  let searchQuery    = '';     // lowercased, applied only if length >= 2

  // ── Apply filters (category AND search) ─────────────────────
  function applyFilters() {
    let anyVisible = false;

    groups.forEach((group) => {
      const category = group.getAttribute('data-category');
      const categoryMatch = activeCategory === 'all' || category === activeCategory;

      if (!categoryMatch) {
        group.style.display = 'none';
        return;
      }

      // Category matches — show group (may be hidden by search below)
      group.style.display = '';

      const cards = group.querySelectorAll('.product-card');
      let hasVisibleCard = false;

      cards.forEach((card) => {
        const searchText = card.getAttribute('data-search-text') || '';
        const searchMatch = searchQuery.length < 2 || searchText.includes(searchQuery);

        if (searchMatch) {
          card.style.display = '';
          hasVisibleCard = true;
          anyVisible = true;
        } else {
          card.style.display = 'none';
        }
      });

      // Hide group if no cards survived the search
      if (!hasVisibleCard) {
        group.style.display = 'none';
      }
    });

    // Toggle no-results announcement
    if (noResults) {
      if (!anyVisible && searchQuery.length >= 2) {
        noResults.classList.remove('visually-hidden');
      } else {
        noResults.classList.add('visually-hidden');
      }
    }
  }

  // ── Tab activation ──────────────────────────────────────────
  function setActiveTab(activeTab) {
    tabs.forEach((tab) => {
      tab.classList.remove('filter-bar__tab--active');
      tab.setAttribute('aria-pressed', 'false');
    });

    activeTab.classList.add('filter-bar__tab--active');
    activeTab.setAttribute('aria-pressed', 'true');
  }

  // ── Handle tab click ────────────────────────────────────────
  function handleTabClick(event) {
    const tab = event.currentTarget;
    const filter = tab.getAttribute('data-filter');

    if (!filter) {
      return;
    }

    setActiveTab(tab);
    activeCategory = filter;
    applyFilters();
  }

  // Attach click handlers to tabs
  tabs.forEach((tab) => {
    tab.addEventListener('click', handleTabClick);
  });

  // ── Handle search input ─────────────────────────────────────
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      searchQuery = searchInput.value.toLowerCase().trim();
      applyFilters();
    });
  }

  // ── Keyboard navigation (tabs only) ─────────────────────────
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
