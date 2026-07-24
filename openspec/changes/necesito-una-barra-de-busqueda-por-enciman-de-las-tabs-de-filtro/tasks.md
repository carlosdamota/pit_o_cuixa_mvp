# Tasks: Search Bar Above Filter Tabs

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~120–160 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

All frontend work — single sub-team, single PR.

## Phase 1: i18n Strings (Foundation)

- [x] 1.1 Add `menu.search.label`, `menu.search.placeholder`, `menu.search.no_results` to `src/shared/i18n/ca.php`
- [x] 1.2 Add same keys to `src/shared/i18n/es.php`
- [x] 1.3 Add same keys to `src/shared/i18n/en.php`

## Phase 2: Search Data Attribute (Foundation)

- [x] 2.1 Add `data-search-text="<?= strtolower(name_es . name_en . desc_es . desc_en) ?>"` to `src/frontend/templates/partials/product-card.php`

## Phase 3: Template & Input

- [x] 3.1 Restructure `src/frontend/templates/pages/menu.php`: wrap existing tabs in `.filter-bar__tabs`, add `.filter-bar__search` with `<label for="menu-search">` + `<input type="search" id="menu-search" data-menu-search>`
- [x] 3.2 Add `<p id="search-no-results" aria-live="polite" class="visually-hidden">` inside `[data-menu-products]` for no-results announcements

## Phase 4: CSS Layout

- [x] 4.1 Add `.filter-bar__search` / `.filter-bar__tabs` two-row layout in `public/css/components/filter-bar.css` (stack <640px, inline ≥640px)
- [x] 4.2 Add search input styles (height, border, font, clear-button tweaks) using design tokens
- [x] 4.3 Add `.visually-hidden` utility if missing, style no-results paragraph

## Phase 5: JavaScript Search Logic

- [x] 5.1 Replace `showAll()`/`filterByCategory()` with unified `applyFilters()` reading `activeCategory` + `searchQuery` closure variables in `public/js/menu-filter.js`
- [x] 5.2 Add `input` event listener on `[data-menu-search]` with ≥2-char gate, `data-search-text.includes()` match
- [x] 5.3 Add no-results toggle: hide/show `#search-no-results` paragraph based on visible card count
- [x] 5.4 Verify keyboard arrow-nav between tabs still works after refactor

## Phase 6: Verification

- [x] 6.1 `php -l` on all modified PHP files (i18n, product-card, menu)
- [x] 6.2 Manual browser checklist: name match, desc match, AND with tab, × clear, ≥2 chars gate, no-results, mobile 360px, desktop 1280px, no-JS regression
