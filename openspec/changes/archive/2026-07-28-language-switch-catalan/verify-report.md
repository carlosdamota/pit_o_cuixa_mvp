
## Verification Report

**Change**: language-switch-catalan
**Branch**: fix/language-switch-catalan (HEAD: c92081a)
**Mode**: Standard
**Date**: 2026-07-28

### Completeness
| Metric | Value |
|--------|-------|
| Tasks total | 10 (3 phases, 6 verification) |
| Tasks complete | 9 |
| Tasks incomplete | 1 (task 1.1: `.env` comment not updated) |

### Runtime Evidence
**Config Verification**:
✅ `Config::defaultLocale() === 'ca'` — verified via `php -r`
```text
ca
```

**Database Verification** (via PDO):
```text
Products: 48/48 have Catalan name, 48/48 have Catalan description
Categories: 11/11 have Catalan name
```

### Spec Compliance Matrix
| Requirement | Scenario | Evidence | Result |
|-------------|----------|----------|--------|
| I-001 MODIFIED | Default locale resolution | bootstrap.php:49-62 — no lang/cookie → `Config::defaultLocale()` → verified returns `ca` | ✅ COMPLIANT |
| I-001 MODIFIED | Invalid locale fallback | bootstrap.php:51-58 — `in_array($requested, Config::supportedLocales())` rejects `fr`, falls to default | ✅ COMPLIANT |
| I-001 MODIFIED | Environment enforces Catalan default | `.env`: `DEFAULT_LOCALE=ca` ✅; `.env.example`: `DEFAULT_LOCALE=ca` ✅; `.env` comment: `es\|en` ❌ → should be `ca\|es\|en` | ⚠️ PARTIAL |
| I-007 ADDED | JS sends Catalan locale header | api-client.js:22-24 — `htmlLang` read from `<html lang>`, validated via `ALLOWED.includes()` | ✅ COMPLIANT |
| I-007 ADDED | JS locale whitelist validation | api-client.js:13 — `const ALLOWED = ['ca', 'es', 'en']`; line 23, 29 use `ALLOWED.includes()` | ✅ COMPLIANT |
| I-007 ADDED | HTML lang attribute fallback | api-client.js:22-24 — `document.documentElement.getAttribute('lang')` → `ALLOWED.includes(htmlLang)` → return | ✅ COMPLIANT |
| I-008 ADDED | FAQ route redirects to query param | index.php:170-174 — `Response::redirect('/faq?lang=' . $lang, 302)` for accepted locales | ✅ COMPLIANT |
| I-008 ADDED | FAQ preserves locale across all 3 languages | index.php:170 — `in_array($lang, ['ca', 'es', 'en'], true)` covers all three | ✅ COMPLIANT |
| I-008 ADDED | LANG constant consistency on FAQ page | Redirect re-enters bootstrap → LANG defined via same resolution chain as all other pages | ✅ COMPLIANT |

**Compliance summary**: 8/9 scenarios fully compliant, 1 partial (`.env` comment)

### Correctness (Static Evidence)
| Requirement | Status | Notes |
|------------|--------|-------|
| `.env` DEFAULT_LOCALE=ca | ✅ Correct | Value is `ca` |
| `.env` comment listing locales | ⚠️ Partial | Comment says `es\|en` instead of `ca\|es\|en` |
| `.env.example` DEFAULT_LOCALE=ca | ✅ Correct | Value is `ca`, comment says `ca\|es\|en` |
| `api-client.js` ALLOWED constant | ✅ Correct | `['ca', 'es', 'en']` at module level |
| `api-client.js` getLocale() whitelist | ✅ Correct | Uses `ALLOWED.includes()` for both checks |
| `api-client.js` default return | ✅ Correct | Returns `'ca'` |
| `api-client.js` JSDoc | ✅ Correct | `'ca' \| 'es' \| 'en'` |
| `index.php` FAQ redirect | ✅ Correct | 302 redirect to `/faq?lang=` + locale |
| `index.php` FAQ locale validation | ✅ Correct | `in_array($lang, ['ca', 'es', 'en'], true)` |
| `index.php` FAQ unknown locale 404 | ✅ Correct | Unrecognised prefix → 404 |
| `Config::defaultLocale()` | ✅ Correct | Returns `DEFAULT_LOCALE` or `'ca'` |
| `Config::supportedLocales()` | ✅ Correct | `['ca', 'es', 'en']` |
| `bootstrap.php` locale resolution | ✅ Correct | `?lang=` → cookie → default, all validated |
| `db/schema.sql` Catalan fields | ✅ Correct | `name_ca`, `description_ca` columns present |
| `db/migrations/003` | ✅ Correct | Migration adds Catalan fields |
| Database population | ✅ Correct | 48 products + 11 categories with Catalan translations |

### Coherence (Design)
| Decision | Followed? | Notes |
|----------|-----------|-------|
| D1: Edit `.env`/`.env.example` only (data-layer fix) | ✅ Yes | `DEFAULT_LOCALE=ca` in both; `.env.example` updated |
| D2: Single `ALLOWED` constant for JS whitelist | ✅ Yes | `const ALLOWED = ['ca', 'es', 'en']` at module level, reused in both validation sites |
| D3: 302 redirect for FAQ route | ✅ Yes | `Response::redirect('/faq?lang=' . $lang, 302)` with proper locale validation |

All 3 architecture decisions followed precisely.

### Issues Found
**CRITICAL**: None

**WARNING**:
- **W-1**: `.env` line 18 comment says `# Default locale: es|en` but should be `# Default locale: ca|es|en` per Task 1.1 and Spec I-001 scenario. The value `DEFAULT_LOCALE=ca` is correct, but the comment is misleading and violates the spec requirement that the comment SHALL document `ca|es|en` as valid options. Fix: change line 18 from `# Default locale: es|en` to `# Default locale: ca|es|en`.

**SUGGESTION**: None

### Files Verified
| File | Status | Notes |
|------|--------|-------|
| `.env` | ⚠️ Warning | Value correct, comment outdated |
| `.env.example` | ✅ Pass | Both value and comment correct |
| `public/js/api-client.js` | ✅ Pass | All tasks 2.1-2.4 implemented |
| `public/index.php` | ✅ Pass | Tasks 3.1-3.2 implemented |
| `src/shared/Config.php` | ✅ Pass | Correct fallback and supported list |
| `src/shared/bootstrap.php` | ✅ Pass | Proper locale resolution chain |
| `src/shared/i18n/ca.php` | ✅ Pass | 157-line Catalan translation file |
| `db/schema.sql` | ✅ Pass | `name_ca`, `description_ca` fields |
| `db/migrations/003-add-catalan-fields.sql` | ✅ Pass | Proper migration |
| `data/pitocuixa.db` | ✅ Pass | 48 products + 11 categories with ca data |

### Verdict
**PASS WITH WARNINGS**

One minor documentation issue: `.env` comment was not updated to reflect supported locales (`ca|es|en`). The runtime behavior is fully correct — all three spec requirements (I-001, I-007, I-008) are implemented and functional. The issue is cosmetic/documentation only and does not block archival.
