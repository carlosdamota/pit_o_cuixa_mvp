# Tasks: i18n Refactor — Support Catalan, Spanish & English

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~185–210 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

Not needed — under 400-line budget. Single PR deliverable.

## Phase 1: Foundation — Backend

- [x] 1.1 Create `src/shared/i18n/ca.php` — pure `return [...]` of Catalan strings from old es.php; strip `__()`/`getTranslations()`. (~55 lines)
- [x] 1.2 Rewrite `src/shared/i18n/es.php` — new Spanish `return [...]` for ~50 keys; phone via `Config::phone()`. (~55 lines)
- [x] 1.3 Convert `src/shared/i18n/en.php` — pure `return [...]`; remove function defs; phone standardized. (~5 lines)
- [x] 1.4 Update `src/shared/config.php` — `supportedLocales(): ['ca','es','en']`, `defaultLocale(): 'ca'`, `phone(): '+34 977 64 20 10'`. (~10 lines)

## Phase 2: Core — Backend

- [x] 2.1 Rewrite `src/shared/bootstrap.php` — 3-locale detection (?lang→cookie→default), fallback merge (`array_merge(en, ca, requested)`), define `__()` once; set cookie on GET param. (~30 lines)
- [x] 2.2 Update `src/backend/pages/home.php` — restructure `langs` array to ca/es/en. (~5 lines)
- [x] 2.3 Update `src/backend/pages/menu.php` — same `langs` restructuring. (~5 lines)
- [x] 2.4 Update `src/backend/pages/sitemap.php` — add `ca` hreflang entry; x-default→ca URL. (~10 lines)

## Phase 3: Frontend Integration

- [x] 3.1 Replace nav toggle in `src/frontend/templates/partials/nav.php` — `<form method="get"><select>` (Català/Castellano/English) + inline JS auto-submit. (~15 lines)
- [x] 3.2 Update `src/frontend/templates/layouts/default.php` — 3-way og:locale map (ca→ca_ES, es→es_ES, en→en_US); JSON-LD phone→`Config::phone()`; 3 hreflang links + x-default→ca. (~15 lines)

## Phase 4: Verification

- [x] 4.1 Run `php -l` syntax check on all changed files.
- [x] 4.2 Manual integration: navigate 3 locales (?lang=ca, ?lang=es, ?lang=en), verify nav/body/footer/og:locale/hreflang.
- [x] 4.3 Fallback test: remove key from es.php → CA value surfaces; remove from ca.php → EN value surfaces.
- [x] 4.4 Regression: grep `977` across repo — single canonical `+34 977 64 20 10` in i18n keys + JSON-LD.

## Phase 5: Cleanup

- [x] 5.1 Add CHANGELOG entry about stale `es` cookies now serving Spanish (breaking change noted in design).
- [x] 5.2 Verify `DEFAULT_LOCALE` env override works for staging demos.
