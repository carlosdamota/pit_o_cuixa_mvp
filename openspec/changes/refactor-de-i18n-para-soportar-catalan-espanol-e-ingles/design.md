# Design: i18n Refactor — Support Catalan, Spanish & English

## Technical Approach

Refactor the per-locale file pattern so each locale file is a **pure data array** (`return [...]`) instead of redeclaring `__()` and `getTranslations()`. A single loader in `bootstrap.php` merges the requested locale's array with the fallback (CA → EN), then defines `__()` once. This is required to implement the proposal's fallback chain without PHP `Cannot redeclare function` errors — the current pattern (same `__()`/`getTranslations()` in every file) only works because exactly one file is `require`'d per request.

Maps to proposal steps 1–7: rename es.php→ca.php, new es.php, 3-locale detection, fallback chain, dropdown, og:locale 3-way mapping, phone standardization.

## Architecture Decisions

| Decision | Option | Tradeoff | Chosen |
|---|---|---|---|
| Locale file shape | (A) Keep duplicated `__()` per file | Blocks fallback (redeclaration) | ❌ |
| | (B) Pure `return [...]` arrays + shared loader | Clean, enables fallback, minimal Vanilla PHP | ✅ |
| Fallback chain | requested → CA → EN — matches proposal default-locale logic | Slight bias toward Catalan even for EN visitors missing keys | ✅ |
| Locale switcher UI | Native `<select>` in a `<form method="get">` auto-submitting via tiny inline JS | JS-free needs server redirect glue; JS inline keeps it dependency-free | `<select>` + ~5 lines inline JS |
| Phone source of truth | `Config::phone()` constant + i18n keys referencing it | Decouples value from translation files | ✅ |
| Migration of stale `es` cookies | Accept breakage; `setcookie('lang', ...)` refreshes on next visit | Some users see Spanish once where they expected Catalan | Accept + CHANGELOG note |

## Data Flow

    Request ──► bootstrap.php
                 ├─ Config::supportedLocales() → ['ca','es','en']
                 ├─ detect: $_GET['lang'] ?? $_COOKIE['lang'] ?? Config::defaultLocale()('ca')
                 ├─ validate against supported (else 'ca')
                 ├─ require i18n/{locale}.php   → $localeStrings (array)
                 ├─ require i18n/ca.php         → $fallbackStrings (skip if locale==='ca')
                 ├─ $t = array_merge($fallbackStrings, $localeStrings)
                 └─ define __($k): $t[$k] ?? $k

                 $locale constant LANG ──► default.php (og:locale map, hreflang)
                                        ──► nav.php (dropdown selected option)

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/shared/i18n/ca.php` | Create (rename) | Pure `return [...]` of current Catalan strings (from old es.php). Strip `__()`/`getTranslations()` definitions |
| `src/shared/i18n/es.php` | Rewrite | New Spanish `return [...]` for all ~50 keys. Phone → `Config::phone()` formatted |
| `src/shared/i18n/en.php` | Modify | Convert to pure `return [...]`; remove function defs; phone standardized |
| `src/shared/bootstrap.php` | Modify | Sections 4–5: 3-locale detection, fallback merge loader, define `__()` once; set `lang` cookie on GET change |
| `src/shared/config.php` | Modify | `supportedLocales()→['ca','es','en']`, `defaultLocale()→'ca'`, add `phone(): string` returning `+34 977 64 20 10` |
| `src/frontend/templates/partials/nav.php` | Modify | Replace `$otherLang` binary link with `<form><select>` (3 options: Català/Castellano/English) |
| `src/frontend/templates/layouts/default.php` | Modify | `og:locale` → 3-way map; JSON-LD `telephone` → `Config::phone()`; `x-default` → `$langs['ca']` |
| `src/backend/pages/home.php` | Modify | `langs` array: add `'ca'`, restructure to ca/es/en |
| `src/backend/pages/menu.php` | Modify | Same `langs` update |
| `src/backend/pages/sitemap.php` | Modify | Add `ca` hreflang entry; `x-default` → ca URL |

## Interfaces / Contracts

```php
// Each i18n/{locale}.php is now pure data:
return [
    'site.name' => 'Pit o Cuixa',
    // ... ~50 keys across domains: site, nav, lang, footer, home, menu, product, error, admin
];

// config.php
public static function supportedLocales(): array { return ['ca', 'es', 'en']; }
public static function defaultLocale(): string   { return self::get('DEFAULT_LOCALE', 'ca'); }
public static function phone(): string           { return '+34 977 64 20 10'; }

// og:locale map (default.php)
$ogLocaleMap = ['ca' => 'ca_ES', 'es' => 'es_ES', 'en' => 'en_US'];
```

## Testing Strategy

No PHP test runner exists (`openspec/config.yaml`: `strict_tdd: false`, build = `php -l`).

| Layer | What | Approach |
|-------|------|----------|
| Syntax | All changed files | `php -l` per file (CI `build_command`) |
| Unit (manual) | Fallback chain | Request `?lang=es`, remove a key from es.php temporarily, assert CA value returned |
| Integration | 3 locales render | Manual: hit `/?lang=ca`, `/?lang=es`, `/?lang=en`; verify nav, home body, footer, og:locale, hreflang |
| Regression | Phone consistency | Grep `977` across repo — single canonical format in i18n keys + JSON-LD |
| SEO | og:locale + hreflang | View-source 3 pages: `ca_ES`, `es_ES`, `en_US`; 3 `<link hreflang>` + `x-default`→ca |

## Migration / Rollout

- **Breaking**: `?lang=es` previously served Catalan; now serves Spanish. Stale `es` cookies will load Spanish on next visit. Document in CHANGELOG; `bootstrap.php` refreshes the cookie when a valid `?lang=` is sent.
- **No DB migration**, no feature flags. Single `git revert` rollback (proposal §Rollback Plan).
- Recommend deploying with a brief note on the site's social/landing channel about the language switch.

## Open Questions

- [ ] Native labels in dropdown: `Català / Castellano / English` — confirm preferred (vs. `Catalan / Spanish / English`).
- [ ] Should `defaultLocale()` be env-overridable for staging demos? Current design reads `DEFAULT_LOCALE` env (defaults `ca`).