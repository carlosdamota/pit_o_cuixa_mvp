# Archive Report: menu-delivery-tabs

## Change Summary

**Change Name**: menu-delivery-tabs
**Archived**: 2026-08-07
**Status**: Completed (PASS WITH WARNINGS)

## Intent

Remove the "Todo / All" default tab from the delivery menu filter bar, lead with "Más vendidos" (unselected), and auto-select the first category tab by default. This reduces decision fatigue and aligns the tab bar with how customers actually browse — by category, not by "everything".

## Artifacts Synced

### Delta Spec → Main Spec

**Source**: `openspec/changes/menu-delivery-tabs/specs/product-catalog/spec.md`
**Target**: `openspec/specs/product-catalog/spec.md`

**Changes merged**:
- Modified PC-002 requirement description: removed "All" tab, added "Más vendidos" first (unselected), first category selected by default
- Removed scenario: "All filter resets view"
- Added 4 new scenarios:
  - Default tab selection on page load
  - ScrollSpy highlights active category
  - Valid `?cat=` URL parameter
  - Unknown or legacy `?cat=all` URL parameter

**Main spec updated**: ✅ Yes (PC-002 now reflects new behavior)

### Capability Registry

**File**: `openspec/capabilities.yaml`
**Status**: Does not exist — no update needed

## Implementation Summary

### Files Modified

| File | Lines Changed | Description |
|------|---------------|-------------|
| `src/frontend/templates/pages/menu.php` | 5+/10- | Removed "all" tab button, reordered tabs, first category active by default |
| `public/js/menu-filter.js` | 20+/29- | Replaced `'all'` defaults with first category slug, updated ScrollSpy fallback, URL param handling |

**Total**: 64 lines changed (25 insertions / 39 deletions)

### Tasks Completed

- ✅ Task 1: Remove "All" tab and reorder PHP template
- ✅ Task 2: Update JS initial state and applyFilters logic
- ✅ Task 3: Update ScrollSpy fallback to use first category
- ✅ Task 4: Remove "all" click handler and update URL param fallback
- ✅ Task 5: Update JSDoc comment to reflect removal of "all"
- ⚠️ Task 6: Manual integration testing (PENDING — requires browser)

### Verification Results

**Verdict**: PASS WITH WARNINGS

- **Build**: ✅ Passed (`php -l` + `node --check`)
- **Spec Compliance**: 6/6 scenarios compliant
- **Task Acceptance**: 19/20 criteria PASS (1 PENDING — browser testing)
- **Design Coherence**: 5/5 decisions faithfully implemented
- **Regression Check**: ✅ Zero `'all'` references in filter logic
- **Edge Case Coverage**: ✅ Complete (empty `$catList`, only popular tab, legacy URLs)

## Warnings

1. **Task 6 — Manual browser testing pending**: 13 scenarios require human browser verification (scroll behavior, IntersectionObserver, tab highlighting, console errors). Static analysis confirms code structure matches specifications, but visual behavior needs live browser testing.

2. **Unused i18n key `menu.filter.all`**: Still defined in 4 translation files (`es.php`, `en.php`, `ca.php`, `uk.php`). No runtime impact (key is never queried), but represents dead code. Optional cleanup in a future change.

## Rollback Instructions

```bash
git checkout HEAD -- src/frontend/templates/pages/menu.php public/js/menu-filter.js
```

No database or config changes to undo.

## Follow-ups

- [ ] Execute Task 6 manual browser testing (13 scenarios from tasks.md)
- [ ] Optional: Remove unused i18n key `menu.filter.all` from 4 locale files
- [ ] Optional: Add lightweight JS test runner (Vitest + jsdom) for automated filter logic testing

## Archive Location

**Original**: `openspec/changes/menu-delivery-tabs/`
**Archived**: `openspec/archive/menu-delivery-tabs/`

All artifacts preserved:
- proposal.md
- specs/product-catalog/spec.md (delta)
- design.md
- tasks.md
- apply-progress.md
- verify-report.md
- archive-report.md (this file)

## Session Metadata

- **Project**: pit_o_cuixa_mvp
- **Stack**: PHP 8.2+ + HTML5 + CSS (BEM) + JavaScript (ES Modules) + SQLite
- **SDD Mode**: Interactive, both (OpenSpec + Engram), ask-on-risk, 400-line budget
- **Actual lines**: 64 (within budget)
- **Duration**: Single session (2026-08-07)
