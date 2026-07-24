# Proposal: Search Bar Above Filter Tabs

## Intent

Visitors browsing the menu page (~45 products, 11 categories) must scroll through all cards or rely solely on category tabs to find a specific item. There is no text search. Adding a search input above the filter tabs lets users type a product name or keyword and instantly narrow results — combining text search AND category filtering for faster discovery.

## Scope

### In Scope
- Search input rendered above the category tab row inside the sticky filter bar
- Client-side text matching against product name and description (both locales)
- AND logic with active category tab (search filters within visible category)
- Clear button (×) to reset search
- No-results empty state when search + filter yields zero cards
- i18n strings for placeholder and no-results message (ES/EN)
- Accessible markup: `<input type="search">`, associated `<label>`, `aria-live` region

### Out of Scope
- Server-side / API search endpoint
- Fuzzy matching, typo tolerance, or stemming
- Pagination or virtual scrolling
- Search highlighting in cards
- URL state (no query param sync)

## Capabilities

### New Capabilities
- `menu-search`: Client-side product text search integrated with the existing category filter bar

### Modified Capabilities
- `product-catalog`: PC-002 menu filter gains a search input; PC-005 mobile layout must accommodate search row in sticky bar

## Approach

Pure client-side, progressive enhancement. The search input is added to the DOM inside `.filter-bar__inner` above the tab row. On each `input` event, `menu-filter.js` iterates visible `[data-category]` groups and hides individual product cards whose `data-search-text` attribute (pre-rendered with lowercased name + description) does not include the query. Category tab filtering continues to work — search operates within the currently visible group(s). A `data-search-text` attribute is added server-side to each product card to avoid JS DOM scraping.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/frontend/templates/pages/menu.php` | Modified | Add search input + label above tab row inside `.filter-bar` |
| `src/frontend/templates/partials/product-card.php` | Modified | Add `data-search-text` attribute with lowercased name + description |
| `public/css/components/filter-bar.css` | Modified | Search input styles, two-row layout, mobile responsive adjustments |
| `public/js/menu-filter.js` | Modified | `input` event listener, text matching logic, no-results toggle |
| `src/shared/i18n/es.php` / `en.php` | Modified | Add `menu.search.placeholder`, `menu.search.no_results` keys |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Sticky bar grows taller, reducing viewport on mobile | Medium | Compact single-row layout: search input and tabs share the same row on ≥640px; stack vertically only below 640px |
| Search matches too broadly (common words) | Low | Match against name + description only; min 2 characters before filtering |
| Accessibility: missing label or live region | Low | Use `<label>` with `for`, `aria-live="polite"` on results container, `type="search"` for native clear button |
| `data-search-text` bloats HTML | Low | Attribute is ~50 chars per card × 45 cards ≈ 2.2 KB — negligible |

## Rollback Plan

Revert the single commit. All changes are additive to existing files — no migrations, no schema changes. The filter bar and product cards function identically without the search JS (progressive enhancement). Remove the 5 modified files to restore previous state.

## Dependencies

- None. No backend changes, no new dependencies, no database migrations.

## Success Criteria

- [ ] Search input visible above/beside category tabs on menu page
- [ ] Typing a product name filters cards in real-time (< 16ms per keystroke)
- [ ] Search AND category tab work together (e.g., search "pollo" + "Bocadillos" tab shows only matching bocadillos)
- [ ] Clear button (×) resets search and restores all cards
- [ ] No-results message shown when zero cards match
- [ ] Accessible: screen reader announces result count, input has visible label
- [ ] Layout usable at 360px viewport (search + tabs don't overflow)
- [ ] Works without JS (search input hidden, all products visible via SSR)
