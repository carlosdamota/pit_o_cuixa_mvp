# Apply Progress: Menu Delivery Tab Reorder

**Change**: `menu-delivery-tabs`
**Date**: 2026-08-07
**Mode**: `both` (OpenSpec + Engram, topic_key `sdd/menu-delivery-tabs/apply-progress`)
**Delivery**: ask-on-risk — ~64 changed lines, within 400-line budget, no guard needed

## Status

Tasks 1–5 **COMPLETE** and statically verified. Task 6 (manual browser testing) is **PENDING human verification** — this project has no test runner and the apply phase ran headless, so the 13 browser scenarios could not be executed here. Static checks covering the non-browser portions of Task 6 were run (see Testing below).

## Tasks Completed

| Task | File | Status | Notes |
|------|------|--------|-------|
| 1. Remove "All" tab, reorder template | `src/frontend/templates/pages/menu.php` | ✅ | "popular" first (unselected), first category active via `$index === 0`, `aria-pressed` set per tab |
| 2. JS init state + applyFilters | `public/js/menu-filter.js` | ✅ | `activeCategory` reads first category slug from DOM; `''` empty-string edge case branch |
| 3. ScrollSpy fallback | `public/js/menu-filter.js` | ✅ | Scroll-to-top re-activates first category instead of "all" |
| 4. Click handler + URL param | `public/js/menu-filter.js` | ✅ | `filter === 'all'` branch deleted; `?cat=all`/unknown → first category; `?cat=popular` excluded |
| 5. JSDoc cleanup | `public/js/menu-filter.js` | ✅ | Header + data-attribute doc updated |
| 6. Manual integration testing | — | ⏳ | Pending human browser verification (test matrix documented in tasks.md) |

## Files Modified (with line counts)

| File | + | − | Net |
|------|---|---|-----|
| `src/frontend/templates/pages/menu.php` | 5 | 10 | −5 (385 → 380 lines) |
| `public/js/menu-filter.js` | 20 | 29 | −9 (349 → 340 lines) |
| **Total** | **25** | **39** | **−14** |

Measured via `git diff --numstat`. Estimate was ~78 changed lines; actual changed lines (insertions + deletions) = 64, within budget.

## Deviations from Plan

1. **Inline comment updated (menu-filter.js line 313)**: The URL-param comment "Unknown slugs fall back silently to 'all'." was updated to "Unknown slugs and legacy ?cat=all fall back silently to the first category." — tasks.md only specified the code block (lines 322–348) and omitted this comment line, but leaving it stale would contradict Task 4's acceptance criteria ("?cat=all falls back to first category"). Minimal, 1-line deviation.
2. **Task 6 marked PENDING**: Browser-based acceptance criteria cannot be verified headless. Server-rendered HTML correctness was verified statically instead (see below).

## Static Verification Performed (Task 6 partial)

- `php -l src/frontend/templates/pages/menu.php` → **No syntax errors detected**
- `node --check public/js/menu-filter.js` → **passes**
- `grep 'data-filter="all"'` in `menu.php` → **no matches** (All tab removed)
- `grep "'all'|\"all\""` in `menu-filter.js` → **no matches** (zero stale references in filter logic)
- `grep 'menu.filter.all'` in `src/` → only the 4 i18n translation files (`es.php`, `en.php`, `ca.php`, `uk.php`) still define the key; it is now **unused** but harmless (leaving it avoids breaking lookups in other locales). Optional cleanup for a future change.
- `accordion.js` checked: only toggles `hidden` on `[data-filter-tabs]` container — no dependency on an "all" tab. `main.js` only imports `initMenuFilter`.

## Testing Results

No automated tests exist (no PHPUnit/Pest). Manual browser test matrix (13 scenarios from tasks.md) is **pending human execution** — scenarios 1–9, 11 relate to the changed filter bar; scenario 10 (dine-in) and 12 (keyboard nav) are unaffected by this change but should still be smoke-tested.

## Edge Cases Handled

- Empty `$catList`: `foreach` renders nothing, no PHP error; JS `firstCategorySlug` → `''`; `applyFilters()` shows all blocks via the `activeCategory === ''` branch.
- Only "popular" tab present (no categories): `firstCategoryTab` is `null`, `firstCategorySlug` is `''`, scroll fallback guard `activeCategory !== firstCategorySlug` is a no-op — no JS errors.
- Legacy `?cat=all` and unknown slugs both route through the `else` fallback → first category tab, no error, no "all" tab lookup.

## Rollback Instructions

```sh
git checkout HEAD -- src/frontend/templates/pages/menu.php public/js/menu-filter.js
```

Re-apply only if a later change touches the same files and the revert is desired.
