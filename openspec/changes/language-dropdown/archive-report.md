# Archive Report: Language Dropdown Change

## Change Information
- **Change Name**: `language-dropdown`
- **Archived Date**: 2026-08-07
- **Artifact Store**: hybrid (OpenSpec + Engram)
- **Status**: PASS (verification complete)

## Executive Summary
The `language-dropdown` change replaced the inline 4-flag row in the home footer with a custom dropdown matching the existing header pattern. The implementation spans three files (home.php markup, home.css grid/upward styles, main.js multi-instance hardening) across 5 commits. Verification confirmed 11/11 requirements and 15/15 scenarios compliant via code inspection with 0 CRITICAL, 0 WARNING, 3 SUGGESTION findings. All implementation and verification tasks are complete.

## Commits
| Commit | Message | Files |
|--------|---------|-------|
| `787e386` | `feat(home): replace flag row with language dropdown` | home.php |
| `d9bc01c` | `feat(home): grid footer + upward dropdown styles` | home.css |
| `dd2a8fb` | `fix(js): harden initLangDropdown for multi-instance` | main.js |
| `22aafce` | `fix(home): match footer dropdown toggle to header style and fix grid column positioning` | home.php, home.css |
| `f5f147a` | `fix(home): restore square favicon dimensions for footer dropdown flags` | home.css |

## Files Changed
- `src/frontend/templates/pages/home.php` — markup replacement with `[data-lang-dropdown]`
- `src/frontend/css/pages/home.css` — grid `1fr auto 1fr`, `.onboarding__lang-*` upward menu
- `public/js/main.js` — `initLangDropdown()` multi-instance via `querySelectorAll`

## Requirements Satisfied (11/11)
- **LS-001**: Disclosure Toggle — `aria-haspopup="listbox"`, `aria-expanded`, `aria-controls`
- **LS-002**: Menu Options — 4 `<a>` links with `aria-current="true"`, `?lang=` navigation
- **LS-003**: Close on Outside Click — document handler closes only open instance
- **LS-004**: Close on Escape — closes + returns focus to toggle
- **LS-005**: Multiple Instances — header + footer coexist independently
- **LS-006**: Footer Dropdown Positioning — upward (`bottom: calc(100% + 8px); right: 0`)
- **LS-007**: Footer Grid Layout — `1fr auto 1fr` with CTA symmetry preservation
- **LS-008**: Touch Target — 44×44px minimum on toggle
- **LS-009**: Reduced Motion — `prefers-reduced-motion: reduce` disables animation
- **LS-010**: Narrow Viewport — 360px `uk` no overflow + ellipsis via `min-width: 0`
- **LS-011**: No-JS Fallback — menu stays `hidden` without JS

## Verification Summary
| Metric | Result |
|--------|--------|
| Build (PHP lint + JS syntax) | ✅ Passed |
| Tests (php scripts/test-sync.php) | ✅ 58 passed, 0 failed |
| Spec compliance matrix | ✅ 15/15 scenarios verified |
| Design coherence | ✅ 5/5 decisions followed |
| Regression (header + other pages) | ✅ No impact |
| Accessibility (code inspection) | ✅ All checks pass (1 minor ARIA semantic note) |
| Visual QA (code inspection) | ✅ All checks pass |

**Verdict**: PASS — All requirements met, no blockers, 3 low-priority suggestions only.

## Suggestions (Non-blocking)
1. **Semantic ARIA mismatch**: Toggle uses `aria-haspopup="listbox"` but menu contains `<a>` links rather than `role="option"` — functional, matches header pattern, low priority.
2. **No automated browser tests**: Manual matrix relies on code inspection; consider Cypress/Playwright for future.
3. **Pre-existing uncommitted work**: Two unrelated hunks in `home.php` remain uncommitted (design task).

## Archive Location
- **Engram**: `sdd/language-dropdown/archive-report` (observation #886)
- **OpenSpec**: `openspec/changes/archive/2026-08-07-language-dropdown/`

## SDD Cycle Complete
The change has been fully planned, implemented, verified, and archived. Ready for the next change.