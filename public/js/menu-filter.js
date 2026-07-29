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
  const searchInput = document.querySelector('[data-menu-search]');
  const noResults = document.getElementById('search-no-results');

  if (!filterBar) {
    return; // Not on the menu page — skip
  }

  const tabs = filterBar.querySelectorAll('[data-filter]');

  if (tabs.length === 0) {
    return;
  }

  // ── Filter state ────────────────────────────────────────────
  let activeCategory = 'all';  // 'all' | category slug
  let searchQuery    = '';     // lowercased, applied only if length >= 2

  // ── Apply filters (category AND search) ─────────────────────
  function applyFilters() {
    let anyVisible = false;
    const blocks = document.querySelectorAll('[data-category]');

    blocks.forEach((block) => {
      // Don't filter hidden channel views
      const channelView = block.closest('[data-channel-view]');
      if (channelView && channelView.hidden) {
        return;
      }

      const category = block.getAttribute('data-category');
      const categoryMatch = activeCategory === 'all' || category === activeCategory;

      if (!categoryMatch) {
        block.style.display = 'none';
        return;
      }

      // Category matches — show block
      block.style.display = '';

      const items = block.querySelectorAll('.product-card, .listview-item, .accordion-item--featured');
      if (items.length === 0) {
        anyVisible = true;
        return;
      }

      let hasVisibleItem = false;

      items.forEach((item) => {
        const searchText = item.getAttribute('data-search-text') || item.textContent.toLowerCase();
        const searchMatch = searchQuery.length < 2 || searchText.includes(searchQuery);

        if (searchMatch) {
          item.style.display = '';
          hasVisibleItem = true;
          anyVisible = true;
        } else {
          item.style.display = 'none';
        }
      });

      // Hide block if no inner items matched search
      if (!hasVisibleItem && searchQuery.length >= 2) {
        block.style.display = 'none';
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

  // ── Preselect category from URL (?cat=slug) ─────────────────
  // Landing buttons link here with a preselected filter.
  // Unknown slugs fall back silently to 'all'.
  const catParam = new URLSearchParams(window.location.search).get('cat');

  if (catParam && catParam !== 'all') {
    const target = filterBar.querySelector(`[data-filter="${CSS.escape(catParam)}"]`);

    if (target) {
      setActiveTab(target);
      activeCategory = catParam;
      applyFilters();
    }
  }
}
