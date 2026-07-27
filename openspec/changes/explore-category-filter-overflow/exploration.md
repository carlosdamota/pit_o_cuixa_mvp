## Exploration: Category Filter Bar Overflow Bug

### Status
**Found: Root cause identified.**

The horizontal scroll on the menu page's category filter bar does not work. Instead of scrolling internally within the bar, the tabs row expands to full content width and causes the entire page to scroll horizontally, breaking the layout.

### Current State

The menu page (`/menu`) has a sticky filter bar at the top with:
1. A search input (full width)
2. A row of category tab buttons (horizontally scrollable on mobile)

The deployed CSS is in `public/css/components/filter-bar.css` (NOT `src/frontend/css/` — these are out of sync).

### Root Cause

**`min-width: max-content` on `.filter-bar__tabs`** (line 83 of `public/css/components/filter-bar.css`).

Here is the full chain of events:

#### HTML Structure
```html
<nav class="filter-bar">
  <div class="filter-bar__inner container">  <!-- container: width:100%, max-width:1280px -->
    <div class="filter-bar__search">...</div>
    <div class="filter-bar__tabs">           <!-- display:flex, overflow-x:auto, min-width:max-content -->
      <button class="filter-bar__tab">All</button>
      <button class="filter-bar__tab">Parrillada</button>
      <button class="filter-bar__tab">Croquetas</button>
      ...
    </div>
  </div>
</nav>
```

#### CSS (deployed version)
```css
.filter-bar__inner {
  display: flex;
  flex-direction: column;  /* search stacked above tabs */
  /* width: 100% from .container — constrained to viewport */
}

.filter-bar__tabs {
  display: flex;
  gap: var(--space-xs);
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  min-width: max-content;  /* ← THE BUG */
}
```

#### Why It Breaks

1. **`.filter-bar__inner`** is a column flex container constrained to viewport width by `.container`'s `width: 100%` and `max-width: 1280px`.

2. **`.filter-bar__tabs`** is a flex item with `min-width: max-content`. This overrides the column flex's default `stretch` behavior — instead of being constrained to the parent's width, the tabs container becomes **exactly as wide as all its buttons combined** (e.g., 600px if there are 8-10 category pills).

3. Since `.filter-bar__tabs` is **exactly** as wide as its content, `overflow-x: auto` on it **never triggers** — there is no overflow within the tabs container itself. The buttons fit perfectly.

4. But `.filter-bar__tabs` (600px) overflows `.filter-bar__inner` (375px on mobile). Neither `.filter-bar__inner` nor `.filter-bar` has `overflow` set to clip it.

5. The overflow propagates up to `<body>`, giving the **entire page** a horizontal scrollbar instead of just the tabs row.

#### Visual Symptom
```
┌──────────────────────┐
│    Search Input      │  ← .filter-bar__inner (375px)
├──────────────────────┤
│ [All] [Pollo] [... } │  ← .filter-bar__tabs (600px, overflows!)
└──────────────────────┘
     ← page-level scrollbar here, not bar-level →
```

### What Should Happen
```
┌──────────────────────┐
│    Search Input      │
├──────────────────────┤← scroll bar here
│ ← [All] [Pollo] [... } →
└──────────────────────┘
```

### Detailed Investigation

#### Files Examined

| File | Relevance |
|------|-----------|
| `public/css/components/filter-bar.css` | **Deployed CSS** — has the bug |
| `src/frontend/css/components/filter-bar.css` | **Source CSS** — different approach, no bug but also NOT deployed |
| `src/frontend/templates/layouts/default.php` | CSS loading order |
| `src/frontend/templates/pages/menu.php` | HTML structure of the filter bar |
| `src/frontend/css/base.css` | `.container` class definition |
| `src/frontend/css/tokens.css` | Design tokens (spacing, etc.) |
| `public/js/menu-filter.js` | JS — does NOT manipulate layout/overflow |
| `openspec/specs/product-catalog/spec.md` | Spec PC-005 (horizontal scroll scoped to tabs row) |
| `openspec/changes/ui-home-improvements/specs/product-catalog/spec.md` | PC-007 (mobile category slider) |

#### Git History

| Commit | What Changed |
|--------|-------------|
| `b310613` — "Implement search bar above filter tabs" | **Moved `overflow-x: auto` from `.filter-bar` to `.filter-bar__tabs`**; changed layout from row to column; **added `min-width: max-content`** (the bug) |
| `b7c418c` — "FAQ page, mobile nav redesign, category slider" | Added `scroll-snap` behavior for mobile slider |
| `ea17d71` — "client-side search bar above filter tabs" | First iteration of search-above-tabs layout |

**Note**: `src/frontend/css/components/filter-bar.css` was last touched in the initial MVP commit (`242ab02`) and has NOT been updated to match the deployed version. There is source-of-truth drift.

### Approaches

1. **Remove `min-width: max-content` from `.filter-bar__tabs`**
   - **Pros**: One-line fix. The tabs container will be constrained by the column flex parent (`stretch`), the buttons (with `flex-shrink: 0`) will overflow it, and `overflow-x: auto` will correctly create the scroll container. Spec-compliant (PC-005: horizontal scroll scoped to tabs row).
   - **Cons**: None identified.
   - **Effort**: Low

2. **Move `overflow-x: auto` back to `.filter-bar` and revert to row layout**
   - **Pros**: Alternative working approach (same as source version).
   - **Cons**: Reverts the search-above-tabs layout (search would be inline with tabs). Contravenes spec PC-005 which explicitly requires search full-width above tabs. Higher effort.
   - **Effort**: Medium

3. **Add `overflow-x: auto` to `.filter-bar` or `.filter-bar__inner`**
   - **Pros**: Could also clip the overflow.
   - **Cons**: `.filter-bar__inner` needs to NOT have `.container`'s padding causing double-clipping. And `overflow-x: auto` on `.filter-bar` would scroll the ENTIRE bar (search + tabs), violating PC-005 ("search bar MUST NOT participate in horizontal scrolling"). Not spec-compliant.
   - **Effort**: Low

### Recommendation

**Approach 1** — Remove `min-width: max-content` from `.filter-bar__tabs`.

This is the simplest, most correct fix. Without `min-width: max-content`, the tabs container in a column flex layout will be constrained to its parent's width. The tab buttons (`flex-shrink: 0`) will overflow the container, and `overflow-x: auto` will correctly trigger an internal scrollbar for the tabs row only.

The scrollbar is already visually hidden (via `scrollbar-width: none` and `::-webkit-scrollbar { display: none }`), so the behavior will be a swipe-to-scroll slider — matching the PC-007 spec for a "horizontally scrollable row of pills" with `scroll-snap-type: x mandatory`.

After fixing, also sync `src/frontend/css/components/filter-bar.css` with `public/css/components/filter-bar.css` to resolve the source-of-truth drift, or establish a build pipeline.

### Risks

- **Low**: Removing `min-width: max-content` is safe — it was mistakenly added as part of the overflow migration. It doesn't serve any other purpose.
- **Medium**: The source file (`src/frontend/css/`) and deployed file (`public/css/`) are out of sync. If the next developer edits the source expecting it to be the source of truth, changes will be lost. Recommend syncing them or adding a copy script.
- **None**: No JS changes needed — all data attributes and filter logic will continue working.

### Ready for Proposal

**Yes.** The root cause is clear and the fix is a one-line CSS change. The next step should be `sdd-propose` → `sdd-spec` → `sdd-design` → `sdd-tasks` → `sdd-apply` → `sdd-verify`.
