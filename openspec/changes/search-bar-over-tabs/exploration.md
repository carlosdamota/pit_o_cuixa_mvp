## Exploration: Search Bar Above Filter Tabs

### Current State

The menu page (`GET /menu`) renders a **sticky filter bar** with category tabs and product groups below. The filter bar is a `<nav class="filter-bar" data-filter-bar>` containing `<button class="filter-bar__tab">` elements — one "All" button plus one per category.

**Files involved today:**
- `src/frontend/templates/pages/menu.php` — SSR template: filter bar nav + product group sections
- `public/js/menu-filter.js` — ESM module: category tab show/hide via `style.display`
- `public/css/components/filter-bar.css` — BEM styles: sticky, horizontal scroll, pill-shaped tabs
- `src/backend/pages/menu.php` — page controller: fetches categories + products, builds grouped data
- `src/backend/api/menu.php` — JSON API (GET /api/menu): identical grouping logic
- `src/backend/db/repositories/product.php` — `all()` fetches all active products from SQLite

**Filter mechanism:** Purely client-side. The JS hides/shows entire `<section class="product-group" data-category="{slug}">` elements. On initial SSR, ALL products are visible. Tabs toggle category visibility via `display: none`.

**Data model (products table):**
- `name_es`, `name_en` — bilingual names
- `description_es`, `description_en` — bilingual descriptions
- `price`, `image_url`, `last_shop_url`, `slug`
- Categories are in a separate table, joined via `category_id`
- ~45 products across 11 categories

**No existing search functionality exists anywhere in the project.**

### Affected Areas

| File | Why affected |
|------|-------------|
| `src/frontend/templates/pages/menu.php` | Insert search `<input>` inside `filter-bar__inner` div, above the tab buttons |
| `public/css/components/filter-bar.css` | Add styles for `.filter-bar__search` — input sizing, icon, focus state |
| `public/js/menu-filter.js` | Add search event listener, text-matching logic that works alongside category tabs |
| `src/shared/i18n/es.php` | Add `menu.search.placeholder` translation |
| `src/shared/i18n/en.php` | Add `menu.search.placeholder` translation |

### Approaches

#### 1. Client-side text search on existing DOM (recommended)

**Description:** Add a search `<input>` in the filter bar HTML. JS filters individual product cards (`[data-product-slug]`) by matching `name` and `description` text against the query. Works together with category tabs — tabs filter *groups*, then search filters *cards within visible groups*.

**Pros:**
- Zero backend changes — works with existing SSR + API
- Instant feedback (no network request)
- Survives both SSR initial load and API refresh
- Combines naturally with existing category tabs (AND logic: category + search)
- ~45 items = negligible performance cost for DOM filtering

**Cons:**
- Doesn't search server-side — if product count grows >500, consider server-side
- Limited to text already rendered in the DOM (names + descriptions)

**Effort:** Low

**Implementation outline:**
1. HTML: Add `<input type="search" class="filter-bar__search" data-search-bar>` before the tabs inside `filter-bar__inner`
2. CSS: Style as a full-width input on mobile, compact on desktop; use design tokens
3. JS: Listen to `input` event on `[data-search-bar]`, iterate `.product-card` elements, hide/show based on text match against `.product-card__title` and `.product-card__desc`
4. i18n: `menu.search.placeholder` → "Buscar en la carta..." / "Search the menu..."

#### 2. Server-side search via API

**Description:** Send search query to `GET /api/menu?q=...`, backend adds `WHERE` clause with `LIKE` on name/description fields, returns filtered grouped data.

**Pros:**
- Scales to thousands of products
- Can search fields not rendered in DOM (e.g. raw bilingual data)
- Works with pagination if needed

**Cons:**
- Requires backend + API changes (product repo, menu API, menu page controller)
- Network latency per keystroke (needs debounce)
- Breaks the current SSR-only model (initial page load shows everything)
- Overkill for ~45 products
- More complex to combine with category tabs

**Effort:** Medium

#### 3. Hybrid: client-side search on SSR, server-side for initial query param

**Description:** Same as approach #1, but also accept `?q=` query param on the menu page to pre-filter on initial SSR load. Backend does a basic WHERE LIKE, frontend takes over after.

**Pros:**
- Enables shareable/deep-linkable search URLs
- Progressive enhancement: JS-off users get server-side search

**Cons:**
- Two implementations (backend + frontend)
- Duplicated logic
- Adds complexity to page controller and API

**Effort:** Medium-High

### Recommendation

**Approach 1: Client-side text search** — it's the right fit for the current scale (~45 products), aligns with the existing client-side filter pattern, requires zero backend changes, and can be implemented cleanly in a single session.

The search bar goes INSIDE the `<nav class="filter-bar">` but ABOVE the tab buttons — physically between `filter-bar__inner` and its current children. This keeps it in the sticky scroll area.

Key design decisions:
- Search applies AND with category tabs: visible cards must pass BOTH filters
- Use `input` event with empty-string reset (no debounce needed for 45 items)
- Match against `.product-card__title` and `.product-card__desc` text content
- Clear search when switching categories? **No** — let both filters coexist

### Risks

1. **Accessibility**: Search input must have a label (visible or `sr-only`), proper `type="search"`, and clear button semantics. Must not break existing tab keyboard navigation.
2. **Mobile layout**: The filter bar is already horizontally scrollable on mobile. A full-width search bar above the tabs could push tabs below the fold. Consider collapsing the search or making it compact on small screens.
3. **CSS specificity**: The search input needs styles that override `base.css` defaults for `input`. Must be intentional, not accidental.
4. **Sticky bar height**: Adding an input increases the sticky bar height, taking more vertical space. Consider whether the search bar stays sticky or scrolls with content.
5. **No results state**: When search returns zero matches in a visible category, the empty category section should show a fallback message (already exists: `menu-products__empty`).

### Ready for Proposal

**Yes.** The exploration is complete. The orchestrator should tell the user that search is feasible as a purely client-side addition, requires changes to 5 files, and can be implemented in one session. Recommend Approach 1 (client-side DOM text search) as the simplest path with maximum impact.
