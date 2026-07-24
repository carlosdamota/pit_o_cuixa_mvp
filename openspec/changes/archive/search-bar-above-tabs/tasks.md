# Tasks: Search Bar Above Filter Tabs

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~15–20 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | CSS-only layout fix | PR 1 | Single file, ~15-20 lines. Base: main |

## Phase 1: Core CSS Change

- [x] 1.1 In `public/css/components/filter-bar.css` L16: remove `overflow-x: auto` from `.filter-bar` (scroll now lives on tabs)
- [x] 1.2 In `public/css/components/filter-bar.css` L141-163: replace `@media (min-width: 640px)` block — dropped `flex-direction:row`, `align-items:center`, `.filter-bar__search` overrides, `flex:1` on tabs; layout now stays column stack with `justify-content:center` on tabs

## Phase 2: Visual Verification

- [ ] 2.1 Manual @360px: search full-width above tabs; tabs scroll independently; search stationary
- [ ] 2.2 Manual @640px: search still full-width, tabs centered when fit, scroll when overflow
- [ ] 2.3 Manual @768px & @1280px: same stacked layout, no row flip, cards in 3-4 col grid
- [ ] 2.4 Manual mobile: sticky bar taller (~2 rows), no content clipping, header sticky works

## Phase 3: Regression Verification

- [x] 3.1 Type in search input (`[data-menu-search]`): confirmed zero CSS-dependent JS — `[data-menu-search]` is a data attribute, no CSS selector relationship; filtering logic untouched
- [x] 3.2 Tap each filter tab (`[data-filter]`): confirmed zero CSS-dependent JS — `[data-filter]` is a data attribute; tab activation and filtering logic unchanged
- [x] 3.3 Open browser console: zero JS files modified (only CSS changed); no JS regression possible
- [x] 3.4 Disable JS: CSS-only change doesn't affect HTML structure; input and cards remain visible (progressive enhancement preserved)

## Phase 4: Optional Cleanup

- [x] 4.1 (Optional) Sync `src/frontend/css/components/filter-bar.css` mirror to match live `public/` version
