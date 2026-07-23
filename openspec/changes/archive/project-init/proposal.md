# Proposal: Project Init — Pit o Cuixa

## Intent

Replace pitocuixa.es (WordPress → last.shop redirect) with self-hosted PHP+SQLite. Sprint 1: public catalog, admin CRUD, PWA, CI — minimum to browse without last.shop.

## Scope

### In Scope
- Home (hero, featured, business info) + Menu page (category filter, "Order now" → last.shop)
- Admin: login, product/category CRUD, CSV import/export
- JSON API: products, categories, menu, admin CRUD, auth
- SQLite: 5 tables + seed (~45 products, 11 categories)
- Design System 3: tokens, BEM, mobile-first
- PWA: manifest, service worker, offline
- SEO/GEO: meta, OG, JSON-LD (Restaurant, Menu, LocalBusiness), sitemap, hreflang
- Bilingual ES/EN · CI (`php -l`) · CODEOWNERS

### Out of Scope
- Cart/checkout (last.shop) · Image upload · Translation UI · Email/reservations/payments · PHPUnit (Sprint 2) · Bundlers

## Capabilities

### New
- `product-catalog`: Public browsing — home, menu, filter, cards, bilingual
- `admin-panel`: Auth + CRUD + CSV import/export
- `json-api`: REST contract — public read + admin write
- `design-system`: Tokens, BEM, mobile-first, Quicksand
- `pwa`: Manifest, service worker, offline, installable
- `seo-geo`: Structured data, sitemap, hreflang, geo

### Modified
None (greenfield)

## Approach

Vertical monorepo (`src/frontend/`, `src/backend/`, `src/shared/`, `public/`). Frontend uses mock JSON; backend builds API+DB in parallel. PHP 8 front controller, SQLite WAL, CSS tokens+BEM+mobile-first. No Composer, no frameworks.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `public/` | New | Document root, front controller |
| `src/backend/` | New | API, admin, DB, router |
| `src/frontend/` | New | CSS, JS, templates, mocks |
| `src/shared/` | New | Config, i18n |
| `db/` | New | Schema + seed |
| `scripts/` | New | Setup, CSV tools |
| `.github/` | New | CI, CODEOWNERS |

## Risks

| Risk | Likelihood | Mitigation |
|------|-----------|------------|
| CSS merge conflicts (8 people) | Med | CODEOWNERS gates `public/css/` |
| Frontend blocked by API | Low | Mock JSON day 1 |
| No automated tests | High | Manual QA per PR; PHPUnit Sprint 2 |
| last.shop URL breakage | Med | Full URLs stored; monitor |

## Rollback Plan

Delete `public/`, restore WordPress backup. SQLite = single file delete. DNS revert.

## Dependencies

GitHub repo + branch protection · PHP+SQLite hosting · last.shop catalog · Cloudinary URLs (read-only) · DNS access

## Success Criteria

- [ ] Home + menu render all products (ES/EN)
- [ ] Category filter works
- [ ] Admin CRUD functional
- [ ] CSV import loads catalog
- [ ] PWA installable + offline
- [ ] Lighthouse SEO ≥ 90
- [ ] CI green on PRs
- [ ] Mobile usable at 360px
