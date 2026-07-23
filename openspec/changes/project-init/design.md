# Design: Project Init — Pit o Cuixa

## Technical Approach

Greenfield vertical monorepo: `public/` front controller (single entry), `src/backend/` JSON API + DB layer, `src/frontend/` PHP templates + CSS + JS, `src/shared/` config + i18n, `db/` schema + seed, `scripts/` setup. PHP 8 vanilla, single-file SQLite with WAL, PDO, no Composer, no frameworks. Front controller pattern routes both HTML pages (SSR) and JSON API. Mock JSON lets frontend ship day 1 while backend builds the API. This matches the proposal's approach and the exploration's winning vertical-slice structure.

## Architecture Overview (high level)

```
           ┌─────────────────────────────────────────────────────────┐
HTTP → public/index.php (front controller)
           │  route() decides: API (JSON) or HTML page (SSR)
           ├─ /api/*        → src/backend/api/*   → pdo → SQLite (db/pitocuixa.db)
           └─ /*            → src/frontend/templates/pages/* (PHP SSR HTML)
                              uses design tokens CSS + JS modules
           └─ static /css /js /img /manifest.json /sw.js /sitemap.xml /robots.txt
           │
           └─ admin HTML at /admin/* uses same front controller + session guard
           └─ last.shop: external ordering via product.last_shop_url (read-only link)
```

Two kinds of response leave `index.php`:
- **JSON** — Content-Type `application/json; charset=utf-8` (admin auth + public reads)
- **HTML** — server-rendered templates consuming JSON-shaped data from the same repositories (Frontend never touches DB directly; it calls repositories or the API client in JS).

The DB (SQLite) is the single source of truth. Catalog data flows: `products/categories → repositories → API controllers (or page controllers) → response`.

## Module Structure

| Module | Path | Responsibility | Depends on |
|--------|------|----------------|------------|
| Front Controller | `public/index.php` | Routing dispatch, env/bootstrap | `src/shared`, backend, frontend |
| Bootstrap | `src/shared/bootstrap.php` | autoload, error mode, config load | `src/shared/config.php` |
| Config | `src/shared/config.php` | Env-driven constants (DB path, site URL, secrets) | — |
| i18n | `src/shared/i18n/{es,en}.php` | Translation arrays + `__()` helper | — |
| Router | `src/backend/router.php` | Method+path matching, controller resolution | controllers |
| Repositories | `src/backend/db/repositories/{product,category,session}.php` | SQL query objects, prepared statements | PDO connection |
| DB | `src/backend/db/connection.php` | PDO singleton, WAL, pragmas | config |
| API Controllers | `src/backend/api/{products,categories,menu,auth,admin}/*.php` | Request parse, validate, call repo, JSON response | repositories |
| Auth | `src/backend/auth/auth.php` | Session token issue/verify/logout | `user` + `session` repos |
| Response | `src/backend/http/response.php` | `json($data,$code)` + `csv()` + uniform error | — |
| Page Controllers | `src/backend/pages/{home,menu,admin}/*.php` | SSR: fetch data, render template | repositories, templates |
| Templates | `src/frontend/templates/{layouts,partials,pages}/` | HTML+PHP views, no business logic | CSS/JS assets |
| CSS | `src/frontend/css/` → served via `public/css/` | Tokens, BEM components, mobile-first | — |
| JS | `src/frontend/js/` → served via `public/js/` | ESM modules: api-client, menu-filter, sw registration | — |
| Scripts | `scripts/{setup,import-csv,export-csv}.php` | CLI: create DB, CSV round-trip | DB, repositories |

## Data Flow

```
SQLite products ─▶ PDO ─▶ repository ─▶ controller ─┬─▶ JSON  ─▶ <script>/fetch (JS modules)
                                                   └─▶ page controller ─▶ PHP template (SSR HTML) ─▶ browser
                                                         ▲
                              locale (es|en) picks name_es/name_en at render time
```

Frontend JS uses `api-client.js` (fetch wrapper) for dynamic bits (category filter, locale toggle); base page arrives already SSR. `__($key)` resolves from i18n arrays; locale from URL `?lang=en` or cookie, default `es` (per PC-004).

## Request Lifecycle (sequence)

```
Browser ──HTTP──▶ public/index.php
                     │ require src/shared/bootstrap.php → config + i18n
                     │ src/backend/router.php::route($method, $path)
                     │
                     ├─ match /api/*       → API controller
                     │     ├─ if admin/* → auth::require_token() → 401 if bad
                     │     ├─ validate input → repository → PDO → SQLite
                     │     └─ response::json($payload, $status)
                     │
                     ├─ match /admin/*    → page controller (admin)
                     │     ├─ auth::require_session() → redirect /admin/login if bad
                     │     └─ render templates/pages/admin/*.php
                     │
                     └─ match / | /menu  → page controller (public)
                           ├─ repositories fetch active products/categories
                           └─ render templates/pages/{home,menu}.php → layout/default.php

index.php emits final response (echo/headers) and returns.
```

## Authentication & Authorization

Stateless-ish token in SQLite `sessions` table:

```
POST /api/auth/login {username,password}
  → user repo verifies password_hash() (bcrypt)
  → session::create(user_id, token=random_bytes(32), expires_at = now+8h)
  → response {token, user}
Admin API requests: header `Authorization: Bearer <token>`
  → auth::require_token() → session repo find token, check expires_at → user (active?)
  → invalid/expired → 401 {error:true,message:"Unauthorized",code:401}
Admin HTML pages: same token stored in a cookie (httpOnly + SameSite=Lax); page guard reads cookie.
POST /api/auth/logout → DELETE session row → {success:true}
```

Decision: token-on-DB over native PHP `$_SESSION` files. **Rationale**: shared hosting session config varies; a token table is portable, survives load-balancers (if added later), and satisfies JA constraints. No roles in Sprint 1 (all admins full access).

## Database Access Layer

`connection.php` returns a single PDO instance with WAL and error-mode EXCEPTION. Repositories (not raw PDO) own SQL via a tiny query builder of pure PHP builders — no ORM. Prepared statements everywhere.

```php
// src/backend/db/connection.php (sketch — non-obvious parts)
$pdo = new PDO('sqlite:' . Config::dbPath());
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA journal_mode=WAL');
$pdo->exec('PRAGMA foreign_keys=ON');
$pdo->exec('PRAGMA busy_timeout=5000'); // mitigate SQLITE_BUSY for concurrent admin edits
```

```php
// repository pattern: prepared statement, booleans coerced for JSON
$stmt = $pdo->prepare('SELECT * FROM products WHERE is_active=1 AND category_id=:c ORDER BY sort_order');
$stmt->execute([':c' => $id]);
$rows = $stmt->fetchAll();
// serialize: is_active → bool, price → float
```

Decision: no query-builder library; hand-rolled prepared statements. **Rationale**: spec forbids Composer; schema is tiny (5 tables), a repo-per-entity keeps SQL in one place, editable by backend CODEOWNERS only.

## Frontend Architecture

- Templates are PHP files using `__()` for i18n and plain `<?= $product['name_'.LANG] ?>`. Layouts wrap pages; partials (header/footer/product-card/nav) reused. No template engine.
- CSS served from `public/css/` (built by copying/concatenating `src/frontend/css/`, no build step). Order: `tokens → base → components/* → layouts/* → pages/*`.
- JS ES modules from `public/js/`. `main.js` imports `api-client.js`, `components/menu-filter.js`, registers SW. Progressive enhancement: server HTML renders without JS (PC-006 constraint).
- CSS organized by component (BEM), not page; page CSS only for overrides. CODEOWNERS gate `public/css/` to frontend.

## API Contract Implementation

`router.php` maps `method + path pattern` to a controller function returning `(status, body)`:

```php
$routes = [
  ['GET','/api/products',            fn() => $products->all($_GET['id_category'] ?? null)],
  ['GET','/api/products/{slug}',     fn($p) => $products->bySlug($p['slug'])],
  ['GET','/api/menu',                fn() => $menu->grouped()],
  ['POST','/api/auth/login',         fn() => $auth->login(body())],
  ['POST','/api/admin/products',     fn() => $admin->create(body(), req_auth())],
  ['PUT','/api/admin/products/{id}', fn($p) => $admin->update((int)$p['id'], body(), req_auth())],
  ['DELETE','/api/admin/products/{id}',fn($p) => $admin->delete((int)$p['id'], req_auth())],
  ['POST','/api/admin/import',       fn() => $admin->import($_FILES, req_auth())],
  ['GET','/api/admin/export',        fn() => $admin->export(req_auth())],
];
```

Response envelope: `response::json($data,$code)` sets `Content-Type: application/json; charset=utf-8`; booleans/integers coerced in serializers; errors always `{error:true,message,code}` (JA-005). CSV export sets `text/csv` + `Content-Disposition: attachment`.

## PWA Strategy

Registration (in `main.js`, only over HTTPS/localhost):

```js
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
// sw.js: skipWaiting() on update; clients.claim() on activate
```

Cache strategy table (matches PW-003):

| Route | Strategy | Cache name |
|-------|----------|------------|
| `GET /` `/menu` (HTML) | network-first, fallback cache | `pages-v1` |
| `/css`, `/js`, fonts | cache-first | `static-v1` |
| `/img/*` | cache-first, LRU cap (~30 entries) | `images-v1` |
| `/api/*` | network-first, fallback cache | `api-v1` |
| unmatched offline | `offline.html` fallback | — |

`manifest.json` static at root; icons 192/512/maskable in `/img/`.

## SEO/GEO Implementation

SSR is mandatory for SEO (no JS-rendered critical content). Page controllers inject per-page meta:

```php
// page controller assembles a $meta array, layout/default.php renders <head>
$meta = ['title'=>__('home.title'), 'desc'=>__('home.desc'),
         'canonical'=>Config::siteUrl().'/', 'og_image'=>'/img/og-image.jpg',
         'langs'=>['es'=>siteUrl().'/','en'=>siteUrl().'/en/']];
// layout outputs: <title>, meta description, canonical, og:*, twitter:*, hreflang es/en/x-default,
// geo.region, geo.placename, geo.position (41.1412,1.3939), ICBM
```

JSON-LD blocks per page: home → `Restaurant` + `LocalBusiness` (with `geo`, `openingHours`); menu → `Menu` with `provider` referencing the Restaurant `@id`. `sitemap.xml` and `robots.txt` are **PHP-generated endpoints** (route to a controller that lists `/`, `/menu`, `/es/`, `/en/` active products with hreflang). Decision: dynamic sitemap over static file — **rationale**: catalog changes frequently via admin API; a static file would drift.

## Deployment & Environment

Hosting document root = `public/`. Repo layout vs deployed:

```
hosting/                          repo/
├── public/  ←── deployed ──      public/        (index.php, sw.js, manifest.json, /css /js /img)
├── data/pitocuixa.db             db/            (schema.sql, seed.sql; DB ignored in git)
├── src/        (OUTSIDE docroot) src/           (backend, frontend, shared)
└── .env                         .env.example
```

`src/shared/config.php` reads `.env` (a hand-rolled `parse_ini_file` loader — no Composer):

```php
class Config {
  static function dbPath(): string  { return getenv('DB_PATH') ?: __DIR__.'/../../data/pitocuixa.db'; }
  static function siteUrl(): string { return rtrim(getenv('SITE_URL') ?: 'https://pitocuixa.es','/'); }
  static function env(): string    { return getenv('APP_ENV') ?: 'prod'; }
}
```

SQLite file permissions: `0750` dir, `0660` file, owned by the PHP user (writeable + WAL sidecar `-wal`/`-shm`). `.env` outside docroot; never committed. Admin seed creates one user via `scripts/setup.php`.

## Testing Strategy

Test layers available: none (config.testing.layers all disabled). Sprint 1 keeps the bar low.

| Layer | Sprint 1 | Sprint 2 plan |
|-------|----------|---------------|
| Unit | ❌ none | Add PHPUnit (manual PHAR download, no Composer) — repository + auth tests |
| Integration | ❌ none | PHPUnit test DB via `:memory:` PDO; run schema + seed per test |
| E2E | ❌ none | Optional Playwright for menu-filter + admin CRUD |
| Lint | `php -l` via CI | Add PHPStan level 1 (manual PHAR) |
| CSS/JS | manual review | Optional Stylelint + eslint |
| Frontend | manual QA | — |
| PWA/SEO | Lighthouse manual run | Add Lighthouse CI to workflow |

Manual QA checklist per PR (replaces automated tests):

- [ ] `php -l` passes on changed PHP files
- [ ] Home renders hero + ≥3 featured + business info (ES and EN)
- [ ] Menu shows all ~45 products grouped by 11 categories
- [ ] Category filter shows/hides, "All" resets
- [ ] Product cards: image/name/desc/price `€X,XX`/CTA → correct last.shop URL
- [ ] No-JS: pages render (SR HTML)
- [ ] Login/logout works; unauthenticated admin → redirect/401
- [ ] Product CRUD + validation errors inline
- [ ] CSV import: per-row errors; CSV export round-trips
- [ ] 360px usable; 1280px multi-column
- [ ] Installable (Lighthouse PWA ≥ 90); offline home after first visit
- [ ] Lighthouse SEO ≥ 90; JSON-LD passes Rich Results Test
- [ ] `robots.txt` + `sitemap.xml` reachable; hreflang bidirectional

## Migration / Rollout

No migration (greenfield). Rollback = delete `public/` deploy, restore WordPress backup, drop SQLite file, DNS revert (per proposal). Feature flags: none in Sprint 1.

## Open Questions

- [ ] Exact street address + phone + opening hours (needed for JSON-LD `Restaurant` + `LocalBusiness`)
- [ ] Quicksand self-hosted vs Google Fonts (FOUC/FOUT tradeoff; affects offline font caching)
- [ ] Is `last.shop` URL pattern stable enough to store full URLs long-term? (monitor)
- [ ] Confirm hosting supports writing `data/` outside docroot (affects SQLite placement)