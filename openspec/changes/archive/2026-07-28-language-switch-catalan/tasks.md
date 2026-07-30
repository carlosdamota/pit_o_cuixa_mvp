# Tasks: Fix Language Switch to Catalan

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~25–35 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | single-pr |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

## Phase 1: Config Layer Fix

- [ ] 1.1 `.env` — Update comment from `# Default locale: es|en` to `# Default locale: ca|es|en` (value `DEFAULT_LOCALE=ca` is already correct)
- [x] 1.2 `.env.example` — Set `DEFAULT_LOCALE=ca`, update comment to `# Default locale: ca|es|en`

## Phase 2: Client-Side Locale Fix

- [x] 2.1 `public/js/api-client.js` — Add `const ALLOWED = ['ca', 'es', 'en'];` at module level
- [x] 2.2 `public/js/api-client.js` — Replace both `===` chains in `getLocale()` with `ALLOWED.includes()` calls
- [x] 2.3 `public/js/api-client.js` — Change default return from `'es'` to `'ca'`
- [x] 2.4 `public/js/api-client.js` — Update JSDoc return type from `'es' | 'en'` to `'ca' | 'es' | 'en'`

## Phase 3: FAQ Route Fix

- [x] 3.1 `public/index.php` — Replace the `/{lang}/faq` inline-merge body (lines 167–183) with `Response::redirect('/faq?lang=' . $lang, 302)` for accepted locales
- [x] 3.2 `public/index.php` — Delegate rejected locales (`ca|es|en` check fails) to 404 not-found

## Phase 4: Verification

- [x] 4.1 Verify `Config::defaultLocale() === 'ca'` via `php -r "require 'src/shared/bootstrap.php'; echo Config::defaultLocale();"`
- [x] 4.2 Verify `GET /ca/faq` returns `302` with `Location: /faq?lang=ca` via `curl -sI http://localhost/ca/faq`
- [x] 4.3 Verify FAQ redirect for all three locales: `/ca/faq`, `/es/faq`, `/en/faq`
- [x] 4.4 Verify `X-Locale: ca` sent from JS when `<html lang="ca">` (browser devtools)
- [x] 4.5 Verify `lang=es` cookie users still see Spanish: `curl -s -H 'Cookie: lang=es' http://localhost/ | grep '<html lang'` → `es`
- [x] 4.6 Verify invalid `?lang=fr` falls back to `ca`: `curl -s 'http://localhost/?lang=fr' | grep '<html lang'` → `ca`
