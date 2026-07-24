## Exploration: i18n System Refactor (CA/ES/EN)

### Current State

**File Structure:**
`src/shared/i18n/` has two files:
- `es.php` — claims to be Spanish but contains **Catalan** strings (e.g. "Inici", "Carta", "la nostra carta", "Tancar sessió")
- `en.php` — English translations (correct)

**No `ca.php` file exists.** The `es.php` file effectively IS the Catalan translation mislabeled as Spanish.

**Locale Loading (bootstrap.php):**
1. Priority: `$_GET['lang']` → `$_COOKIE['lang']` → `Config::defaultLocale()` (from .env)
2. Defines `LANG` constant
3. Requires `i18n/{LANG}.php` — so `LANG=ca` would fail since `i18n/ca.php` doesn't exist
4. The locale file defines global `__()` function + `getTranslations()` array + `$GLOBALS['_translations_{lang}']`

**Config (config.php):**
- `supportedLocales()` returns `['es', 'en']` (hardcoded)
- `defaultLocale()` reads `DEFAULT_LOCALE=es` from .env

**Locale Switching (nav.php):**
- Toggles between 'es' and 'en' only: `$otherLang = LANG === 'es' ? 'en' : 'es'`
- Builds URL with `?lang={otherLang}` appended
- **No cookie is SET** — only READ from bootstrap. Locale resets on every session unless `?lang=` is in the URL.

**The `__()` function is duplicated identically in each locale file.** Same code, same structure. Only the `getTranslations()` array differs.

**JS Client-Side (api-client.js):**
- Reads locale from `<html lang>` attribute or `?lang=` param
- Sends `X-Locale` header on API calls
- **No client-side string translation** — all strings are hardcoded in Spanish inline JS

### String Inventory

**46 keys total**, identical across both files. Organized by domain:

| Domain | Keys | Examples |
|--------|------|---------|
| Global/Layout | 11 | site.name, nav.home, lang.switch, footer.rights |
| Home Page | 10 | home.title, home.hero.title, home.info.address |
| Menu Page | 8 | menu.heading, menu.filter.all, menu.no_products |
| Product Labels | 3 | product.price, product.featured, product.view |
| Errors | 8 | error.404, error.500, error.401 |
| Admin | 19 | admin.title, admin.products, admin.save, admin.password |

### Integration Points

**Templates using `__()`:**
- `layouts/default.php` — meta title/desc, og:locale, JSON-LD
- `partials/nav.php` — nav links, lang switch label
- `partials/footer.php` — brand, tagline, hours, copyright
- `partials/product-card.php` — "Order" CTA
- `partials/admin-nav.php` — logout button
- `pages/home.php` — hero, featured section, info section
- `pages/menu.php` — heading, subtitle, filter, empty state
- `pages/404.php` — error message
- `pages/admin/login.php` — labels
- `pages/admin/dashboard.php` — title (meta via backend)
- `pages/admin/products.php` — (meta via backend only)
- `pages/admin/categories.php` — (meta via backend only)

**Backend PHP using `__()`:**
- `backend/pages/home.php`, `menu.php` — meta titles/descriptions
- `backend/pages/admin/*.php` — meta titles
- `backend/auth/auth.php` — error messages (401)
- `backend/api/auth.php` — logout response message

**Data localization (NOT i18n system):**
- Product/category names use DB columns `name_{LANG}`, `description_{LANG}` — accessed via `$product["name_{$lang}"]`
- This is a separate system from `__()`, but uses the same `LANG` constant

**Sitemap (sitemap.php):**
- Hardcoded `'es'` and `'en'` hreflang annotations
- URL paths differ by locale: `/producto/` (ES) vs `/product/` (EN)

**Hardcoded strings not using `__()` (all in Spanish):**
- **admin-nav.php**: "Dashboard", "Productos", "Categorías", "Ver sitio" (hardcoded)
- **admin/login.php**: "Contraseña" (label), "Error de autenticación", "Error de conexión" (JS inline)
- **admin/dashboard.php**: "Panel de Administración", "Productos", "Categorías", "Destacados", "Productos por Categoría"
- **admin/products.php**: "Productos", "+ Nuevo Producto", "Nuevo Producto", "Editar Producto", "Guardar", "Cancelar", "Editar", "Eliminar", "Todos los Productos", table headers, JS messages
- **admin/categories.php**: "Categorías", "+ Nueva Categoría", "Nueva Categoría", "Editar Categoría", "Guardar", "Cancelar", "Editar", "Eliminar", JS messages

### Approaches

1. **Minimal rename + create Spanish** — Only fix the i18n files and locale detection
   - Pros: Fast, addresses the core bug
   - Cons: Doesn't touch hardcoded admin strings or JS
   - Effort: Low

2. **Full i18n overhaul** — Rename, create Spanish, add cookie persistence, add locale dropdown, extract all admin hardcoded strings
   - Pros: Complete solution, 3 languages fully supported
   - Cons: Significant template changes (admin area), more risk
   - Effort: Medium-High

3. **Phased approach** — Phase 1: fix files + locale detection (minimal). Phase 2: admin i18n + JS strings + UX improvements
   - Pros: Core fix ships fast, admin cleanup follows
   - Cons: Two delivery cycles
   - Effort: Medium (split across phases)

### Recommendation

**Approach 3 (phased).** The core bug (wrong locale content + missing Spanish) is quick to fix and should ship first. The admin area has ~50+ hardcoded strings that are primarily used by business owners who likely speak Spanish anyway — lower priority. Phase 2 can tackle admin i18n, cookie persistence, and the language dropdown.

### Risks

1. **BREAKING CHANGE**: Current `es.php` contents → `ca.php` rename means any existing `?lang=es` or `lang=es` cookie will break until users get the new locale. Need to decide: keep `es` as Spanish and create `ca` for Catalan, or keep `es` as-is (Catalan) and use a different code for Spanish.
2. **Phone number discrepancy**: `home.info.phone` has different numbers in `es.php` (+34 977 64 20 10) vs `en.php` (+34 977 64 18 05) — need to verify which is correct.
3. **Mixed error strings**: Current `es.php` has some Spanish error keys mixed with Catalan ("Página no encontrada" vs "Pàgina no trobada").
4. **Database bilingual columns**: `name_es`, `description_es` — if these are currently storing Spanish (correct), no migration needed. But verify no code, seed data, or fixtures assumes `_es` = Catalan.
5. **og:locale hardcoded mapping**: In `default.php` — `$locale === 'en' ? 'en_US' : 'es_ES'` — needs `ca_ES` support.
6. **Admin i18n scope**: ~50+ strings across 4 admin templates + inline JS. Each JS string (`confirm()` messages, success alerts) needs careful extraction.

### Ready for Proposal

Yes. The orchestrator should decide:
- **Recommended approach**: Phased (core fix first, admin/UX later)
- **Key decision**: Default locale — keep `es` (Spanish) or change to `ca` (Catalan, since the business is in Catalonia)?
- **Locale codes**: Use `ca` for Catalan, `es` for Spanish (ISO 639-1 standard) — this means `es.php` CORRECTLY becomes Spanish, and we create `ca.php` from the current `es.php` content.
- **Lang switch UX**: Binary toggle won't work with 3 languages — needs a `<select>` or dropdown
