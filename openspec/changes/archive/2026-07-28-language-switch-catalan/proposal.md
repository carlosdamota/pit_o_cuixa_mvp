# Proposal: Fix Language Switch to Catalan

## Intent

i18n spec (I-001) mandates `ca` as default locale. But `.env` sets `DEFAULT_LOCALE=es`, so first-time visitors get Spanish. Two more bugs: `api-client.js` blocks `'ca'` from the `X-Locale` header, and the FAQ `/{lang}/faq` route bypasses bootstrap locale resolution, creating a `LANG` constant mismatch.

## Scope

### In Scope
- Fix `.env` and `.env.example` to set `DEFAULT_LOCALE=ca`
- Fix `api-client.js` `getLocale()` to accept `'ca'`
- Fix FAQ `/{lang}/faq` handler to use bootstrap locale resolution instead of inline override

### Out of Scope
- Bug 4: Server `X-Locale` header consumption (LOW — dead path)
- Bug 5: `nav.php` regex edge case with `lang` as first query param (LOW — cosmetic)

## Capabilities

### New Capabilities
_None_

### Modified Capabilities
- `i18n`: Fix default locale enforcement, JS locale whitelist, and FAQ route locale handling to match spec I-001/I-002

## Approach

| Bug | File | Fix |
|-----|------|-----|
| 1 — Default locale | `.env`, `.env.example` | Change `DEFAULT_LOCALE=ca`, update comment to `ca\|es\|en` |
| 2 — JS locale block | `public/js/api-client.js:18-31` | Add `'ca'` to both whitelist checks in `getLocale()` |
| 3 — FAQ stale LANG | `public/index.php:166-183` | Replace inline translation merge with redirect to `/faq?lang=$lang` |

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `.env` | Modified | `DEFAULT_LOCALE` value `es` → `ca` |
| `.env.example` | Modified | Same default + comment update |
| `public/js/api-client.js` | Modified | `getLocale()` whitelist adds `'ca'` |
| `public/index.php` | Modified | FAQ `/{lang}/faq` handler simplified |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Existing users with `lang=es` cookie see no change | Low | Cookie already set — only new visitors affected |
| FAQ route removal breaks bookmarks | Low | Redirect to `/faq?lang=$lang` instead of deleting |
| `.env` change requires manual prod update | Med | Document in PR; `.env.example` as reminder |

## Rollback Plan

Revert the feature branch merge. All 3 fixes are independent — any single fix can be cherry-picked or reverted without affecting the others.

## Dependencies

_None — all changes are within existing codebase, no external dependencies._

## Success Criteria

- [ ] First-time visitor (no cookie, no `?lang=`) sees Catalan UI
- [ ] `api-client.js` sends `X-Locale: ca` when `<html lang="ca">`
- [ ] FAQ page at `/ca/faq`, `/es/faq`, `/en/faq` renders correct locale strings
- [ ] `LANG` constant matches displayed locale on all pages including FAQ
- [ ] Existing `lang=es` cookie users still see Spanish (no regression)
