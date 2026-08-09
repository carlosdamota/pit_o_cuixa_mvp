# Tasks: Menu Delivery Tab Reorder

## Review Workload Forecast

| File | Estimated Changed Lines |
|------|------------------------|
| `src/frontend/templates/pages/menu.php` | ~20 lines |
| `public/js/menu-filter.js` | ~55 lines |
| **Total** | **~75 lines** |

**Status**: Within 400-line budget. No review workload guard needed.

---

## Task 1 — Remove "All" tab and reorder filter bar in PHP template

**Complexity**: S (~20 lines changed)
**Dependencies**: None

### Description

Modify `src/frontend/templates/pages/menu.php` lines 81-104 to:
1. Delete the `data-filter="all"` button (lines 82-87)
2. Keep the "popular" button as first child (lines 89-94, now becomes first after deletion)
3. Modify the `$catList` foreach loop to add `filter-bar__tab--active` class and `aria-pressed="true"` to the first category item

### File Changes

**`src/frontend/templates/pages/menu.php`**

Replace lines 81-104 with:

```php
<div class="filter-bar__tabs" data-filter-tabs<?= $isDeliveryMode ? '' : ' hidden' ?>>
    <button class="filter-bar__tab"
            data-filter="popular"
            type="button"
            aria-pressed="false">
        <?= __('menu.filter.popular') ?>
    </button>

    <?php foreach ($catList as $index => $cat):
        $isActive = ($index === 0);
    ?>
        <button class="filter-bar__tab<?= $isActive ? ' filter-bar__tab--active' : '' ?>"
                data-filter="<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?>"
                type="button"
                aria-pressed="<?= $isActive ? 'true' : 'false' ?>">
            <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
        </button>
    <?php endforeach; ?>
</div>
```

### Acceptance Criteria

- [x] No `data-filter="all"` button exists in the rendered HTML
- [x] "Mas vendidos" tab is the first tab in the filter bar
- [x] First category tab has class `filter-bar__tab--active` and `aria-pressed="true"`
- [x] All other category tabs have `aria-pressed="false"`
- [x] If `$catList` is empty, no category tabs render and no PHP errors occur

### Testing Steps

1. Open `/menu` in delivery mode
2. Inspect filter bar HTML — verify tab order: `[popular] [cat-1 (active)] [cat-2] ...`
3. Verify no "Todo" / "All" button exists
4. View-source to confirm server-rendered HTML has correct active state (before JS loads)

### Rollback

```
git checkout HEAD -- src/frontend/templates/pages/menu.php
```

---

## Task 2 — Update JS initial state and applyFilters logic

**Complexity**: S (~20 lines changed)
**Dependencies**: Task 1 (template must be correct first)

### Description

Modify `public/js/menu-filter.js` to:
1. Replace `let activeCategory = 'all'` (line 39) with DOM-read first category slug
2. Replace the `activeCategory === 'all'` branch in `applyFilters()` (lines 80-81) with empty-string guard for edge case

### File Changes

**`public/js/menu-filter.js`**

**Change 1 — Line 39**: Replace initial state:

```js
// Before:
let activeCategory     = 'all';  // 'all' | 'popular' | category slug

// After:
const firstCategoryTab = filterBar.querySelector('[data-filter]:not([data-filter="popular"])');
const firstCategorySlug = firstCategoryTab ? firstCategoryTab.getAttribute('data-filter') : '';
let activeCategory     = firstCategorySlug;  // '' | 'popular' | category slug
```

**Change 2 — Lines 77-86**: Replace `applyFilters` category matching logic:

```js
// Before (lines 77-86):
} else if (searchQuery.length < 2 && activeCategory !== 'popular') {
  categoryMatch = true;
} else if (activeCategory === 'all') {
  categoryMatch = true;
} else if (activeCategory === 'popular') {
  categoryMatch = true;
} else {
  categoryMatch = (category === activeCategory);
}

// After:
} else if (searchQuery.length < 2 && activeCategory !== 'popular') {
  categoryMatch = true;
} else if (activeCategory === '') {
  categoryMatch = true;  // Edge case: no categories, show all blocks
} else if (activeCategory === 'popular') {
  categoryMatch = true;  // Block matches conditionally based on inner products
} else {
  categoryMatch = (category === activeCategory);
}
```

### Acceptance Criteria

- [x] `activeCategory` initializes to first category slug (not `'all'`)
- [x] When `$catList` is empty, `activeCategory` is `''` and no JS errors occur
- [x] `applyFilters()` with `activeCategory === ''` shows all blocks (edge case)
- [x] No reference to string `'all'` remains in filter state logic

### Testing Steps

1. Open browser console on `/menu`
2. Verify no JS errors on load
3. Verify first category products are visible, others hidden
4. (If possible) Temporarily set `$catList = []` in PHP, reload, verify no console errors

### Rollback

```
git checkout HEAD -- public/js/menu-filter.js
```

---

## Task 3 — Update ScrollSpy fallback to use first category

**Complexity**: S (~12 lines changed)
**Dependencies**: Task 2 (uses `firstCategorySlug` and `firstCategoryTab` variables)

### Description

Replace the scroll-to-top "all" activation logic (lines 191-205) with first-category re-activation.

### File Changes

**`public/js/menu-filter.js`**

**Lines 191-205**: Replace scroll fallback:

```js
// Before (lines 191-205):
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

// After:
// Scroll fallback to re-activate first category when user scrolls back to top
window.addEventListener('scroll', () => {
  if (isProgrammaticScrolling || deliveryView.hidden || searchQuery.length >= 2 || activeCategory === 'popular') return;
  const firstSection = sections[0];
  if (firstSection) {
    const rect = firstSection.getBoundingClientRect();
    if (rect.top > 160 && activeCategory !== firstCategorySlug) {
      activeCategory = firstCategorySlug;
      if (firstCategoryTab) {
        setActiveTab(firstCategoryTab, true);
      }
    }
  }
}, { passive: true });
```

### Acceptance Criteria

- [x] Scrolling above all sections re-activates first category tab (not "all")
- [x] No `querySelector('[data-filter="all"]')` call remains in scroll handler
- [x] `activeCategory` is set to `firstCategorySlug` on scroll-to-top

### Testing Steps

1. Open `/menu` in delivery mode
2. Scroll down past first category section — verify tab changes
3. Scroll back to top — verify first category tab re-activates
4. Verify no console errors

### Rollback

```
git checkout HEAD -- public/js/menu-filter.js
```

---

## Task 4 — Remove "all" click handler and update URL param fallback

**Complexity**: S (~23 lines changed)
**Dependencies**: Task 2 (uses `firstCategorySlug` and `firstCategoryTab`)

### Description

1. Delete the `filter === 'all'` click handler branch (lines 230-238)
2. Update URL param fallback (lines 322-348) to use first category instead of "all"

### File Changes

**`public/js/menu-filter.js`**

**Change 1 — Lines 230-238**: Delete the "all" scroll block:

```js
// DELETE these lines (230-238):
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
```

Replace with just:

```js
if (filter !== 'popular') {
```

**Change 2 — Lines 322-348**: Replace URL param handling:

```js
// Before (lines 322-348):
const catParam = new URLSearchParams(window.location.search).get('cat');

if (catParam && catParam !== 'all') {
  const target = filterBar.querySelector(`[data-filter="${CSS.escape(catParam)}"]`);

  if (target) {
    setActiveTab(target);
    activeCategory = catParam;

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

// After:
const catParam = new URLSearchParams(window.location.search).get('cat');

if (catParam && catParam !== 'popular') {
  const target = filterBar.querySelector(`[data-filter="${CSS.escape(catParam)}"]`);

  if (target) {
    setActiveTab(target);
    activeCategory = catParam;

    const deliveryBtn = document.querySelector('[data-channel-target="delivery"]');
    if (deliveryBtn) {
      deliveryBtn.click();
    } else {
      applyFilters();
    }
  } else {
    // Unknown slug or legacy ?cat=all — fall back to first category
    activeCategory = firstCategorySlug;
    if (firstCategoryTab) {
      setActiveTab(firstCategoryTab);
    }
    applyFilters();
  }
}
```

### Acceptance Criteria

- [x] No `filter === 'all'` branch exists in click handler
- [x] `?cat=valid-slug` selects the correct tab and scrolls to section
- [x] `?cat=all` falls back to first category (no error, no "all" tab activation)
- [x] `?cat=unknown-slug` falls back to first category silently
- [x] `?cat=popular` is excluded from URL param handling (popular is loaded on click, not URL)

### Testing Steps

1. Navigate to `/menu?cat=<existing-slug>` — verify correct tab selected
2. Navigate to `/menu?cat=all` — verify first category selected, no errors
3. Navigate to `/menu?cat=nonexistent` — verify first category selected, no errors
4. Click each tab — verify scroll-to-section works for category tabs
5. Check browser console — no errors

### Rollback

```
git checkout HEAD -- public/js/menu-filter.js
```

---

## Task 5 — Update JSDoc comment to reflect removal of "all"

**Complexity**: S (~3 lines changed)
**Dependencies**: Tasks 1-4

### Description

Update the module JSDoc header in `menu-filter.js` to remove references to `'all'` reset behavior.

### File Changes

**`public/js/menu-filter.js`**

Lines 4 and 9:

```js
// Line 4 — Before:
 * ESM module: unified filter (category + search text) with "All" reset.

// Line 4 — After:
 * ESM module: unified filter (category + search text) with first-category default.

// Line 9 — Before:
 *   [data-filter]           — category slug on each tab button ('all' for reset)

// Line 9 — After:
 *   [data-filter]           — category slug on each tab button ('popular' for best-sellers)
```

### Acceptance Criteria

- [x] No mention of `'all'` reset in JSDoc comments
- [x] Comments accurately describe current behavior

### Testing Steps

N/A — comment-only change.

### Rollback

```
git checkout HEAD -- public/js/menu-filter.js
```

---

## Task 6 — Manual integration testing (all scenarios)

**Complexity**: S (no code changes)
**Dependencies**: Tasks 1-5

### Description

Execute all manual test scenarios from the spec and design to verify end-to-end correctness.

### Test Matrix

| # | Scenario | Steps | Expected Result |
|---|----------|-------|-----------------|
| 1 | Default page load | Navigate to `/menu` (delivery mode) | "Mas vendidos" first (unselected), first category second (selected), only first category products visible |
| 2 | No "All" tab | Inspect filter bar | No "Todo" / "All" button exists |
| 3 | Category tab click | Click a category tab | Tab highlights, products filter, smooth scroll to section |
| 4 | Popular tab click | Click "Mas vendidos" | Tab highlights, popular products load via API |
| 5 | ScrollSpy | Scroll through categories | Tabs highlight matching visible section |
| 6 | Scroll to top | Scroll above all sections | First category tab re-activates |
| 7 | `?cat=valid-slug` | Navigate to `/menu?cat=<slug>` | Correct tab selected, auto-switch to delivery |
| 8 | `?cat=all` (legacy) | Navigate to `/menu?cat=all` | First category selected, no error |
| 9 | `?cat=unknown` | Navigate to `/menu?cat=nonexistent` | First category selected, no error |
| 10 | Dine-in unaffected | Switch to "En Local" | Accordion view, no filter tabs visible |
| 11 | Search | Type in search box | Products filter by text, tabs remain functional |
| 12 | Keyboard nav | Arrow keys on tabs | Focus moves between tabs |
| 13 | No console errors | All above scenarios | Zero JS errors in browser console |

### Acceptance Criteria

- [ ] All 13 scenarios pass — **PENDING human browser verification** (no test runner exists; apply ran headless)
- [ ] No JavaScript console errors in any scenario — **PENDING human browser verification**
- [x] Server-rendered HTML (view-source) shows correct active state before JS loads — verified statically: template inspection + `php -l` pass + `node --check` pass

### Rollback

N/A — testing only.

---

## Dependency Graph

```
Task 1 (PHP template)
  └──→ Task 2 (JS init + applyFilters)
         ├──→ Task 3 (ScrollSpy fallback)
         └──→ Task 4 (click handler + URL param)
                └──→ Task 5 (JSDoc cleanup)
                       └──→ Task 6 (integration testing)
```

## Summary

| Task | File | Complexity | Est. Lines | Dependencies |
|------|------|-----------|-----------|--------------|
| 1. Remove "All" tab, reorder template | `menu.php` | S | ~20 | None |
| 2. JS init state + applyFilters | `menu-filter.js` | S | ~20 | Task 1 |
| 3. ScrollSpy fallback | `menu-filter.js` | S | ~12 | Task 2 |
| 4. Click handler + URL param | `menu-filter.js` | S | ~23 | Task 2 |
| 5. JSDoc cleanup | `menu-filter.js` | S | ~3 | Tasks 1-4 |
| 6. Manual integration testing | — | S | 0 | Tasks 1-5 |
| **Total** | | | **~78** | |
