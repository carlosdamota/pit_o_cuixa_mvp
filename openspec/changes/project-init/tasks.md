# Tasks: Project Init — Pit o Cuixa

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~3,100 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (Foundation) → PR 2 (Catalog) → PR 3 (Admin) → PR 4 (PWA/SEO/CI) |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Foundation: DB, config, bootstrap, router, front controller, env, CSS tokens+base | PR 1 | base=main; no upstream deps |
| 2 | Public Catalog: repos, API (products/categories/menu), page controllers, templates, component CSS, JS | PR 2 | base=main; depends on PR 1 |
| 3 | Admin+Auth: auth system, admin API CRUD+CSV, admin pages+templates, admin CSS | PR 3 | base=main; depends on PR 2 |
| 4 | PWA+SEO+CI: manifest, sw.js, icons, offline, JSON-LD, sitemap, robots, CI, CODEOWNERS, scripts | PR 4 | base=main; can parallelize |

## Phase 1: Foundation

### Backend
- [x] B-01 `db/schema.sql` — 5 tables (users, sessions, categories, products) + ~45 product seed + 11 categories
- [x] B-02 `src/shared/{config,bootstrap}.php` — env config loader, autoload, error mode
- [x] B-03 `src/shared/i18n/{es,en}.php` — `__()` helper + translation arrays
- [x] B-04 `src/backend/db/connection.php` — PDO singleton, WAL, pragmas
- [x] B-05 `src/backend/http/response.php` — `json()`, `csv()`, uniform error envelope
- [x] B-06 `src/backend/router.php` — method+path matching, controller dispatch
- [x] B-07 `public/index.php` — front controller routing /api/* vs HTML pages
- [x] B-08 `.env.example` + `.gitignore` + `public/.htaccess`

### Frontend
- [x] F-01 `src/frontend/css/tokens.css` — design tokens (:root variables per DS-001)
- [x] F-02 `src/frontend/css/base.css` — reset, body, typography, Quicksand (DS-004)
- [x] F-03 `src/frontend/templates/layouts/default.php` — HTML shell, `<head>` meta placeholders

## Phase 2: Core — Public Catalog

### Backend
- [x] B-09 `src/backend/db/repositories/product.php` — all(), bySlug(), byCategory()
- [x] B-10 `src/backend/db/repositories/category.php` — all(), bySlug()
- [x] B-11 `src/backend/api/products.php` — GET /api/products[/{slug}], /api/categories
- [x] B-12 `src/backend/api/menu.php` — GET /api/menu (grouped by category)
- [x] B-13 `src/backend/pages/home.php` — SSR: hero, featured products, business info
- [x] B-14 `src/backend/pages/menu.php` — SSR: grouped products with filter state

### Frontend
- [x] F-04 `src/frontend/templates/pages/home.php` — hero section, featured grid, business info
- [x] F-05 `src/frontend/templates/pages/menu.php` — filter bar, product groups
- [x] F-06 `src/frontend/css/components/product-card.css` — BEM, mobile-first grid (DS-002)
- [x] F-07 `src/frontend/css/components/filter-bar.css` — sticky category tabs
- [x] F-08 `src/frontend/css/layouts/{header,footer}.css` — nav, language toggle
- [x] F-09 `src/frontend/templates/partials/{header,footer,product-card,nav}.php`
- [x] F-10 `public/js/menu-filter.js` — ESM: category filter show/hide, "All" reset
- [x] F-11 `public/js/api-client.js` — fetch wrapper, error handling, locale header

## Phase 3: Admin + Auth

### Backend
- [x] B-15 `src/backend/auth/auth.php` — `require_token()`, `require_session()`, `password_verify()`
- [x] B-16 `src/backend/db/repositories/user.php`, `session.php` — token CRUD, user lookup
- [x] B-17 `src/backend/api/auth.php` — POST /api/auth/login, POST /api/auth/logout
- [x] B-18 `src/backend/api/admin-products.php` — POST/PUT/DELETE /api/admin/products
- [x] B-19 `src/backend/api/admin-categories.php` — POST/PUT /api/admin/categories
- [x] B-20 `src/backend/api/admin-io.php` — POST /api/admin/import, GET /api/admin/export
- [x] B-21 `src/backend/pages/admin/{login,dashboard,products,categories}.php` — SSR admin pages

### Frontend
- [x] F-12 `src/frontend/templates/pages/admin/{login,dashboard,products,categories}.php`
- [x] F-13 `src/frontend/css/pages/admin.css` — form styles, table, validation errors

## Phase 4: PWA + SEO + CI

### Frontend (PWA)
- [x] F-14 `public/manifest.json` — name, icons[192/512/maskable], standalone display (PW-001)
- [x] F-15 `public/sw.js` — service worker: 4 cache strategies (PW-003)
- [x] F-16 `public/offline.html` — minimal offline fallback
- [x] F-17 `public/img/icon-{192,512,maskable}.svg` — PWA icons (SVG placeholders)
- [x] F-18 `public/js/main.js` — SW registration, progressive enhancement

### Shared (SEO)
- [x] S-01 JSON-LD in layout `<head>`: Restaurant (home), Menu (menu), LocalBusiness (all) per SG-003
- [x] S-02 OG meta, geo meta, hreflang tags in layout/default.php (SG-001, SG-002, SG-005, SG-006)
- [x] S-03 `src/backend/pages/sitemap.php` — dynamic XML sitemap with hreflang (SG-004)
- [x] S-04 `src/backend/pages/robots.php` — Allow all, Sitemap reference (SG-007)

### DevOps
- [x] S-05 `.github/workflows/ci.yml` — `php -l` syntax check on changed PHP files
- [x] S-06 `.github/CODEOWNERS` — backend/, frontend/, public/ ownership per subteam
- [x] S-07 `scripts/setup.php` — DB creation, schema load, admin password prompt

## Dependency Graph

```
Phase 1 (Foundation) ──────┐
                           ↓
Phase 2 (Public Catalog) ──→ Phase 3 (Admin + Auth)
                           │
                           └── Phase 4 (PWA + SEO + CI) ←── parallelizable
```

Phase 1 has no upstream deps. Phase 2 requires Phase 1 DB + config + routing in place. Phase 3 requires Phase 2 repos + routing. Phase 4 SEO tasks need layout from Phase 1 but are otherwise independent of Phases 2–3. DevOps tasks S-05–S-07 are independent of all and can start immediately.
