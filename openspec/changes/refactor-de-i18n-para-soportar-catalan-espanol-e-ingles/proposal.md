# Proposal: i18n Refactor — Support Catalan, Spanish & English

## Intent

`src/shared/i18n/es.php` contains **Catalan** strings mislabeled as Spanish. The project claims bilingual ES/EN but actually serves Catalan/EN. Torredembarra is in Catalonia — the correct locale set is **CA, ES, EN**. This change fixes the mislabeling, adds real Spanish translations, and converts the binary language toggle into a 3-locale selector.

## Scope

### In Scope
- Rename `es.php` → `ca.php` (content is already Catalan)
- Create new `es.php` with Spanish translations for all 46+ keys
- Update `Config::supportedLocales()` to return `['ca', 'es', 'en']`
- Update `Config::defaultLocale()` to `'ca'` (Catalan = local default)
- Update locale detection in `bootstrap.php` to accept 3 locales
- Convert binary toggle in `nav.php` to 3-option dropdown
- Fix phone number inconsistency (`977 64 20 10` vs `+34977641805` — standardize to one)
- Update `og:locale` mapping: `ca` → `ca_ES`, `es` → `es_ES`, `en` → `en_US`
- Add locale fallback chain: requested locale → CA → EN

### Out of Scope
- Admin area internationalization (~50+ hardcoded strings) — Phase 2
- Client-side JS string internationalization — Phase 2
- Database-stored product translations (already handled per-locale in DB)
- Hreflang `x-default` target change (stays on CA as local default)

## Capabilities

### New Capabilities
- `i18n`: Locale detection, translation loading, fallback chain, and locale switcher UI for 3 locales (CA, ES, EN)

### Modified Capabilities
- `seo-geo`: `og:locale` must map 3 locales correctly; hreflang annotations need `ca` entry alongside `es` and `en`; `x-default` target updates from `es` to `ca`

## Approach

1. **Rename** `es.php` → `ca.php` (no content changes — strings are already Catalan)
2. **Create** new `es.php` with Spanish translations for all existing keys
3. **Update** `Config`: `supportedLocales()` → `['ca', 'es', 'en']`, `defaultLocale()` → `'ca'`
4. **Update** `bootstrap.php`: locale detection accepts 3 values; add fallback chain
5. **Replace** binary toggle in `nav.php` with `<select>` dropdown (3 options, JS-free via `<form>` or CSS-only)
6. **Fix** `og:locale` ternary in `default.php` to a 3-way mapping
7. **Standardize** phone number across all locale files and JSON-LD

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/shared/i18n/es.php` | Modified | Becomes Catalan content (renamed to `ca.php`) |
| `src/shared/i18n/ca.php` | New | Renamed from `es.php` |
| `src/shared/i18n/es.php` | New | Spanish translations (all 46+ keys) |
| `src/shared/config.php` | Modified | `supportedLocales()`, `defaultLocale()` |
| `src/shared/bootstrap.php` | Modified | 3-locale detection + fallback chain |
| `src/frontend/templates/partials/nav.php` | Modified | Binary toggle → 3-option dropdown |
| `src/frontend/templates/layouts/default.php` | Modified | `og:locale` 3-way mapping |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Breaking `?lang=es` bookmarks/cookies (now Catalan → Spanish swap) | Medium | Accept breakage; document in CHANGELOG. Old `es` cookie now loads Spanish (correct behavior) |
| Missing Spanish translations | Low | Translate all 46+ keys manually; no placeholders |
| Phone number mismatch across files | Low | Standardize to `+34 977 64 20 10` everywhere |
| Dropdown UX regression vs toggle | Low | Use native `<select>` — accessible, no JS dependency |

## Rollback Plan

Revert the git commit. Since this is a self-contained i18n refactor touching only translation files + config + nav template, a single `git revert` restores the previous binary-toggle behavior. No database migrations involved.

## Dependencies

- None. Pure codebase refactor, no external services or libraries.

## Success Criteria

- [ ] `?lang=ca` renders Catalan strings (same content as current `?lang=es`)
- [ ] `?lang=es` renders Spanish strings (new translations)
- [ ] `?lang=en` renders English strings (unchanged behavior)
- [ ] Nav dropdown shows 3 options: Català, Castellano, English
- [ ] `og:locale` outputs `ca_ES`, `es_ES`, or `en_US` correctly
- [ ] Phone number is consistent across all locale files and JSON-LD
- [ ] No broken `?lang=` URLs — invalid locale falls back to CA
