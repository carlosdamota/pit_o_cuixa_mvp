# Proposal: Fix Category Filter Bar Overflow

## Intent

The category filter bar on `/menu` overflows the entire page instead of scrolling internally within the tabs row. `min-width: max-content` on `.filter-bar__tabs` (line 82, deployed CSS) forces the container to expand beyond its parent width, defeating `overflow-x: auto`. Users on mobile must scroll the whole page horizontally instead of swiping just the tab row — violating spec PC-005.

## Scope

### In Scope
- Remove `min-width: max-content` from `.filter-bar__tabs` in `public/css/components/filter-bar.css`
- Sync source CSS (`src/frontend/css/components/filter-bar.css`) with deployed version to eliminate divergence

### Out of Scope
- HTML/PHP template changes (`menu.php` structure is correct)
- JavaScript filter logic changes
- New features or UI enhancements beyond the overflow fix

## Capabilities

### New Capabilities
None

### Modified Capabilities
- `product-catalog`: Fix PC-005/PC-007 compliance — horizontal scroll must be scoped to tabs row only, search bar must remain stationary

## Approach

One-line CSS deletion: remove `min-width: max-content` from `.filter-bar__tabs` (line 82). The existing `overflow-x: auto` + `flex-shrink: 0` on child `.filter-bar__tab` already provides correct scroll behavior without it.

After fix, reconcile `src/frontend/css/components/filter-bar.css` (81 lines, outdated — different class structure, no search styles, no `.filter-bar__tabs`) with the deployed 160-line version.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `public/css/components/filter-bar.css` | Modified | Remove line 82 (`min-width: max-content`) |
| `src/frontend/css/components/filter-bar.css` | Modified | Sync with deployed version after fix |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Removing `min-width` breaks desktop centering | Low | `justify-content: center` in `@media (min-width: 640px)` handles desktop layout independently |
| Source CSS sync introduces regressions | Medium | Diff both files carefully; deployed CSS is the source of truth |

## Rollback Plan

Revert the CSS commit. The change is a single-line deletion — `git revert` restores the previous state instantly.

## Dependencies

None

## Success Criteria

- [ ] Tabs row scrolls horizontally on mobile (viewport < 640px) without page-level horizontal scroll
- [ ] Search bar remains stationary and full-width during tab scrolling
- [ ] Desktop layout (>= 640px) unchanged — tabs centered below search
- [ ] Source CSS (`src/frontend/css/`) matches deployed CSS (`public/css/`) after sync
