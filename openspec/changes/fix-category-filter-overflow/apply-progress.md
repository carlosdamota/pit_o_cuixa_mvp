# Apply Progress: Fix Category Filter Bar Overflow

## Status: Phase 1 Complete, Phase 2 Pending

### Phase 1 — Core Fix ✅

| Task | Status | Evidence |
|------|--------|----------|
| 1.1 Remove `min-width: max-content` from `.filter-bar__tabs` | ✅ Done | `public/css/components/filter-bar.css` — removed line 82 (`git diff -U1` confirms deletion) |
| 1.2 Sync src with fixed deployed version | ✅ Done | `src/frontend/css/components/filter-bar.css` fully replaced with fixed `public/` version (restructured from 81-line old implementation to 159-line aligned version with search bar, no-results message, mobile snap, and correct overflow handling) |

### Phase 2 — Manual Verification 🔲

#### Changes Made

**File 1: `public/css/components/filter-bar.css`**
- Removed `min-width: max-content` from `.filter-bar__tabs` (line 82)
- This was forcing the tabs container to expand to fit ALL tab content, pushing past the parent width and causing page-level horizontal scroll
- With `overflow-x: auto` still present, the tabs now scroll internally as intended

**File 2: `src/frontend/css/components/filter-bar.css`**
- Complete replacement to match the corrected `public/` structure
- Old `src/` version had a different approach (overflow on `.filter-bar` itself, tabs in a horizontal row via `.filter-bar__inner`)
- New version now uses the `.filter-bar > .filter-bar__inner > .filter-bar__search + .filter-bar__tabs` stacked layout with internal scroll on `.filter-bar__tabs`

#### Manual Verification Checklist

Open the menu page and verify:

- [ ] **Mobile (< 640px)**: Open Chrome DevTools, set viewport to 375px wide. With 6+ category tabs, confirm: (a) tabs row scrolls horizontally with snap, (b) no page-level horizontal scrollbar appears, (c) search bar stays full-width and stationary
- [ ] **Desktop (640px+)**: Set viewport to 1024px. Confirm: (a) tabs are centered below search bar, (b) no horizontal scrollbar at any level, (c) search bar is full-width
- [ ] **Long category names**: Add a tab with text like "Categoría con nombre muy largo para probar". Confirm the tab container scrolls to reveal it, search bar stays put
- [ ] **Edge — single category**: Minimal tabs should still display correctly, centered on desktop, left-aligned on mobile
- [ ] **Edge — many categories (12+)**: Aggressive test — tabs should scroll smoothly, no page overflow
