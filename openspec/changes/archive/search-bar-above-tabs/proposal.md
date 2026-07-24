# Proposal: Search Bar Above Filter Tabs

## Intent

The search bar and filter tabs currently share a single horizontal row inside `.filter-bar__inner` on the menu page. This makes the search input compete with category tabs for attention and horizontal space — especially on mobile where both scroll together. Moving the search bar above the tabs (full width) makes search more prominent, separates the two concerns visually, and eliminates the awkward shared horizontal scroll.

## Scope

### In Scope
- CSS layout change: stack search bar above filter tabs vertically
- Search bar occupies full container width
- Horizontal scroll scoped to tabs row only (mobile)
- Responsive breakpoint adjustments (640px+)

### Out of Scope
- Search functionality or JavaScript behavior
- Filter tab logic or DOM structure
- Other pages or components
- HTML/PHP template changes

## Capabilities

### New Capabilities
- None

### Modified Capabilities
- `product-catalog`: PC-005 mobile layout — filter bar scroll behavior changes from full-bar scroll to tabs-only scroll; search bar becomes a full-width stacked row above tabs

## Approach

CSS-only change in `src/frontend/css/components/filter-bar.css`:

1. Change `.filter-bar__inner` from `display: flex` to `display: grid; grid-template-columns: 1fr` — stacks children vertically
2. Remove `min-width: max-content` from `.filter-bar__inner`
3. Add to `.filter-bar__tabs`: `display: flex; overflow-x: auto; min-width: max-content; scrollbar-width: none` — scopes horizontal scroll to tabs row
4. Move `justify-content: center` from `.filter-bar__inner` media query to `.filter-bar__tabs` at 640px+
5. Remove `overflow-x: auto` from `.filter-bar` (no longer needed — scroll is on tabs)

No HTML changes. No JavaScript changes. JS uses attribute selectors (`[data-menu-search]`, `[data-filter]`) independent of DOM position.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/frontend/css/components/filter-bar.css` | Modified | Grid layout, scroll behavior, breakpoint rules |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Mobile scroll regression — search bar caught in tabs scroll | Low | Scope `overflow-x: auto` to `.filter-bar__tabs` only, not parent |
| Increased sticky bar height pushes content down on mobile | Low | Acceptable tradeoff; search row is compact (~40px). Monitor in testing |
| Hidden scrollbar styles lost when moving scroll to tabs | Low | Reapply `scrollbar-width: none` and `::-webkit-scrollbar` on `.filter-bar__tabs` |

## Rollback Plan

Revert the CSS changes in `filter-bar.css` — restore `display: flex` on `.filter-bar__inner`, move `overflow-x: auto` back to `.filter-bar`, remove grid and tabs-specific scroll rules. Single file revert, zero risk to JS or PHP.

## Dependencies

- None

## Success Criteria

- [ ] Search bar renders above filter tabs on all viewport widths (360px–1280px+)
- [ ] Search bar occupies full container width
- [ ] Filter tabs remain horizontally scrollable on mobile (<640px)
- [ ] Filter tabs centered on tablet/desktop (≥640px)
- [ ] No visual regression on sticky bar behavior
- [ ] No JavaScript errors or broken filter/search functionality
