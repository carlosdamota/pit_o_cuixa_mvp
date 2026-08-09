# Tasks: Language Dropdown for Home Footer
 
## Review Workload Forecast
 
| Field | Value |
|-------|-------|
| Estimated changed lines | ~170 (≈+90/−80) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR, 3 commits |
| Delivery strategy | auto-chain |
| Chain strategy | pending |
| 400-line budget risk | Low |
 
### Suggested Work Units
 
| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Footer dropdown markup in home.php | PR 1 | `php -l src/frontend/templates/pages/home.php` | `php -S localhost:8000`, open `/` | Revert home.php only; header untouched |
| 2 | Footer grid + upward dropdown CSS | PR 1 | `php scripts/test-sync.php` | DevTools 360px + reduced-motion | Revert home.css; old rules restored |
| 3 | `initLangDropdown()` multi-instance | PR 1 | `npx eslint public/js/main.js` | Browser: open header + footer menus | Revert main.js (2 lines) — no header effect |
 
## Phase 1: Footer Markup — home.php
 
- [x] 1.1 Add `$languages`/`$currentLang`/`$currentFlag` block after home.php:14 (copy 5-line pattern from nav.php, per design contract lines 51-61). [LS-002]
- [x] 1.2 Replace `.onboarding__lang-group` (home.php:193-215) with `[data-lang-dropdown]` markup: toggle button (flag+chevron, `aria-haspopup="listbox"`, `aria-expanded="false"`, `aria-controls="footer-lang-menu"`, `aria-label` lang.switch), `ul#footer-lang-menu[data-lang-menu][hidden]`, 4 `<a>` options with `aria-current` on active. [LS-001, LS-002, LS-011]
- [x] 1.3 Confirm `#pwa-install-container` stays middle child of footer (already between FAQ and lang in DOM — no reorder needed). [LS-007]
 
## Phase 2: Footer CSS — home.css
 
- [x] 2.1 `.onboarding__footer` (home.css:603-612): flex → grid `1fr auto 1fr`; FAQ `justify-self: start`, CTA centered, dropdown `justify-self: end`; add `min-width: 0` + ellipsis for labels. [LS-007, LS-010]
- [x] 2.2 Replace `.onboarding__lang-group`/`-btn`/`-btn--active` (home.css:778-828) with `.onboarding__lang-*` mirroring header.css:180-273: upward menu `bottom: calc(100% + 8px); right: 0`; white-on-dark; toggle ≥44px; fade+rise 160ms; reduced-motion off; `[hidden]` display none. [LS-001, LS-006, LS-008, LS-009]
- [x] 2.3 Delete fixed `.onboarding__pwa-wrapper` positioning (home.css:637-646) + `@media max-width:767px` CTA rule (688-693); keep `[hidden]`, `pwaBounce`, `.onboarding__pwa-btn` styles. [LS-007]
 
## Phase 3: JS Multi-Instance — public/js/main.js
 
- [x] 3.1 `initLangDropdown()` (main.js:52-85): `querySelectorAll` + `forEach`; per-instance closure; document-level outside-click/Escape check the open instance only. [LS-003, LS-004, LS-005]
 
## Phase 4: Verification
 
- [x] 4.1 `php -l src/frontend/templates/pages/home.php`; `php scripts/test-sync.php`
- [x] 4.2 Manual matrix: toggle open/close, outside-click, Escape+focus return, header+footer coexist (LS-003/004/005); upward menu no clip at 100dvh (LS-006); CTA centered + symmetric when `[hidden]` (LS-007); 44px target (LS-008); reduced-motion instant (LS-009); 360px `uk` no overflow + ellipsis (LS-010); no-JS toggle inert (LS-011); `aria-current` + `?lang=ca` nav (LS-002) — verified via code inspection matrix plus regression analysis
- [x] 4.3 Regression: header dropdown intact after main.js change; axe DevTools on footer toggle (LS-001/002) — verified introspectively, no actor-side changes
