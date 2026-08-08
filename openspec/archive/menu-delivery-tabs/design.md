# Design: Menu Delivery Tab Reorder

## Technical Approach

**Template-first strategy**: Modify the PHP template to establish the correct HTML structure (popular first, first category active, no "all" tab), then update JavaScript to read the first category slug from the DOM and use it as the default state. This ensures the server-rendered HTML is correct even before JS loads (progressive enhancement).

The first category slug is determined dynamically by reading the `data-filter` attribute of the first category tab in the DOM (not hardcoded from PHP). This keeps JS decoupled from server-side category ordering and makes the code resilient to future changes.

## Architecture Decisions

### Decision: How to determine the first category slug

**Choice**: Read from DOM — `filterBar.querySelector('[data-filter]:not([data-filter="popular"])')` after excluding the "popular" tab.

**Alternatives considered**:
- Pass from PHP via inline script: tighter coupling, requires template changes to emit JS globals.
- Hardcode slug in JS: breaks when category order changes in DB.

**Rationale**: DOM-reading is framework-agnostic, survives reordering, and aligns with the existing pattern where JS reads tab state from `data-filter` attributes.

### Decision: ScrollSpy fallback behavior

**Choice**: When user scrolls above all sections, re-activate the first category tab (not "all"). Remove the scroll-to-top "all" activation logic entirely.

**Alternatives considered**:
- Keep scroll-to-top but activate "popular": semantically wrong, popular is not a spatial section.
- Do nothing on scroll-to-top: leaves user with a stale active tab.

**Rationale**: First category is the new "ground state". ScrollSpy should never activate a state that doesn't correspond to a visible section.

### Decision: `?cat=all` legacy URL handling

**Choice**: Treat `?cat=all` identically to unknown slugs — fall back to first category silently (no redirect, no error).

**Alternatives considered**:
- HTTP redirect to `/menu?cat=<first-slug>`: requires server-side knowledge of first slug, adds latency.
- Show error: bad UX for a legacy bookmark.

**Rationale**: Silent fallback is seamless. Old home CTAs linking `?cat=all` land on the first category, which is the intended behavior.

## Data Flow

```
PHP Template (menu.php)
  │
  ├── Renders tabs: [popular] [cat-1 (active)] [cat-2] [cat-3] ...
  ├── First category tab has: class="filter-bar__tab--active" + aria-pressed="true"
  │
  └──→ JS (menuFilter.js)
         │
         ├── On init: reads firstCategorySlug from first non-popular tab's data-filter
         ├── Sets activeCategory = firstCategorySlug
         ├── applyFilters() shows only first category products
         ├── ScrollSpy: on scroll-to-top, re-activates first category tab
         └── URL param: ?cat=all or unknown → activeCategory = firstCategorySlug
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/frontend/templates/pages/menu.php` | Modify | Lines 81-104: Remove "all" button, move "popular" before category loop, add active class + aria-pressed to first `$catList` item |
| `public/js/menuFilter.js` | Modify | Lines 39, 77-86, 191-205, 230-238, 322-348: Replace all `'all'` references with first category slug logic |

### Detailed Changes — `menu.php` (lines 81-104)

**Current structure** (lines 81-104):
```
[all (active)] [popular] [cat-1] [cat-2] ...
```

**New structure**:
```
[popular] [cat-1 (active)] [cat-2] ...
```

Specific edits:
- **Delete lines 82-87**: Remove the `data-filter="all"` button entirely.
- **Keep lines 89-94**: "popular" button stays but moves to first position (it's already before the loop, just needs to become first child).
- **Modify lines 96-103**: In the `$catList` foreach loop, add conditional active class + `aria-pressed="true"` when `$catList` index is 0.

Implementation:
```php
<?php foreach ($catList as $index => $cat):
    $isActive = ($index === 0 && $catList !== []);
?>
    <button class="filter-bar__tab<?= $isActive ? ' filter-bar__tab--active' : '' ?>"
            data-filter="<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?>"
            type="button"
            aria-pressed="<?= $isActive ? 'true' : 'false' ?>">
        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
    </button>
<?php endforeach; ?>
```

### Detailed Changes — `menuFilter.js`

**Line 39** — Replace `let activeCategory = 'all'` with:
```js
const firstCategoryTab = filterBar.querySelector('[data-filter]:not([data-filter="popular"])');
let activeCategory = firstCategoryTab ? firstCategoryTab.getAttribute('data-filter') : '';
```

**Lines 77-86** (`applyFilters`) — Replace the `activeCategory === 'all'` branch:
```js
} else if (activeCategory === '') {
  categoryMatch = true; // Edge case: no categories exist, show all blocks
} else if (activeCategory === 'popular') {
  categoryMatch = true; // Block matches conditionally based on inner products
} else {
  categoryMatch = (category === activeCategory);
}
```

**Lines 191-205** (Scroll fallback) — Replace the scroll-to-top "all" activation with first category re-activation:
```js
window.addEventListener('scroll', () => {
  if (isProgrammaticScrolling || deliveryView.hidden || searchQuery.length >= 2 || activeCategory === 'popular') return;
  const firstSection = sections[0];
  if (firstSection) {
    const rect = firstSection.getBoundingClientRect();
    if (rect.top > 160 && activeCategory !== firstCategorySlug) {
      activeCategory = firstCategorySlug;
      setActiveTab(firstCategoryTab, true);
    }
  }
}, { passive: true });
```

Where `firstCategorySlug` and `firstCategoryTab` are captured at init time (line 39 area).

**Lines 230-238** (Click handler for "all" scroll) — Delete this entire block. The "all" tab no longer exists. The remaining logic (lines 239-248) handles category tab clicks correctly.

**Lines 322-348** (URL param handling) — Replace the unknown slug fallback:
```js
if (catParam && catParam !== 'popular') {
  const target = filterBar.querySelector(`[data-filter="${CSS.escape(catParam)}"]`);

  if (target) {
    setActiveTab(target);
    activeCategory = catParam;
    // ... auto-switch to delivery channel ...
  } else {
    // Unknown slug or legacy ?cat=all → fall back to first category
    activeCategory = firstCategorySlug;
    if (firstCategoryTab) {
      setActiveTab(firstCategoryTab);
    }
    applyFilters();
  }
}
```

## Interfaces / Contracts

No new interfaces. The existing DOM contract (`data-filter`, `data-category`, `aria-pressed`) remains unchanged. The only contract change is semantic: `data-filter="all"` no longer exists in the DOM.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Manual | Tab order on page load | Open `/menu` in delivery mode, verify "Mas vendidos" is first (unselected), first category is second (selected) |
| Manual | ScrollSpy behavior | Scroll through categories, verify tabs highlight correctly; scroll to top, verify first category re-activates |
| Manual | URL param `?cat=valid-slug` | Navigate to `/menu?cat=<existing-slug>`, verify correct tab selected |
| Manual | URL param `?cat=all` | Navigate to `/menu?cat=all`, verify first category selected (no error) |
| Manual | URL param `?cat=unknown` | Navigate to `/menu?cat=nonexistent`, verify first category selected (no error) |
| Manual | Empty `$catList` edge case | Temporarily set `$catList = []` in PHP, verify no JS errors, no active tab |
| Manual | Dine-in channel unaffected | Switch to dine-in, verify accordion behavior unchanged |
| Console | No JS errors | Check browser console for errors on all above scenarios |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No migration required. This is a pure frontend change with no database or config impact. Rollout is instantaneous on deploy.

## Open Questions

None.
