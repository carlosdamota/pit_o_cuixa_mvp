## Exploration: Language Switch to Catalan Fails

### Current State

The i18n system was refactored (commit `d05330a`) to support 3 locales (CA/ES/EN) with proper fallback chain. The code structure looks correct:
- `src/shared/i18n/ca.php`, `es.php`, `en.php` — all exist with proper translations
- `Config::supportedLocales()` returns `['ca', 'es', 'en']`
- `Config::defaultLocale()` code default is `'ca'`
- `bootstrap.php` properly detects locale from `?lang=` → cookie → default
- SSR renders correctly in all 3 locales
- Language switcher in UI has 3 options: desktop `<select>` form + mobile direct links
- Cookie is set when user switches languages

**However**, the system has accumulated bugs that cause the language switch to fail in specific scenarios.

### Root Cause

**.env** (and `.env.example`) have `DEFAULT_LOCALE=es` and the comment says `# Default locale: es|en` — excluding `'ca'` entirely. This is stale from before the i18n refactor.

`Config::defaultLocale()` reads the env var: `self::get('DEFAULT_LOCALE', 'ca')` — so the env override wins. First-time visitors without a cookie see Spanish, not Catalan.

### Bugs Found

#### Bug 1: `.env` sets default locale to Spanish (PRIMARY — likely the user's complaint)

- **File**: `.env` (and `.env.example`)
- **Line**: `DEFAULT_LOCALE=es` (comment: `# Default locale: es|en`)
- **Impact**: First-time visitors see Spanish. Users have to manually switch to Catalan every new session.
- **Severity**: HIGH — this is the most likely reason the user says "el cambio de idioma a catalán falla"

#### Bug 2: `api-client.js` `getLocale()` explicitly blocks `'ca'`

- **File**: `public/js/api-client.js`, lines 18-31
- **Code**:
  ```javascript
  function getLocale() {
    const htmlLang = document.documentElement.getAttribute('lang');
    if (htmlLang === 'es' || htmlLang === 'en') { return htmlLang; } // ← 'ca' excluded
    const params = new URLSearchParams(window.location.search);
    const lang = params.get('lang');
    if (lang === 'es' || lang === 'en') { return lang; } // ← 'ca' excluded
    return 'es'; // ← hardcoded Spanish fallback
  }
  ```
- **Impact**: Currently **latent** — `api-client.js` is never imported by any module (`main.js` only imports `menu-filter.js` and `menu-slider.js`). But if anything starts using the API client, Catalan data fetching will break immediately.
- **Severity**: MEDIUM (latent, but will bite)

#### Bug 3: FAQ `/{lang}/faq` path handler has stale `LANG` constant

- **File**: `public/index.php`, lines 166-183
- **Problem**: The handler sets `$_GET['lang']` and reloads translations, but `LANG` was already defined by `bootstrap.php` and **cannot be redefined** (PHP constants). The handler never updates the cookie.
- **Impact**: When visiting `/ca/faq`, `/es/faq`, or `/en/faq`:
  - Translations are reloaded correctly (content OK)
  - But `LANG` constant is stale → canonical URL, `og:locale`, `<html lang>` all use wrong locale
  - Cookie is NOT updated for path-based locale URLs
- **Severity**: MEDIUM — only affects path-based FAQ URLs

#### Bug 4: `bootstrap.php` ignores `X-Locale` HTTP header (design gap)

- **File**: `src/shared/bootstrap.php`, lines 47-65
- **Problem**: The JS `api-client.js` sends `X-Locale` header but the server never reads it. The bootstrap only checks `$_GET['lang']`, `$_COOKIE['lang']`, and default.
- **Impact**: The X-Locale mechanism is dead code. API calls from JS without `?lang=` in the URL and without a cookie always fall back to the default locale.
- **Severity**: LOW — currently masked because `api-client.js` is unused and API calls get locale from cookie or default

#### Bug 5: `nav.php` regex strip can create broken URLs (edge case)

- **File**: `src/frontend/templates/partials/nav.php`, line 15
- **Code**: `$baseUri = preg_replace('/[?&]lang=[a-z]{2}/', '', $baseUri);`
- **Problem**: When `lang` is the FIRST query parameter (e.g., `/?lang=ca&foo=bar`), the regex removes `?lang=ca` leaving orphaned `&foo=bar` → `/&foo=bar`
- **Impact**: Affects mobile language switch links in edge cases where `lang` is first param and other params exist. Desktop form works because browser handles query construction.
- **Severity**: LOW (edge case)

### Affected Areas

| File | Role |
|------|------|
| `.env` | Environment config — sets `DEFAULT_LOCALE=es` (wrong default) |
| `.env.example` | Template — same stale `DEFAULT_LOCALE=es` comment/code |
| `public/js/api-client.js` | JS API client — `getLocale()` excludes `'ca'` from whitelist |
| `src/shared/bootstrap.php` | Bootstrap — locale detection doesn't read `X-Locale` header |
| `public/index.php` (lines 166-183) | FAQ path-based route handler — stale `LANG` constant |
| `src/frontend/templates/partials/nav.php` | Language switcher UI — regex bug in URL stripping |

### Approaches

1. **Minimal fix (just `.env`)** — Change `DEFAULT_LOCALE=ca` and update comments
   - Pros: Fast, addresses the most likely user complaint
   - Cons: Doesn't fix latent bugs in `api-client.js`, FAQ route, or server header reading
   - Effort: **Low** (5 minutes)

2. **Targeted fix (`.env` + `api-client.js` + FAQ route)** — Fix all 3 real bugs
   - Pros: Comprehensive fix for all observable and latent issues
   - Cons: Slightly more work but still manageable
   - Effort: **Low-Medium**

3. **Full fix (all bugs + server reads `X-Locale`)** — Also fix the design gap
   - Pros: Complete end-to-end locale correctness (JS sends → server reads)
   - Cons: Requires modifying both JS and PHP; `X-Locale` header mechanism needs server-side reading in bootstrap
   - Effort: **Medium**

### Recommendation

**Approach 2** — Fix the 3 bugs that matter:
1. Fix `.env` (and `.env.example`) — `DEFAULT_LOCALE=ca`
2. Fix `api-client.js` — add `'ca'` to the locale whitelist and fix the fallback to use a smarter detection (read from `<html lang>` instead of hardcoded `'es'`)
3. Fix the FAQ `/{lang}/faq` route handler — either redefine locale properly or refactor to use a mutable locale variable instead of the `LANG` constant

Bug 4 (server reads X-Locale) is lower priority since `api-client.js` is currently dead code. Bug 5 (regex edge case) is low severity and can be deferred.

### Risks

- `.env` change in dev: only affects local dev. The `.env.example` fix matters for new devs cloning the repo.
- Fixing `api-client.js` is safe since it's dead code — but the fix ensures it works correctly if/when it's imported in the future.
- The FAQ route fix needs care — `LANG` is a PHP constant and can't be redefined. May need to refactor to use a variable or pass locale explicitly to `Faq::render()`.
- No production risk since the SSR i18n system itself works correctly (the i18n refactor was done properly).

### Ready for Proposal

**Yes.** The exploration is complete. The bugs are well-understood. Recommended fix scope:
1. `.env` + `.env.example`: `DEFAULT_LOCALE=ca`
2. `public/js/api-client.js`: add `'ca'` to `getLocale()` whitelist, use `<html lang>` attribute as smarter fallback
3. `public/index.php` FAQ route: make locale detection work for path-based URLs (override LANG or refactor)
