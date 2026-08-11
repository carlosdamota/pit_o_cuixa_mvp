# Proposal: Language Dropdown for Home Footer

## Intent

The onboarding footer's 4-flag language row (~172px) crowds the home landing and leaves no room for the PWA Install CTA, which floats fixed bottom-left. The header already uses a compact language dropdown — the home footer is the inconsistent outlier. This change replaces the flag row with a compact dropdown (~44px) mirroring the proven header pattern, and moves the Install CTA into the footer, centered.

## Scope

### In Scope
- Footer language dropdown: flag + chevron toggle, 4 options, opens upward + right-aligned
- Footer → CSS grid `1fr auto 1fr`; Install CTA becomes in-flow centered child; drop fixed positioning + its media query (keep `pwaBounce` and `[hidden]` handling)
- Harden `initLangDropdown()` to `querySelectorAll` so header + footer instances coexist
- A11y: `aria-expanded`/`aria-controls`/`aria-current`, 44px touch target, `prefers-reduced-motion`

### Out of Scope
- `?lang=` URL-switch mechanism, cookie logic, hreflang (SG-005) — untouched
- Header dropdown redesign; native `<select>` or cycle-button alternatives (rejected in exploration)
- iOS toast z-index (9995 vs 9999) — verify only; separate fix if confirmed

## Capabilities

### New
- `language-switcher`: shared dropdown behavior — disclosure toggle (flag + chevron), upward/right-aligned menu, outside-click/Escape close, ARIA states; covers header + footer instances wired by `initLangDropdown()`.

### Modified
None — PWA installability (PW-004) and i18n/hreflang requirements unchanged; the CTA relocation is presentation-level.

## Approach

1. Replace `.onboarding__lang-group` (home.php:166-216) with `data-lang-dropdown` markup from `nav.php:45-61`; open upward (`bottom: calc(100% + 8px); right: 0`) because `.landing--onboarding` is fixed 100dvh + `overflow: hidden`.
2. Style `.onboarding__lang-*` in home.css (fade + rise 160ms, reduced-motion off); `.onboarding__footer` → grid, `min-width: 0` + ellipsis for long Ukrainian labels.
3. Move `#pwa-install-container` into footer flow; delete fixed rules (home.css:637-693) + `max-width: 767px` variant.
4. Harden `initLangDropdown()` (main.js:52-85): `querySelectorAll` + loop.

## Affected Areas

| Area | Impact |
|------|--------|
| `src/frontend/templates/pages/home.php:166-216` | Modified — flag row → dropdown; CTA into footer |
| `src/frontend/css/pages/home.css:603-828` | Modified — `.onboarding__lang-*`, grid footer, drop fixed CTA |
| `public/js/main.js:52-85` | Modified — `querySelectorAll` |
| `src/frontend/css/layouts/header.css` | Reference only — pattern to mirror |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Menu clipped by `overflow: hidden` landing | High | Open upward `bottom: calc(100% + 8px)` |
| Footer overflow ≤360px (long Ukrainian labels) | Med | Grid `1fr auto 1fr`, `min-width: 0` + ellipsis |
| Single-instance JS init | Low | `querySelectorAll` hardening |
| iOS toast under landing (pre-existing) | Med | Verify at apply; separate fix if confirmed |
| No-JS users lose home switcher | Low | Accepted — links remain plain anchors |

## Rollback Plan

`git revert` the 3-file change restores the flag row and fixed CTA. The `main.js` revert (2 lines) has zero effect on the header. No DB, cache, or SEO side effects; no migration.

## Dependencies

None. `main.js` already loads on home (default.php:203); `lang.switch` keys exist in all 4 locales.

## Success Criteria

- [ ] Footer dropdown matches header behavior (open, outside-click, Escape, focus return)
- [ ] Install CTA visually centered; layout stays symmetric when CTA hidden
- [ ] No dropdown clipping at 360px / 100dvh viewports
- [ ] Header dropdown still works (multi-instance safe)
- [ ] `php -l` and `php scripts/test-sync.php` pass

## Estimated Effort

~3 files, ≈+90/−80 lines (~170 diff lines — within the 400-line review budget, no chaining needed). Low-Medium; one focused session.
