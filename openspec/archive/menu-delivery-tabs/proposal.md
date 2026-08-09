# Proposal: Menu Delivery Tab Reorder

## Intent

The delivery filter bar currently defaults to "Todo" (all products), forcing users to scroll past irrelevant items before finding what they want. Reordering tabs to lead with a category and removing the "all" default reduces decision fatigue and aligns the tab bar with how customers actually browse — by category, not by "everything".

## Scope

### In Scope
- Reorder delivery filter tabs: `Mas vendidos` first (unselected), then category tabs (first selected by default)
- Remove the "Todo / All" tab entirely
- Update JS default state from `'all'` to first category slug
- Update ScrollSpy fallback to use first category instead of `'all'`
- Update `?cat=` URL param fallback to use first category instead of `'all'`

### Out of Scope
- Backend changes (`Product::popular()`, `/api/products/popular` work correctly)
- Dine-in channel view (unchanged)
- Search functionality (unchanged)
- CSS/layout changes (unchanged)

## Capabilities

### New Capabilities
None

### Modified Capabilities
- `product-catalog`: PC-002 default tab selection changes from "All" to first category; PC-002 "All filter resets view" scenario is removed; ScrollSpy and URL param fallbacks updated to match new default

## Approach

**Template** (`src/frontend/templates/pages/menu.php:81-104`):
- Remove the `data-filter="all"` button
- Move `data-filter="popular"` button before the category loop
- Add `filter-bar__tab--active` + `aria-pressed="true"` to the first `$catList` item

**JavaScript** (`public/js/menu-filter.js`):
- Line 39: Replace `let activeCategory = 'all'` with first category slug (read from first category tab's `data-filter`)
- Lines 77-86: Replace `activeCategory === 'all'` branch with first-category logic
- Lines 191-205: Scroll fallback re-activates first category tab instead of `'all'`
- Lines 230-238: Click handler for `'all'` tab removed (tab no longer exists)
- Lines 322-348: `?cat=` unknown slug fallback activates first category tab instead of `'all'`

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/frontend/templates/pages/menu.php` | Modified | Reorder/remove tab buttons (lines 81-104) |
| `public/js/menu-filter.js` | Modified | Replace all `'all'` defaults with first category slug |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| ScrollSpy fires on load before first category is in view | Low | ScrollSpy already skips when `activeCategory` matches; initial state matches first visible section |
| `?cat=all` from old home CTAs breaks | Med | Redirect `?cat=all` to first category slug silently |
| `$catList` empty edge case renders no selected tab | Low | Defensive check: if `$catList` is empty, skip active class; JS falls back gracefully |
| Popular tab loses visibility as first tab | Low | Still present as second tab; user can click it normally |

## Rollback Plan

Revert two files via git:
```
git checkout HEAD -- src/frontend/templates/pages/menu.php public/js/menu-filter.js
```
No database or config changes to undo.

## Dependencies

None

## Success Criteria

- [ ] "Mas vendidos" tab appears first, unselected on page load
- [ ] First category tab appears second, selected by default
- [ ] No "Todo / All" tab exists in the delivery filter bar
- [ ] ScrollSpy highlights correct category tab as user scrolls
- [ ] `?cat=valid-slug` still pre-selects the correct category
- [ ] `?cat=all` or `?cat=unknown` falls back to first category (not broken)
- [ ] No JavaScript console errors on menu page load or interaction
- [ ] Dine-in channel view unaffected
