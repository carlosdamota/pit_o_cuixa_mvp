# Design: Search Bar Above Filter Tabs

## Technical Approach

Client-side progressive enhancement layered on the existing category filter. A central `applyFilters()` function in `menu-filter.js` reconciles two independent state variables — `activeCategory` ("all" | slug) and `searchQuery` (string) — into a single visibility pass over `product-group` sections and their `product-card` children. The search corpus is baked server-side into a `data-search-text` attribute (lowercased name + description of **both** locales) so the JS never scrapes the DOM. No backend, no API, no debounce (≈45 cards). Maps to `menu-search` spec + PC-002/PC-005 deltas.

## Architecture Decisions

### Decision: Centralised filter state in `applyFilters()`

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Refactor to single `applyFilters(state)` | Touches existing tab logic | **Chosen** |
| Separate search fn over visible cards | Two code paths drift out of sync | Rejected |

**Rationale**: Existing `showAll()`/`filterByCategory()` only know category. Search must AND with category; a single recompute on every tab click **and** input event guarantees the two filters never disagree. State is held in closure variables inside `initMenuFilter()`, matching the module's existing ESM-closure pattern.

### Decision: `data-search-text` baked server-side, both locales

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Output `strtolower(name_es name_en desc_es desc_en)` in card partial | +~2.2 KB HTML | **Chosen** |
| JS scrapes `.product-card__title/.desc` at init | Only current locale; re-scrape on locale switch | Rejected |

**Rationale**: Cards render only the active locale's text, but a visitor may type terms in either language. The raw bilingual row (`name_es`, `name_en`, `description_es`, `description_en`) is already passed to the partial, so both locales are concatenated lowercased into one attribute. Searching then reduces to `dataSearchText.includes(query)`.

### Decision: Two-row sticky bar — search row above tabs row

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Search row stacked above tabs row (column on mobile, inline ≥640px) | Sticky bar taller | **Chosen** |
| Search replaces tabs on mobile | Loses category filter on mobile | Rejected |

**Rationale**: Keeps category tabs always available and horizontally scrollable (existing pattern). `.filter-bar__inner` is restructured into `.filter-bar__search` + `.filter-bar__tabs`; mobile stacks vertically, ≥640px places search inline-left with tabs flowing right.

### Decision: Native `type="search"` for clear button

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `<input type="search">` native × clear | Styling varies across UA | **Chosen** |
| Custom JS clear button | More code, more a11y attrs | Rejected |

**Rationale**: Native `type="search"` provides the × button, escape-to-clear, and semantics for free, satisfying the spec's clear-button requirement with zero JS.

## Data Flow

    menu.php (render) ──► product-card.php adds data-search-text (both locales)
                         │
          visitor types ─► input event ─► applyFilters()
          visitor taps ───► tab click ───► applyFilters()
                                            │
                       ┌────────────────────┴────────────────────┐
                       ▼                                         ▼
              per group: category match?              per card: search match?
              hide group if no                       hide card if no
              visible cards                          └─► if 0 cards visible
                                                         globally → no-results
                                                         region (aria-live)

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/frontend/templates/pages/menu.php` | Modify | Restructure `.filter-bar__inner` into `.filter-bar__search` (label + `<input type="search" data-menu-search>`) + `.filter-bar__tabs` (existing buttons). Add `aria-live` no-results `<p>` inside `[data-menu-products]`. |
| `src/frontend/templates/partials/product-card.php` | Modify | Add `data-search-text="<?= htmlspecialchars(strtolower(...), ENT_QUOTES) ?>"` — concatenation of `name_es`, `name_en`, `description_es`, `description_en`. |
| `public/js/menu-filter.js` | Modify | Replace `showAll`/`filterByCategory` with `applyFilters()` driven by `activeCategory` + `searchQuery`. Add `input` listener (≥2 chars), min-length gate, per-card `includes` check, hide empty groups, toggle no-results node, keep existing keyboard nav. |
| `public/css/components/filter-bar.css` | Modify | `.filter-bar__search` + `.filter-bar__tabs` layout: column <640px, row ≥640px; search input styles using design tokens; `.visually-hidden` for label. |
| `src/shared/i18n/es.php` / `en.php` | Modify | Add `menu.search.label`, `menu.search.placeholder`, `menu.search.no_results`. |

## Interfaces / Contracts

```php
// product-card.php output (adds one attribute to existing <article>)
<article class="product-card"
         data-product-slug="..."
         data-search-text="pollo a l'ast pit chicken brocheta ...">
```

```js
// menu-filter.js closure state
let activeCategory = 'all';   // 'all' | <slug>
let searchQuery    = '';      // lowercased, applied only if length >= 2
function applyFilters() { /* reads both, sets display none|'' */ }
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | n/a — no runner | — |
| Integration | PHP syntax | `php -l` on modified templates/i18n (config `build_command`) |
| Manual E2E | Search filters by name + desc; AND with category tab; × clears; min 2 chars; no-results; 360px + 1280px layouts; no-JS regression (input present, all cards visible) | Browser checklist against spec scenarios |

## Migration / Rollout

No migration required. Additive to 5 files, single revert commit (see proposal rollback plan).

## Open Questions

- [ ] **i18n locale mismatch (BLOCKING for the i18n task):** `src/shared/i18n/es.php` currently contains **Catalan** strings ("la nostra carta", "Tot"), not Spanish. The spec's placeholder examples are Spanish ("Buscar productos..."). Confirm whether `es.php` should hold Catalan or Spanish strings before adding `menu.search.*` keys — this affects the whole bilingual ES/EN contract, not just this change.
- [ ] Should the no-results `<p>` live inside `[data-menu-products]` (hidden by category filter logic) or as a sibling? Design assumes inside, toggled by `applyFilters()`.

## Next Step

Ready for tasks (sdd-tasks).