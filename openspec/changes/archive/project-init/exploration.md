# Exploration: Project Init — Pit o Cuixa

## Current State

The project is a greenfield PHP+SQLite application replacing the current WordPress site (pitocuixa.es). The workspace has only SDD scaffolding (openspec/config.yaml) and no source code yet. The existing WordPress site is a single-page landing page redirecting to last.shop for ordering. The last.shop store has ~45 products across 11 categories: Pollo, Menú diario llevar, Menú especial casal, Platos principales, Croquetas, Ensaladas, Patatas, Bebidas, Postre, Arroces y fideuà por encargo, and Operativa restaurante.

The team is 8 people in 2 subteams (frontend/backend), monorepo on GitHub, no CI/CD configured yet.

## Affected Areas

No source files exist yet — this exploration defines the structure for everything:

| Path | Why Affected |
|------|-------------|
| `public/` | Document root — entry point, static assets |
| `src/backend/` | PHP backend — API, admin, DB, router |
| `src/frontend/` | Frontend — CSS tokens, JS, templates |
| `src/shared/` | Shared config, i18n |
| `.github/workflows/` | CI pipeline |
| `.github/CODEOWNERS` | Team ownership by directory |
| `scripts/` | DB setup, CSV import/export |
| `db/schema.sql` | SQLite database schema |
| `db/seed.sql` | Initial catalog seed from last.shop |

---

## Findings

### 1. Monorepo Structure for 2 Subteams

**Recommended approach: Vertical slice by domain, horizontal split by layer**

```
pitocuixa/
├── .github/
│   ├── workflows/
│   │   └── ci.yml                    # php -l, CSS/JS lint (if added)
│   └── CODEOWNERS                    # @pitocuixa/frontend, @pitocuixa/backend
├── public/                           # ← Document root (web server points here)
│   ├── index.php                     # Front controller / router
│   ├── css/                          # Compiled CSS (frontend output)
│   ├── js/                           # Compiled JS (frontend output)
│   ├── img/                          # Static images
│   └── assets/                       # Fonts, icons, etc.
├── src/
│   ├── backend/                      # Backend team — PHP logic only
│   │   ├── api/                      # JSON API endpoints
│   │   ├── admin/                    # Admin panel PHP
│   │   ├── db/                       # DB connection, queries
│   │   └── helpers.php               # Utility functions
│   ├── frontend/                     # Frontend team — views, CSS, JS
│   │   ├── css/                      # Source CSS (organized by component)
│   │   │   ├── tokens.css            # Design tokens (variables)
│   │   │   ├── base.css              # Reset, typography
│   │   │   ├── components/           # Component styles
│   │   │   └── pages/                # Page-specific styles
│   │   ├── js/                       # JS source
│   │   │   ├── main.js               # Entry point
│   │   │   ├── api-client.js         # Fetch wrapper for JSON API
│   │   │   └── components/           # JS component modules
│   │   └── templates/                # PHP view templates (HTML mixed with PHP)
│   │       ├── layouts/
│   │       │   └── default.php       # Base HTML layout
│   │       ├── partials/             # Reusable snippets
│   │       │   ├── header.php
│   │       │   ├── footer.php
│   │       │   ├── product-card.php
│   │       │   └── nav.php
│   │       └── pages/                # Full page templates
│   │           ├── home.php
│   │           ├── menu.php
│   │           └── admin/
│   └── shared/                       # Shared between teams
│       ├── config.php                # App config (DB path, site URL, etc.)
│       ├── constants.php             # Constants, category slugs
│       └── i18n/                     # Translations
│           ├── es.php
│           └── en.php
├── db/                                # Database assets
│   ├── schema.sql                    # Full schema DDL
│   └── seed.sql                      # Initial data (~45 products)
├── scripts/                          # CLI scripts
│   ├── setup.php                     # First-time setup (create DB, run schema + seed)
│   ├── import-csv.php                # Import products from CSV
│   └── export-csv.php                # Export products to CSV
├── README.md                         # Project overview (only when explicitly requested)
└── .gitignore
```

**Why this structure:**
- `public/` as document root — standard PHP hosting, CMS-compatible
- `src/frontend/templates/` contains the HTML/PHP views — frontend team owns markup and CSS/JS, backend team calls these templates but doesn't modify them
- `src/backend/api/` — pure JSON endpoints, no HTML rendering
- `frontend/css/tokens.css` — single source of truth for Design System 3 variables
- `CODEOWNERS` can precisely assign: `src/backend/` → backend team, `src/frontend/` → frontend team, `public/` → both (deployable output)

**Alternative considered: Flat structure** (all PHP in root, CSS in subdir). Rejected because it creates merge conflicts when both teams touch the same directories, and doesn't scale to 8 people.

### 2. JSON API Contract (Frontend ↔ Backend)

The frontend team builds HTML templates + CSS + JS. The backend team serves PHP pages that render those templates AND provides JSON endpoints for dynamic data.

**Endpoints:**

| Method | Endpoint | Description | Response |
|--------|----------|-------------|----------|
| GET | `/api/products` | List all products | `{ products: Product[], categories: Category[] }` |
| GET | `/api/products?id_category={id}` | Filter by category | `{ products: Product[] }` |
| GET | `/api/products/{slug}` | Single product | `{ product: Product }` |
| GET | `/api/categories` | All categories | `{ categories: Category[] }` |
| GET | `/api/menu` | Full menu (grouped by category) | `{ groups: { category: Category, products: Product[] }[] }` |
| POST | `/api/admin/products` | Create product | `{ product: Product }` |
| PUT | `/api/admin/products/{id}` | Update product | `{ product: Product }` |
| DELETE | `/api/admin/products/{id}` | Delete product | `{ success: true }` |
| POST | `/api/admin/import` | Import CSV | `{ imported: int, errors: string[] }` |
| GET | `/api/admin/export` | Export CSV | (CSV download) |
| POST | `/api/auth/login` | Admin login | `{ token: string, user: User }` |
| POST | `/api/auth/logout` | Logout | `{ success: true }` |

**Schema definitions:**

```json
// Product
{
  "id": 1,
  "name_es": "Pollo + Patatas + Alioli",
  "name_en": "Chicken + Chips + Garlic Mayo",
  "slug": "pollo-patatas-alioli",
  "description_es": "Cuarto de pollo asado con patatas y alioli",
  "description_en": "Quarter roast chicken with chips and garlic mayo",
  "price": 19.50,
  "category_id": 1,
  "category_slug": "menu-de-pollo",
  "image_url": "https://res.cloudinary.com/...",
  "last_shop_url": "https://pitocuixa.last.shop/es/pit-o-cuixa/c/menu-de-pollo/p/pollo-patatas-alioli",
  "is_active": true,
  "sort_order": 1
}

// Category
{
  "id": 1,
  "name_es": "MENÚ DE POLLO",
  "name_en": "CHICKEN MENU",
  "slug": "menu-de-pollo",
  "description_es": null,
  "description_en": null,
  "sort_order": 1,
  "is_active": true
}
```

**Error format (consistent across all endpoints):**
```json
{
  "error": true,
  "message": "Product not found",
  "code": 404
}
```

**Mock fixtures:** The frontend team can work with static JSON files at `src/frontend/mock/` (e.g., `products.json`, `categories.json`, `menu.json`) until the API endpoints are ready. This unblocks both teams.

### 3. CSS/JS Organization for Design System 3

**CSS Tokens (`src/frontend/css/tokens.css`):**

```css
:root {
  /* Design System 3 */
  --color-primary: #f7e721;
  --color-secondary: #d32f2f;
  --color-surface: #f7f9ff;
  --color-surface-container-low: #edf4ff;
  --color-surface-container-lowest: #ffffff;
  --color-on-surface: #1a1c1e;

  /* Typography */
  --font-family: 'Quicksand', sans-serif;
  --font-size-base: 16px;
  --font-size-h1: 2.5rem;
  --font-size-h2: 2rem;
  --font-size-h3: 1.5rem;
  --font-size-body: 1rem;
  --font-size-small: 0.875rem;

  /* Spacing */
  --space-xs: 4px;
  --space-sm: 8px;
  --space-md: 16px;
  --space-lg: 24px;
  --space-xl: 32px;
  --space-2xl: 48px;

  /* Border */
  --radius: 8px;           /* ROUND_EIGHT */

  /* Shadows */
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);

  /* Transitions */
  --transition-fast: 150ms ease;
  --transition-normal: 250ms ease;

  /* Breakpoints (for reference in comments) */
  /* --bp-mobile: 640px */
  /* --bp-tablet: 1024px */
  /* --bp-desktop: 1280px */
}
```

**CSS file organization:**

```
src/frontend/css/
├── tokens.css              # Design tokens (variables)
├── base.css                # Reset, body, typography base
├── components/
│   ├── button.css          # Button component styles
│   ├── card.css            # Product card
│   ├── badge.css           # Badge labels (rotisserie labels)
│   ├── nav.css             # Navigation
│   ├── hero.css            # Hero section
│   ├── footer.css
│   └── admin-table.css     # Admin data tables
├── layouts/
│   ├── header.css
│   ├── grid.css            # Product grid layout
│   └── section.css         # Content sections
└── pages/
    ├── home.css
    ├── menu.css
    └── admin.css
```

**JS organization:**

```
src/frontend/js/
├── main.js                 # Entry point — imports and inits
├── api-client.js           # Fetch wrapper: base URL, error handling
├── utils.js                # Format price, slugify, debounce
├── components/
│   ├── menu-filter.js      # Category filter tabs for the menu
│   ├── product-card.js     # Product card interactivity
│   ├── admin-crud.js       # Admin CRUD operations
│   └── csv-import.js       # CSV import UI logic
└── i18n.js                 # Client-side translations
```

**No build step** — CSS source files are copied or served directly (or concatenated manually). JS uses vanilla ES modules if the target browsers support it, or concatenated scripts.

**Design System 3 application rules:**
- Primary yellow (`#f7e721`) for hero blocks, CTA buttons, accent highlights
- Secondary red (`#d32f2f`) for price tags, limited offers, warning badges
- White surface (`#ffffff`) for card backgrounds
- Light blue surface (`#edf4ff`) for section backgrounds
- Product photography is the visual hero — large, above the fold
- Yellow blocks guide attention (call-to-action, featured products)
- All border radii use `8px`

### 4. SQLite Database Schema

**Tables:**

```sql
-- Categories (11 from last.shop: Pollo, Menú diario, etc.)
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name_es TEXT NOT NULL,
    name_en TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description_es TEXT,
    description_en TEXT,
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Products (~45 from last.shop catalog)
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    name_es TEXT NOT NULL,
    name_en TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description_es TEXT,
    description_en TEXT,
    price REAL NOT NULL,  -- Stored as decimal (e.g. 19.50)
    image_url TEXT,        -- Cloudinary URL from last.shop
    last_shop_url TEXT,    -- Full URL to last.shop product page
    is_active INTEGER NOT NULL DEFAULT 1,
    is_featured INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Admin users
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    display_name TEXT NOT NULL,
    email TEXT,
    role TEXT NOT NULL DEFAULT 'admin',  -- 'admin', 'editor'
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Settings (key-value for site config)
CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    description TEXT,
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Session tokens (for admin auth)
CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Indexes for performance
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_slug ON products(slug);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_categories_slug ON categories(slug);
CREATE INDEX idx_categories_sort ON categories(sort_order);
CREATE INDEX idx_sessions_token ON sessions(token);
CREATE INDEX idx_sessions_expires ON sessions(expires_at);
```

**Seed strategy:**
1. Extract the ~45 products from last.shop (manual cataloging from the live store)
2. Create `db/seed.sql` with all categories and products
3. The `scripts/setup.php` runs schema.sql then seed.sql
4. Products include the full `last_shop_url` pointing to the specific product page
5. Prices extracted in EUR from last.shop (e.g., 19.50)
6. Images use the existing Cloudinary URLs from last.shop

**Key decisions:**
- Prices stored as REAL (decimal) since SQLite has no native DECIMAL type — formatting happens in presentation
- Bilingual fields: `name_es`/`name_en`, `description_es`/`description_en` — the app chooses based on locale
- `last_shop_url` stores the complete URL to the last.shop product for the "Order now" button
- `image_url` stores the Cloudinary URL from last.shop (not local uploads)

### 5. GitHub Workflow

**Branch strategy:**
```
main              # Protected — only via PR
├── develop       # (optional) Integration branch if teams need it
├── feat/frontend/*   # Frontend team feature branches
├── feat/backend/*    # Backend team feature branches
└── feat/shared/*     # Cross-cutting changes
```

**CODEOWNERS:**
```
# Global
* @pitocuixa/tech-lead

# Backend team
/src/backend/        @pitocuixa/backend
/src/shared/         @pitocuixa/backend
/db/                 @pitocuixa/backend
/scripts/            @pitocuixa/backend

# Frontend team
/src/frontend/       @pitocuixa/frontend
/public/css/         @pitocuixa/frontend
/public/js/          @pitocuixa/frontend
/public/img/         @pitocuixa/frontend
/src/frontend/mock/  @pitocuixa/frontend

# Infrastructure (both)
/.github/            @pitocuixa/tech-lead
/public/             @pitocuixa/frontend @pitocuixa/backend
```

**CI pipeline (`.github/workflows/ci.yml`):**

```yaml
name: CI

on:
  pull_request:
    branches: [ main ]
  push:
    branches: [ main ]

jobs:
  syntax-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: PHP Syntax Check
        run: |
          find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
```

**Additional checks (can be added):**
- CSS validity check (optional)
- Check for debug statements left in code (`var_dump`, `console.log`)
- Secret scanning

**PR process:**
1. Branch from main: `feat/frontend/menu-page` or `feat/backend/api-products`
2. PR title: Conventional commit format (e.g., `feat: add product list API endpoint`)
3. PR body: Description of changes, test evidence (screenshots for frontend)
4. At least one CODEOWNER from affected area must approve
5. CI must pass (php -l syntax check)
6. Review budget: 400 lines max per PR — if exceeded, use chained PRs

### 6. First Parallelizable Tasks

**Sprint 1 — Foundation (can run in parallel):**

**Backend team (4 people):**
| Task | Owner | Description |
|------|-------|-------------|
| B1 | Dev 1 | `db/schema.sql` — Write full schema DDL |
| B2 | Dev 2 | `scripts/setup.php` — DB setup script (create SQLite file, run schema) |
| B3 | Dev 3 | `src/backend/db/connection.php` — PDO wrapper for SQLite |
| B4 | Dev 4 | `src/shared/config.php` — App config with constants |
| B5 | Team | Extract catalog from last.shop → `db/seed.sql` |
| B6 | Dev 1+2 | `src/backend/api/products.php` — GET /api/products endpoint |
| B7 | Dev 3+4 | `src/backend/api/categories.php` — GET /api/categories endpoint |
| B8 | Dev 2 | `src/backend/router.php` — Simple PHP front controller/router |

**Frontend team (4 people):**
| Task | Owner | Description |
|------|-------|-------------|
| F1 | Dev 5 | `src/frontend/css/tokens.css` — Design System 3 CSS variables |
| F2 | Dev 6 | `src/frontend/css/base.css` — Reset, base typography |
| F3 | Dev 7 | `src/frontend/templates/layouts/default.php` — Base HTML layout |
| F4 | Dev 8 | `src/frontend/templates/partials/header.php` + `footer.php` |
| F5 | Dev 5 | `src/frontend/mock/products.json` + `categories.json` — Mock data |
| F6 | Dev 5+6 | `src/frontend/templates/pages/home.php` — Home page with hero |
| F7 | Dev 7+8 | `src/frontend/templates/pages/menu.php` — Full product menu page |
| F8 | Dev 6 | `src/frontend/js/api-client.js` — Fetch wrapper for JSON API |
| F9 | Dev 7 | `src/frontend/js/components/menu-filter.js` — Category filter tabs |

**Shared (tech lead / cross-team):**
| Task | Owner | Description |
|------|-------|-------------|
| S1 | TL | `.github/workflows/ci.yml` — CI pipeline |
| S2 | TL | `.github/CODEOWNERS` — Ownership rules |
| S3 | TL | `.gitignore` |

## Approaches

### Monorepo Structure
1. **Vertical by domain** (recommended) — `src/frontend/`, `src/backend/`, `src/shared/`
   - Pros: Clear team boundaries, CODEOWNERS works naturally, minimal merge conflicts, parallel work
   - Cons: Slightly more directory nesting
   - Effort: Low

2. **Flat by file type** — `php/`, `css/`, `js/`, `templates/` at root
   - Pros: Simpler for small projects
   - Cons: No team isolation, CODEOWNERS is complex, heavy merge conflicts with 8 people
   - Effort: Low

3. **Feature-based** — `menu/`, `admin/`, `home/` each with own PHP/CSS/JS
   - Pros: Cohesive features
   - Cons: Harder to enforce frontend/backend team boundaries, cross-cutting concerns duplicated
   - Effort: Medium

### Database Schema
1. **Single-file SQLite DB** (recommended) — One SQLite file, all tables
   - Pros: Simplest for CMS hosting, no complex configuration, standard PHP PDO
   - Cons: Concurrent writes limited (irrelevant for a pollería site)
   - Effort: Low

2. **Multiple SQLite files** — Separate DB for products vs admin
   - Pros: Thematic separation
   - Cons: JOINs across files impossible, backup complexity, no real benefit
   - Effort: Medium

### CSS Organization
1. **Component-based CSS with variables** (recommended) — tokens.css + component files
   - Pros: Scales well, Design System variables are a single source of truth, easy to maintain
   - Cons: Requires naming discipline
   - Effort: Low

2. **Single CSS file** — Everything in one file
   - Pros: Simple, no file management
   - Cons: Unmaintainable with 8 people, merge conflict nightmare
   - Effort: Low

## Recommendation

**Adopt Approach 1 for all areas.** The vertical-slice monorepo with `src/frontend/`, `src/backend/`, `src/shared/` is the clear winner for a team of 8 split into 2 subteams. It gives:

- **Parallel work**: Frontend builds templates with mock JSON while backend builds API + DB
- **Clear ownership**: CODEOWNERS maps directly to directory structure
- **No build tooling**: CSS/JS are vanilla, served directly from `public/` after manual copy
- **CMS-compatible**: `public/` as document root matches every shared PHP hosting

The first sprint should deliver:
1. A working home page and menu page showing real products from SQLite
2. Admin login and basic product management
3. CI pipeline protecting main

## Risks

1. **CSS merge conflicts** — Both teams might modify CSS. Mitigation: `CODEOWNERS` requires frontend approval for `public/css/` changes, backend only touches backend-owned files.
2. **No testing** — `php -l` is the only validation. No unit tests, no regression safety. Mitigation: Accept for MVP, add manual QA checklist per PR. Consider adding PHPUnit via manual download later.
3. **SQLite concurrent access** — Multiple admin users editing simultaneously could cause `SQLITE_BUSY`. Mitigation: For a pollería with 1-2 admin users, this is negligible. Use WAL mode in SQLite for better concurrency.
4. **last.shop dependency** — Site relies on last.shop for ordering. If last.shop changes URLs or goes down, the "Order now" links break. Mitigation: Store full URLs, not generated paths.
5. **Bilingual complexity** — All content fields need ES/EN pairs. Mitigation: The schema is already designed for it; frontend must check locale when rendering.
6. **No build step** — CSS/JS are served raw. If CSS organization grows, imports won't be bundled. Mitigation: For MVP, manually copy/concatenate. Evaluate later.

## Ready for Proposal

**Yes.** This exploration provides enough data to write a formal proposal. The orchestrator should:
1. Use this exploration to create `sdd-propose` for the `project-init` change
2. The proposal should define scope (what's in sprint 1) and approach (the vertical monorepo)
3. Proceed to spec → design → tasks → apply

Key messages for the orchestrator to tell the user:
- The vertical monorepo structure allows parallel frontend/backend work from day one
- First sprint: public menu page + admin panel + CI
- Mock JSON files let frontend start immediately without waiting for API
- All source lives in `src/`; `public/` is the deployable document root
