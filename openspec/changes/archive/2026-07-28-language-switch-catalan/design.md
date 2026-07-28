# Design: Fix Language Switch to Catalan

## Technical Approach

Three independent single-touch fixes targeting the i18n boot path described in spec I-001/I-007/I-008:

1. **Config layer (env):** flip `DEFAULT_LOCALE` from `es` to `ca` so the existing `Config::defaultLocale() → LANG` resolution in `src/shared/bootstrap.php:49-65` yields Catalan without code changes.
2. **Client layer (JS):** accept the full `ca|es|en` whitelist in `api-client.js getLocale()` so the `X-Locale` request header stops blocking Catalan. Refactor the duplicated `===` chains into one constant to remove the drift that caused this bug.
3. **Route layer (PHP):** replace the FAQ `/{lang}/faq` inline-merge handler with an HTTP 302 redirect to `/faq?lang={lang}` so the request re-enters the bootstrap locale resolver and the `LANG` constant matches the loaded translations.

All three fixes are isolated to distinct files; any one can ship or revert independently.

## Architecture Decisions

| # | Decision | Choice | Alternatives | Rationale |
|---|----------|--------|--------------|----------|
| D1 | Default-locale source of truth | Edit `.env`/`.env.example` value only | Patch `Config::defaultLocale()` to ignore env when invalid | `Config` already declares `ca` as fallback. The bug is data, not code — fix at data layer; PHP behaviour is already correct. |
| D2 | JS whitelist representation | Single module-level `const ALLOWED = ['ca','es','en']` reused in both validation sites | Add `'ca'` to the two existing `===` chains | The duplicated checks were the root cause (one site can drift from the other). One constant makes drift impossible and keeps the diff small. |
| D3 | FAQ route handler | 302 redirect to `/faq?lang={lang}` via `Response::redirect()` | Keep inline merge but also `define('LANG', $lang)` / re-run bootstrap / rewrite as a 404 | Bootstrap defines `LANG` once and cannot be re-defined in PHP. Redirect is the minimal mechanism that routes the request through the existing, correct resolution path; canonical URLs in `Faq::render()` already point to `/{lang}/faq`, so users can still reach those. |

## Data Flow

After fix D3:

    Browser → GET /ca/faq ─▶ Router
        │  resolve /{lang}/faq  (lang=ca)
        └── handler: Response::redirect('/faq?lang=ca', 302)
                │  Location header
                ▼
    Browser → GET /faq?lang=ca ─▶ Bootstrap
        │  $_GET['lang']=ca  →  setcookie('lang','ca')
        │  define('LANG','ca')
        │  load i18n/ca.php → merge {en, ca, ca}
        └── Faq::render()  →  LANG==='ca', translations consistent

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `.env` | Modify | `DEFAULT_LOCALE=es` → `ca`; comment `# Default locale: es\|en` → `# Default locale: ca\|es\|en` |
| `.env.example` | Modify | Same change as `.env` (template stays in sync) |
| `public/js/api-client.js` | Modify | Add module const `ALLOWED`; `getLocale()` validates `htmlLang` and `params.get('lang')` against `ALLOWED`; default return `'ca'` (aligns with `Config::defaultLocale()`); updated JSDoc to `'ca' \| 'es' \| 'en'` |
| `public/index.php` | Modify | Replace the `/{lang}/faq` inline-merge closure body (lines 167-183) with `Response::redirect` to `/faq?lang=$lang` for accepted locales; delegate to 404 for rejected langs (routes are not the security boundary) |

## Interfaces / Contracts

No new public interfaces. The only contract change is `api-client.js getLocale()` return domain widening from `'es'|'en'` to `'ca'|'es'|'en'`, plus default flip from `'es'` to `'ca'`.

## Testing Strategy

No PHPUnit / Composer / automated suite in the project — strategy is manual smoke + optional bash script, all runnable on a feature branch without committing harness.

| Layer | What to Verify | Approach |
|-------|----------------|----------|
| Config | `Config::defaultLocale() === 'ca'` | `php -r "require 'src/shared/bootstrap.php'; echo Config::defaultLocale();"` from project root |
| Bootstrap | First-time visitor (no cookie, no `?lang=`) sees Catalan | `curl -sI -H 'Cookie: ' http://localhost/` then inspect `<html lang>` and `LANG`-driven strings |
| JS whitelist | `X-Locale: ca` sent when `<html lang="ca">` | Open browser devtools network tab; trigger any `apiFetch` call; verify request header |
| FAQ redirect | `GET /ca/faq` returns `302` with `Location: /faq?lang=ca` | `curl -sI http://localhost/ca/faq`; repeat for `/es/faq`, `/en/faq` |
| FAQ LANG consistency | After redirect, `Faq::render()` canonical matches locale | `curl -s 'http://localhost/faq?lang=es' \| grep canonical`; expect `/es/faq` |
| Regression | `lang=es` cookie users still see Spanish | `curl -s -H 'Cookie: lang=es' http://localhost/ \| grep '<html lang'` returns `es` |
| Invalid input | `?lang=fr` falls back to `ca` | `curl -s 'http://localhost/?lang=fr' \| grep '<html lang'` returns `ca` |

## Migration / Rollout

- **Data migration:** none. Static file edits only.
- **Production `.env`:** `.env` is gitignored. PR must include a deploy note: operators must mirror the `DEFAULT_LOCALE=ca` change in the production `.env`. `.env.example` is the reminder.
- **Bookmarks:** `/{lang}/faq` URLs still resolve (via redirect), so existing links/bookmarks remain valid — no link-rot.
- **Rollback:** revert the feature-branch merge. Fixes are independent; any subset can be cherry-picked.

## Open Questions

- None blocking. The deferred bugs 4 (server `X-Locale` consumption) and 5 (`nav.php` regex) remain out of scope per proposal.