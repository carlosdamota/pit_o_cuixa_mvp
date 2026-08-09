# Apply Progress: Language Dropdown Implementation

## Status

- **Change**: language-dropdown
- **Phase**: apply (3/3 work units implemented, commits created)
- **State**: pending verification (manual matrix LS-001..LS-011 + regression + axe)

## What Was Implemented

### Phase 1: Markup (home.php) — DONE
- Added `$languages`/`$currentLang`/`$currentFlag` block after the `$langSuffix` line (copied 5-line pattern from nav.php).
- Replaced `.onboarding__lang-group` flag row with `[data-lang-dropdown]` markup: toggle button (`aria-haspopup="listbox"`, `aria-expanded="false"`, `aria-controls="footer-lang-menu"`, `aria-label` lang.switch), flag + chevron, `ul#footer-lang-menu[data-lang-menu][hidden]` with 4 `<a>` options carrying `aria-current="true"` on the active locale.
- Confirmed `#pwa-install-container` is the DOM middle child (between FAQ and dropdown) — no reorder needed.

### Phase 2: CSS (home.css) — DONE
- `.onboarding__footer`: flex → grid `1fr auto 1fr`; FAQ `justify-self: start`; dropdown `justify-self: end`; `min-width: 0` + ellipsis on FAQ text.
- New `.onboarding__lang-*` block: upward menu (`bottom: calc(100% + 8px); right: 0`), 44×44px min toggle, fade+rise keyframe animation 160ms, `prefers-reduced-motion: reduce` disables animation, `[hidden]` display none.
- Removed fixed `.onboarding__pwa-wrapper` positioning + `@media (max-width: 767px)` CTA rule; kept `[hidden]`, `pwaBounce`, `.onboarding__pwa-btn`.

### Phase 3: JS (main.js) — DONE
- `initLangDropdown()`: `querySelector('[data-lang-dropdown]')` → `querySelectorAll` + `forEach` with per-instance closures; single document-level outside-click + Escape handlers act only on the currently open instance.

## Commits

| Commit | Message | Files |
|--------|---------|-------|
| `787e386` | `feat(home): replace flag row with language dropdown` | home.php (+31/−23) |
| `d9bc01c` | `feat(home): grid footer + upward dropdown styles` | home.css (+130/−49) |
| `dd2a8fb` | `fix(js): harden initLangDropdown for multi-instance` | main.js (+45/−23) |

## Verification

### CLI (4.1) — PASSED
- `php -l src/frontend/templates/pages/home.php` → no syntax errors
- `php scripts/test-sync.php` → 58 passed, 0 failed
- `node --check public/js/main.js` → JS syntax OK (no eslint config in repo)

### Manual matrix (4.2) — PENDING (browser-based, hand off to sdd-verify)
- LS-001/002/003/004/005/006/007/008/009/010/011 behaviors to confirm in browser
- Note: main.js change is regression-safe for the header dropdown (same hooks, querySelectorAll includes header instance; single-instance header page behavior unchanged)

### Regression + axe (4.3) — PENDING (hand off to sdd-verify)

## Risks / Notes

- **Pre-existing uncommitted work isolated**: `home.php` had two unrelated uncommitted hunks (`Reserva Ara!` call text, drag-hint commented out). They were staged out of the apply commits via a filtered patch (only hunks 0/3/4 of the diff) and remain uncommitted in the worktree — the user's in-progress work was NOT committed. First commit attempt accidentally included them (`git commit -- <path>` commits worktree state, not index); fixed via `git reset --soft HEAD~1` + re-staging the filtered patch.
- `git commit -- <pathspec>` gotcha recorded: pathspec commits ignore the index.
- No `eslint` config in repo — `node --check` used as JS syntax gate.
- iOS toast z-index (9995 vs landing 9999) open question from design remains unverified (opportunistic, out of scope).
