# Design: Search Bar Above Filter Tabs

## Technical Approach

**Premise correction.** The proposal targeted the stale, *unserved* `src/frontend/css/components/filter-bar.css` (81 lines, no search rules). The live shipped file is `public/css/components/filter-bar.css` (163 lines) — `public/` is the web root, no build step (README L118, setup.php L260). Editing `src/` alone ships nothing. The live CSS **already stacks search above tabs on mobile** (`flex-direction: column`, `width: 100%`, tabs-only scroll). The only real gap: the `@media (min-width: 640px)` block (L141-163) re-flips to a side-by-side `row` with a constrained-width search (`min-width: 240px`) and `flex: 1` tabs — violating PC-005 ("stacked at ALL widths", "full container width"). The design is therefore a **surgical edit to one media query in the live file** to keep the column stack at every width. Maps to PC-005 + PC-006 deltas.

## Architecture Decisions

### Decision: Edit the live `public/` CSS, not the stale `src/` mirror

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Edit `public/css/components/filter-bar.css` | Ships immediately; matches established project pattern (prev change edited `public/` CSS) | **Chosen** |
| Edit `src/frontend/css/...` | Ships nothing (unserved) | Rejected as sole target |
| Edit both (sync) | Larger diff; re-syncs 81→150+ lines, out of proposal scope | Recommended hygiene (see Open Questions) |

**Rationale**: `default.php` L96 links `/css/components/filter-bar.css` → `public/css/`. README L118 states `src/frontend/css/` is *served via* `public/css/`. The previous search change (archive `necesito-una-barra...`, L67) already modified `public/css/`. Targeting `public/` is the established, correct path.

### Decision: Keep mobile Flexbox column, do NOT introduce CSS Grid

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Modity the 640px media query to keep `flex-direction: column` | 4-line diff; reuses existing working mobile layout | **Chosen** |
| Rewrite `.filter-bar__inner` to `display: grid` (proposal's plan) | Larger churn; discards already-correct mobile flex; based on stale src | Rejected |

**Rationale**: Proposal's grid plan solved a problem that doesn't exist in the live file — mobile already stacks via the very `flex-direction: column` the proposal tried to invent. Grid would be churn for churn's sake. Follow the existing pattern (BEM + Flexbox mobile-first).

### Decision: At ≥640px, keep search full-width; center tabs only

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Remove the row/search-width/tabs-flex overrides; add `justify-content: center` on tabs | Search stays full width; tabs centered when they fit, scroll when overflow (mobile tabs rules already scoped) | **Chosen** |
| Force all-widths scroll on tabs | Always-scroll UX on desktop | Rejected |

**Rationale**: Mobile `.filter-bar__tabs` already has `min-width: max-content; overflow-x: auto; scrollbar-width: none` — these persist through the breakpoint (the override only set `flex: 1; gap`). Removing `flex: 1` restores independent tabs scroll; `justify-content: center` satisfies the success criterion "tabs centered on tablet/desktop".

## Data Flow

```
 viewport ≥640px
   ┌─────────────── .filter-bar__inner (column, gap md) ───────────────┐
   │  .filter-bar__search  → width:100% (mobile base, no override)     │
   │  .filter-bar__tabs    → justify-content:center; gap sm;           │
   │                         min-width:max-content; overflow-x:auto    │
   │                         (tabs scroll only if content overflows)   │
   └───────────────────────────────────────────────────────────────────┘
   No JS, no PHP, no DOM change — JS uses [data-menu-search]/[data-filter].
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `public/css/components/filter-bar.css` | Modify | Edit `@media (min-width: 640px)` (L141-163): drop `flex-direction:row` + `align-items:center` on `.filter-bar__inner`; delete the `.filter-bar__search { flex-shrink:0; width:auto; min-width:240px }` override; remove `flex:1` from `.filter-bar__tabs` and add `justify-content:center`. Keep `.filter-bar__tab` padding/font bump. Also: remove stale `overflow-x: auto` on `.filter-bar` (L16) — scroll now lives on tabs (PC-005 "scroll scoped to tabs row"). |
| `src/frontend/css/components/filter-bar.css` | (Optional sync) | Re-sync stale mirror to match `public/`. See Open Questions. |

### Resulting 640px block (replaces L141-163)

```css
@media (min-width: 640px) {
  /* PC-005: search stays full-width stacked above tabs at all widths */
  .filter-bar__inner { gap: var(--space-md); }
  .filter-bar__tabs {
    justify-content: center;
    gap: var(--space-sm);
  }
  .filter-bar__tab {
    padding: var(--space-sm) var(--space-lg);
    font-size: var(--font-size-base);
  }
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Manual @360 | Search full-width above tabs; tabs scroll independently; search stays put | Browser devtools |
| Manual @640/768/1280 | Search still full-width & stacked (NOT row); tabs centered when fit; scroll when overflow | Resize sweep |
| Manual mobile @ ≤640 | Sticky bar taller (~2 rows); no content clipping; header sticky still seats above | Scroll the menu |
| Regression | JS search (`[data-menu-search]`) + tab filter (`[data-filter]`) unchanged; console error-free | Type + tap; open console |
| No-JS | Input present; all cards visible | Disable JS |

No automated runner in project (verified: no test infra). All checks manual per `.filter-bar` exists only on `/menu`.

## Migration / Rollout

No migration. Single-file CSS revert restores L141-163 + L16. Zero JS/PHP risk (PC-006).

## Open Questions

- [ ] **src mirror drift (hygiene):** `src/frontend/css/components/filter-bar.css` is 81 lines, missing all search rules — stale since the prior search change. Re-sync now or leave (out of proposal scope)? Recommend orchestrator decide; default = leave, flag in verify.
- [ ] None blocking beyond above.

## Next Step

Ready for tasks (sdd-tasks). Forecast: ~15-20 changed lines in one file → **Decision needed before apply: No • Chained PRs recommended: No • 400-line budget risk: Low**.