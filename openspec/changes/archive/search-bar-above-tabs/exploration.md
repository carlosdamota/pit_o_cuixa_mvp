## Exploration: Search Bar Above Filter Tabs (Full Width)

### Current State

The search bar and filter tabs are both rendered **inside the same container** on the **menu page only** (`/menu` route → `src/frontend/templates/pages/menu.php`).

**Current DOM structure:**

```html
<nav class="filter-bar" data-filter-bar>
  <div class="filter-bar__inner container">
    <div class="filter-bar__search">          ← Search (first child)
      <input type="search" id="menu-search" ...>
    </div>
    <div class="filter-bar__tabs">            ← Filter tabs (second child)
      <button data-filter="all">All</button>
      <button data-filter="pollos">Pollos</button>
      ...
    </div>
  </div>
</nav>
```

**CSS layout:**

- `.filter-bar`: `position: sticky; top: 0; z-index: var(--z-header); overflow-x: auto` — same z-index as the header
- `.filter-bar__inner`: `display: flex; gap: var(--space-xs); min-width: max-content` — both search and tabs in a horizontal flex row
- `.filter-bar__tab`: `flex-shrink: 0; white-space: nowrap` — prevents tabs from wrapping, enables horizontal scroll on mobile
- At `640px+`: `.filter-bar__inner` adds `justify-content: center` for centered tabs

**On mobile**, the entire bar scrolls horizontally (search input + tabs together) because of `overflow-x: auto` on `.filter-bar` and `min-width: max-content` on `.filter-bar__inner`.

### What Needs to Change

The search bar needs to:
1. **Move above** the filter tabs (not beside them)
2. **Span full width** (not share a row with tabs)

### Files Involved

| File | What Needs to Change |
|------|---------------------|
| `src/frontend/templates/pages/menu.php` | HTML structure — likely need to separate search and tabs into distinct containers |
| `src/frontend/css/components/filter-bar.css` | CSS layout — change from `display: flex` on a single row to two stacked rows |

**NOT affected:**
- `public/js/menu-filter.js` — JS uses `querySelector` by data attributes (`[data-menu-search]`, `[data-filter]`), NOT by DOM position. No changes needed.
- `public/js/main.js` — only imports `initMenuFilter`, no layout logic.
- `src/frontend/css/layouts/header.css` — header has its own sticky behavior, no conflict.
- `src/frontend/templates/pages/home.php` — no search/filter on the landing page.
- Other pages — search/filter only exists on `/menu`.

### Technical Approaches

1. **CSS Grid on filter-bar__inner** (recommended — minimal changes)
   - Change `.filter-bar__inner` from `display: flex` to `display: grid; grid-template-columns: 1fr`
   - `.filter-bar__search` stays first, naturally row 1
   - `.filter-bar__tabs` becomes row 2
   - Move `min-width: max-content` and horizontal overflow from `filter-bar__inner` to a new or existing `.filter-bar__tabs` wrapper
   - **Pros**: No HTML changes needed, smallest diff, clean semantics
   - **Cons**: Need to explicitly scope the horizontal scroll behavior to the tabs row only (not the search bar)
   - **Effort**: Low

2. **Restructure DOM + flex column**
   - Move `.filter-bar__search` out of the flex row into its own container
   - Keep `.filter-bar__tabs` in a scrollable flex row below
   - Example: wrap search in a separate div, tabs in another
   - **Pros**: Clean DOM, easy to reason about responsive behavior
   - **Cons**: Requires HTML change in `menu.php`, slightly larger diff
   - **Effort**: Low

3. **Flexbox column + full-width search**
   - Set `.filter-bar__inner { flex-direction: column; }`
   - Make `.filter-bar__search { width: 100%; }`
   - **Cons**: The `min-width: max-content` on `filter-bar__inner` forces the entire container to expand, not just the tabs row. The horizontal scroll on `.filter-bar` would also apply to the search. Not recommended without restructuring the scroll behavior.
   - **Effort**: Medium (needs scroll behavior workaround)

### Recommendation

**Approach 1 (CSS Grid).** It requires NO changes to the PHP template — only CSS changes in `filter-bar.css`. The key changes are:

1. Change `.filter-bar__inner` from flex to grid: `display: grid; grid-template-columns: 1fr`
2. Remove `min-width: max-content` from `.filter-bar__inner` and apply horizontal scroll behavior to `.filter-bar__tabs` instead (e.g., `display: flex; overflow-x: auto; min-width: max-content`)
3. Adjust gap/padding: the current `var(--space-xs)` gap works but consider `var(--space-sm)` for visual separation between the search row and tabs row
4. The tablet `justify-content: center` rule currently targets `.filter-bar__inner` — needs to move to `.filter-bar__tabs` instead

### Risks

1. **Horizontal scroll on mobile**: Currently the ENTIRE bar (search + tabs) scrolls horizontally. After the change, only the tabs row should scroll. If `overflow-x: auto` stays on `.filter-bar` and the search is full-width, the search bar could also trigger scrolling. **Must scope overflow to the tabs container only.**

2. **Sticky stacking**: Both `.header` and `.filter-bar` use `position: sticky; top: 0; z-index: var(--z-header)`. The filter bar appears directly below the header in the DOM. After the change, the taller filter bar (now 2 rows) will still be below the header. No conflict, but the increased height on mobile may push the product grid down further.

3. **Responsive breakpoints**: At `640px+`, tabs are centered via `justify-content: center`. This rule must move to `.filter-bar__tabs` to avoid centering the search bar.

4. **No JS breakage**: The JavaScript uses `querySelector('[data-menu-search]')` and `filterBar.querySelectorAll('[data-filter]')` — both by attribute selectors, completely independent of DOM order or parent structure. **Zero JS risk.**

5. **filter-bar__inner's container padding**: The `.container` class adds `padding: 0 var(--space-md)`. This is fine for both rows.

### Ready for Proposal

Yes. The CSS-only approach (CSS Grid on `filter-bar__inner` + scope scroll behavior to tabs) is low effort, zero JS impact, and addresses the request cleanly.

The orchestrator should proceed with **sdd-propose** for `search-bar-above-tabs`.
