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
  let activeCategory     = 'all';  // 'all' | 'popular' | category slug
  let searchQuery        = '';     // lowercased, applied only if length >= 2
  let popularProductIds  = null;   // Set of product ID strings when popular tab is loaded

  // ── Fetch popular products ──────────────────────────────────
  async function loadPopularProducts() {
    try {
      const res = await fetch('/api/products/popular?limit=5');
      const json = await res.json();
      if (!json.error && Array.isArray(json.data)) {
        popularProductIds = new Set(json.data.map((p) => String(p.id)));
      } else {
        popularProductIds = new Set();
      }
    } catch (e) {
      popularProductIds = new Set();
    }
  }

  // ── Apply filters (category AND search AND popular) ──────────
  function applyFilters() {
    let anyVisible = false;
    const blocks = document.querySelectorAll('[data-category]');

    blocks.forEach((block) => {
      // Don't filter hidden channel views
      const channelView = block.closest('[data-channel-view]');
      if (channelView && channelView.hidden) {
        return;
      }

      const isDineIn = channelView && channelView.dataset.channelView === 'dine_in';
      const category = block.getAttribute('data-category');

      let categoryMatch = false;
      if (isDineIn) {
        // Carta en Local (dine_in) is NEVER filtered by category tabs
        categoryMatch = true;
      } else if (searchQuery.length < 2 && activeCategory !== 'popular') {
        // In delivery mode when not searching/popular, keep ALL category blocks in DOM for scrollspy
        categoryMatch = true;
      } else if (activeCategory === 'all') {
        categoryMatch = true;
      } else if (activeCategory === 'popular') {
        categoryMatch = true; // Block matches conditionally based on inner products
      } else {
        categoryMatch = (category === activeCategory);
      }

      if (!categoryMatch) {
        block.style.display = 'none';
        return;
      }

      // Category matches — check items
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

        let popularMatch = true;
        if (!isDineIn && activeCategory === 'popular' && popularProductIds !== null) {
          const pId = item.getAttribute('data-product-id');
          popularMatch = pId ? popularProductIds.has(String(pId)) : false;
        }

        if (searchMatch && popularMatch) {
          item.style.display = '';
          hasVisibleItem = true;
          anyVisible = true;
        } else {
          item.style.display = 'none';
        }
      });

      // Hide block if no inner items matched search / popular filter
      if (!hasVisibleItem) {
        block.style.display = 'none';
      }
    });

    // Toggle no-results announcement
    if (noResults) {
      if (!anyVisible && (searchQuery.length >= 2 || activeCategory === 'popular')) {
        noResults.classList.remove('visually-hidden');
      } else {
        noResults.classList.add('visually-hidden');
      }
    }
  }

  // ── Tab activation ──────────────────────────────────────────
  function setActiveTab(activeTab, scrollTabIntoView = true) {
    tabs.forEach((tab) => {
      tab.classList.remove('filter-bar__tab--active');
      tab.setAttribute('aria-pressed', 'false');
    });

    activeTab.classList.add('filter-bar__tab--active');
    activeTab.setAttribute('aria-pressed', 'true');

    if (scrollTabIntoView && activeTab.scrollIntoView) {
      activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
  }

  // ── ScrollSpy for Delivery Channel View ─────────────────────
  let isProgrammaticScrolling = false;

  function initScrollSpy() {
    const deliveryView = document.querySelector('[data-channel-view="delivery"]');
    if (!deliveryView || typeof IntersectionObserver === 'undefined') return;

    const sections = Array.from(deliveryView.querySelectorAll('.product-group[data-category]'));
    if (sections.length === 0) return;

    const observerOptions = {
      root: null,
      rootMargin: '-110px 0px -65% 0px',
      threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
      if (isProgrammaticScrolling) return;
      if (deliveryView.hidden) return;
      if (searchQuery.length >= 2 || activeCategory === 'popular') return;

      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const categorySlug = entry.target.getAttribute('data-category');
          if (!categorySlug) return;

          const matchingTab = filterBar.querySelector(`[data-filter="${CSS.escape(categorySlug)}"]`);
          if (matchingTab && activeCategory !== categorySlug) {
            activeCategory = categorySlug;
            setActiveTab(matchingTab, true);
          }
        }
      });
    }, observerOptions);

    sections.forEach((section) => observer.observe(section));

    // Scroll fallback to re-activate 'all' when user scrolls back to top
    window.addEventListener('scroll', () => {
      if (isProgrammaticScrolling || deliveryView.hidden || searchQuery.length >= 2 || activeCategory === 'popular') return;
      const firstSection = sections[0];
      if (firstSection) {
        const rect = firstSection.getBoundingClientRect();
        if (rect.top > 160 && activeCategory !== 'all') {
          const allTab = filterBar.querySelector('[data-filter="all"]');
          if (allTab) {
            activeCategory = 'all';
            setActiveTab(allTab, true);
          }
        }
      }
    }, { passive: true });
  }

  initScrollSpy();

  // ── Handle tab click ────────────────────────────────────────
  async function handleTabClick(event) {
    const tab = event.currentTarget;
    const filter = tab.getAttribute('data-filter');

    if (!filter) {
      return;
    }

    setActiveTab(tab, true);
    activeCategory = filter;

    if (activeCategory === 'popular') {
      await loadPopularProducts();
      applyFilters();
    } else {
      applyFilters();
    }

    // Smooth scroll to category section when clicking category tab
    if (filter === 'all') {
      const deliveryView = document.querySelector('[data-channel-view="delivery"]');
      if (deliveryView) {
        isProgrammaticScrolling = true;
        const yOffset = -120;
        const y = deliveryView.getBoundingClientRect().top + window.pageYOffset + yOffset;
        window.scrollTo({ top: y, behavior: 'smooth' });
        setTimeout(() => { isProgrammaticScrolling = false; }, 800);
      }
    } else if (filter !== 'popular') {
      const targetSection = document.querySelector(`[data-channel-view="delivery"] .product-group[data-category="${CSS.escape(filter)}"]`);
      if (targetSection) {
        isProgrammaticScrolling = true;
        const yOffset = -120;
        const y = targetSection.getBoundingClientRect().top + window.pageYOffset + yOffset;
        window.scrollTo({ top: y, behavior: 'smooth' });
        setTimeout(() => { isProgrammaticScrolling = false; }, 800);
      }
    }
  }

  // Attach click handlers to tabs
  tabs.forEach((tab) => {
    tab.addEventListener('click', handleTabClick);
  });


  // ── Click tracking for order CTA links ──────────────────────
  document.addEventListener('click', (event) => {
    const cta = event.target.closest('[data-track-click]');
    if (cta) {
      const productId = cta.getAttribute('data-product-id');
      if (productId) {
        if (navigator.sendBeacon) {
          navigator.sendBeacon('/api/products/' + productId + '/click');
        } else {
          fetch('/api/products/' + productId + '/click', { method: 'POST', keepalive: true }).catch(() => {});
        }
      }
    }
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

  // ── Channel tab switch listener ─────────────────────────────
  document.querySelectorAll('[data-channel-target]').forEach((btn) => {
    btn.addEventListener('click', () => {
      applyFilters();
    });
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

      // Category filters belong to "Para Llevar / Domicilio" (delivery) channel view.
      // Auto-switch to delivery channel view if present.
      const deliveryBtn = document.querySelector('[data-channel-target="delivery"]');
      if (deliveryBtn) {
        deliveryBtn.click();
      } else {
        applyFilters();
      }
    } else {
      // Fallback for unknown slugs (e.g. picapica): reset to 'all' & render all categories
      activeCategory = 'all';
      const allTab = filterBar.querySelector('[data-filter="all"]');
      if (allTab) {
        setActiveTab(allTab);
      }
      applyFilters();
    }
  }
}
