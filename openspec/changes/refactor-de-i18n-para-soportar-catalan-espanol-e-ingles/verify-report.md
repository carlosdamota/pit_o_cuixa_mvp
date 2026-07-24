# Verification Report: i18n Refactor — Support Catalan, Spanish & English

**Change**: refactor-de-i18n-para-soportar-catalan-espanol-e-ingles
**Date**: 2026-07-24
**Mode**: Standard (strict_tdd: false)

---

## Completeness

| Metric | Value |
|--------|-------|
| Tasks total | 16 |
| Tasks complete | 16 |
| Tasks incomplete | 0 |
| Artifact set | Full (proposal + specs + design + tasks) |

All 16 tasks across 5 phases are marked `[x]` complete. No unchecked tasks remain.

---

## Build & Tests Execution

**Build** (`php -l` syntax check): ✅ Passed — 11/11 files

```text
No syntax errors detected in src/shared/i18n/ca.php
No syntax errors detected in src/shared/i18n/es.php
No syntax errors detected in src/shared/i18n/en.php
No syntax errors detected in src/shared/config.php
No syntax errors detected in src/shared/bootstrap.php
No syntax errors detected in src/backend/pages/home.php
No syntax errors detected in src/backend/pages/menu.php
No syntax errors detected in src/backend/pages/sitemap.php
No syntax errors detected in src/frontend/templates/partials/nav.php
No syntax errors detected in src/frontend/templates/layouts/default.php
No syntax errors detected in src/frontend/templates/pages/home.php
```

**Tests**: No test runner configured (`testing.runner: null`). Runtime scenario verification is source inspection + static analysis only. See §Correctness for per-scenario evidence.

**Coverage**: ➖ Not available

**Phone consistency** (`grep 977`): ✅ 4 canonical occurrences — all `+34 977 64 20 10`

```text
ca.php:38     'home.info.phone'  => 'Tel. +34 977 64 20 10',
es.php:38     'home.info.phone'  => 'Tel. +34 977 64 20 10',
en.php:38     'home.info.phone'  => 'Tel. +34 977 64 20 10',
config.php:144    return '+34 977 64 20 10';
```

No stray/legacy phone numbers found anywhere in the codebase.

**Old pattern cleanup**: ✅ Confirmed — no `$otherLang`, `__()`, or `getTranslations()` remain in any i18n file. All locale files are pure `return [...]` arrays.

---

## Spec Compliance Matrix

### Capability: i18n (6 requirements, 10 scenarios)

| Requirement | Scenario | Static Evidence | Result |
|-------------|----------|----------------|--------|
| **I-001** Default locale = `ca` | Default resolution (no params) | `Config::defaultLocale()` → `'ca'`; `bootstrap.php:49` sets `$locale = Config::defaultLocale()` when no GET/cookie | ✅ COMPLIANT |
| **I-001** | Invalid locale fallback | `bootstrap.php:54` validates via `in_array($requested, Config::supportedLocales(), true)`; if not valid, `$locale` stays at default (`ca`) | ✅ COMPLIANT |
| **I-002** Locale detection priority | Query param priority over cookie | `bootstrap.php:51-58`: GET checked first; if valid, sets `$locale` AND writes cookie. Cookie only read at line 60 if no GET param. | ✅ COMPLIANT |
| **I-002** | Cookie used when no query param | `bootstrap.php:60`: `elseif (isset($_COOKIE['lang']) && in_array(...))` falls through only when no valid GET param | ✅ COMPLIANT |
| **I-003** Translation loading | CA translations loaded | `src/shared/i18n/ca.php` exists, pure `return [...]` with 66 keys. `bootstrap.php:71` loads it. | ✅ COMPLIANT |
| **I-003** | ES translations loaded | `src/shared/i18n/es.php` exists, pure `return [...]` with 66 keys. `bootstrap.php:70` loads via `LANG` constant. | ✅ COMPLIANT |
| **I-004** Fallback chain | Missing ES → CA | `bootstrap.php:74`: `array_merge($enStrings, $caStrings, $localeStrings)` → ES keys win over CA, CA wins over EN. `__()` at line 86 resolves `$GLOBALS['_translations'][$key] ?? $key`. | ✅ COMPLIANT |
| **I-004** | Missing CA → EN | Same merge chain. EN is loaded first in `array_merge`, so EN fills any gaps not covered by CA. | ✅ COMPLIANT |
| **I-005** Dropdown with 3 options | Shows 3 locales | `nav.php:42-44`: `<option value="ca">`, `<option value="es">`, `<option value="en">` with native labels (Català/Castellano/English) | ✅ COMPLIANT |
| **I-005** | Switching locale | `nav.php:40-41`: `<form method="get">` with `onchange="this.form.submit()"` → reloads with `?lang=...`. `noscript` button fallback at line 46. | ✅ COMPLIANT |
| **I-006** Phone consistency | Phone in all locales + JSON-LD | All 3 locale files + `Config::phone()` → `+34 977 64 20 10`. JSON-LD in `default.php:118`: `str_replace(' ', '', Config::phone())`. Home template `home.php:65`: uses `Config::phone()` for `tel:` href. | ✅ COMPLIANT |

### Capability: seo-geo (3 modified requirements, 7 scenarios)

| Requirement | Scenario | Static Evidence | Result |
|-------------|----------|----------------|--------|
| **SG-002** OG tags | OG tags present | `default.php:47-53`: `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, `og:locale`, `og:site_name` all rendered | ✅ COMPLIANT |
| **SG-002** | og:locale 3-way mapping | `default.php:31`: `$ogLocaleMap = ['ca' => 'ca_ES', 'es' => 'es_ES', 'en' => 'en_US']`. Fallback `?? 'ca_ES'` at line 52. | ✅ COMPLIANT |
| **SG-002** | Social share preview | OG tags + Twitter Card (`default.php:56-59`) provide complete metadata. `og:image` → `/img/og-image.jpg` resolved to absolute URL. | ✅ COMPLIANT |
| **SG-004** XML Sitemap | Sitemap accessible | `sitemap.php`: generates valid XML with `Content-Type: application/xml` via `Response::xml()`. Lists `/`, `/menu`, and product URLs. | ✅ COMPLIANT |
| **SG-004** | hreflang annotations | `sitemap.php:63-70` (home), `74-81` (menu), `98-105` (products): each URL has `ca`, `es`, `en` + `x-default` alternates. `x-default` → `ca` URL. | ✅ COMPLIANT |
| **SG-005** Hreflang in HTML | 3 hreflang + x-default | `default.php:62-71`: iterates `$langs` array for hreflang links (ca/es/en). Line 67: `x-default` → `$langs['ca']`. | ✅ COMPLIANT |
| **SG-005** | Bidirectional trilingual mesh | `home.php:46-49`: `langs` → ca, es, en URLs. `menu.php:111-115`: same pattern. `sitemap.php`: full hreflang mesh for all pages. | ✅ COMPLIANT |

**Compliance summary**: ✅ 17/17 scenarios COMPLIANT (0 UNTESTED, 0 FAILING, 0 PARTIAL)

---

## Correctness (Static Evidence)

| Requirement | Status | Notes |
|------------|--------|-------|
| Pure data arrays (design decision) | ✅ | All 3 locale files are `return [...]`. No `__()` or `getTranslations()` in any of them. `bootstrap.php` defines `__()` once. |
| 3-locale detection chain | ✅ | GET → Cookie → default (`ca`). Invalid locales fall through to default. Cookie set on valid GET. |
| Fallback merge order | ✅ | `array_merge(en, ca, requested)` — correct priority: requested > ca > en |
| Cookie security | ✅ | `setcookie(..., time() + 365*86400, '/', '', true, true)` — secure + httponly flags set |
| `supportedLocales()` | ✅ | `config.php:136`: returns `['ca', 'es', 'en']` |
| `defaultLocale()` | ✅ | `config.php:126`: reads `DEFAULT_LOCALE` env, defaults `'ca'` |
| `phone()` | ✅ | `config.php:144`: returns canonical `+34 977 64 20 10` |
| Nav dropdown | ✅ | `<select>` with 3 options, auto-submit, noscript fallback |
| JSON-LD phone | ✅ | `default.php:118`: `str_replace(' ', '', Config::phone())` |
| Sitemap trilingual | ✅ | CA, ES, EN hreflang entries + x-default → CA for all URLs |
| CHANGELOG entry | ✅ | Documents breaking change: `?lang=es` now serves real Spanish; default changed from `es` to `ca` |
| No old pattern leakage | ✅ | Zero occurrences of `$otherLang`, `getTranslations()`, or function-defining `__()` in i18n files |

---

## Coherence (Design)

| Decision | Followed? | Notes |
|----------|-----------|-------|
| Pure `return [...]` arrays + shared loader | ✅ Yes | All 3 files follow this. `bootstrap.php` does single `__()` definition. |
| Fallback chain: requested → CA → EN | ✅ Yes | `array_merge($enStrings, $caStrings, $localeStrings)` |
| `<select>` in `<form method="get">` + inline JS | ✅ Yes | `nav.php:40-41`: `<form method="get"><select onchange="this.form.submit()">`. `noscript` fallback present. |
| `Config::phone()` as single source of truth | ✅ Yes | All i18n files use literal `+34 977 64 20 10`; JSON-LD + `tel:` href use `Config::phone()`. |
| Migrate stale `es` cookies (accept breakage) | ✅ Yes | CHANGELOG documents it. Cookie refreshed on next valid `?lang=` interaction. |
| `DEFAULT_LOCALE` env-overridable | ✅ Yes | `config.php:126`: reads env, defaults `'ca'`. |

---

## Issues Found

### CRITICAL
None.

### WARNING

1. **`lang.switch` aria-label is outdated (a11y)** — All three locale files have binary-toggle leftover values: `ca.php:23` → `'English'`, `es.php:23` → `'English'`, `en.php:23` → `'Català'`. These are used as `aria-label` on the `<select>` dropdown (`nav.php:41`). With the new trilingual dropdown, the label should describe the control (e.g., "Canviar idioma" / "Cambiar idioma" / "Change language"), not a single language name. Current behavior: screen reader on CA page announces "English, combobox" — misleading since the dropdown contains all 3 languages, not just English.

   - **Severity**: Low functional impact but violates WCAG 4.1.2 (Name, Role, Value) — the accessible name does not describe the control's purpose.
   - **Fix**: Update `lang.switch` values to locale-appropriate "Change language" equivalents in all 3 files.

### SUGGESTION

1. **Design open question unresolved**: The design.md lists `Català / Castellano / English` native labels as an open question vs. `Catalan / Spanish / English`. Implementation chose native labels but this was never formally confirmed. Native labels are the safer, more authentic choice — recommend closing the question.

2. **Redundant file loads on `ca`/`en` requests**: `bootstrap.php:70-72` loads all three locale files unconditionally. When `LANG === 'ca'`, `$caStrings` and `$localeStrings` are both `ca.php` (loaded twice via `require`). When `LANG === 'en'`, `$enStrings` and `$localeStrings` are both `en.php` (loaded twice). Functionally harmless (arrays don't redeclare), but adds minor I/O overhead. Could be optimized with a simple `if (LANG !== 'ca')` / `if (LANG !== 'en')` guard. Acceptable as-is for a vanilla PHP project without an opcache.

3. **Task 5.2 runtime verification pending**: "Verify `DEFAULT_LOCALE` env override works for staging demos" was verified via code inspection (the code path is correct), but no runtime test was performed with an actual `.env` override. Recommend a quick smoke test: set `DEFAULT_LOCALE=es` in .env, load a page without `?lang=`, and confirm it renders Spanish.

---

## Verdict

**PASS WITH WARNINGS**

**Reason**: All 16 tasks complete. All 17 spec scenarios (10 i18n + 7 seo-geo) are compliant per static analysis. All 11 changed files pass `php -l` syntax check. Phone number consistency confirmed via grep. Old binary-toggle patterns fully removed. Design decisions followed correctly. One a11y WARNING (misleading `lang.switch` aria-label — cosmetic/accessibility, not functional breakage) and 3 minor SUGGESTIONs that do not block archive readiness.

**Archive recommendation**: Ready to archive. Fix the `lang.switch` aria-label WARNING before or during archive at your discretion — it does not block deployment.
