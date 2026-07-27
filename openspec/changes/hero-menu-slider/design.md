# Design: Hero Menu Slider

## Technical Approach

Two capabilities, one conditional hero on `/menu`. SSR decides via `show_slider = (settings.menu_slider_enabled === '1') AND count(scanned images) > 0`. Controller passes `show_slider` + `slider_images[]` to the menu template; the template renders either the slider markup (fed to a vanilla ESM module) or the CSS-only fallback. Admin `/admin/settings` page + `AdminSettings` API persist the toggle in a new SQLite `settings(key,value)` table. No new dependencies, no build step. Maps 1:1 to specs MS-001..MS-009 and AP-007..AP-010.

## Architecture Decisions

| # | Decision | Choice | Rejected alternative | Rationale |
|---|----------|--------|----------------------|-----------|
| 1 | Slider engine | Vanilla JS + `transform: translateX()`, `transition: .4s ease` | `scroll-snap` carousel | Full control of loops, dots, reduced-motion; avoids scroll-snap jank |
| 2 | Input handling | Pointer Events (`pointerdown/move/up`), 50px threshold, `touch-action: pan-y` | `touchstart` + `mousedown` split | Unifies touch+mouse, lets vertical scroll pass through |
| 3 | Image discovery | Server-side `glob('/img/menu-slider/*')`, filter ext, `sort()` | client fetch JSON | Keeps SSR truthful; slider content visible before JS runs; no extra route |
| 4 | Conditional logic | `show_slider = flag==='1' AND images>0` computed in `Menu::render` | template does filesystem check | One source of truth; template stays dumb; JS module only loaded when needed |
| 5 | Settings storage | SQLite `settings(key TEXT PK, value TEXT NOT NULL)` | `.env` flag, JSON file | Spec AP-008 mandates table; admin-mutable; survives deploys |
| 6 | Migration strategy | Add table to `schema.sql` (new installs) **+** idempotent `002_settings.sql` (existing DBs) **+** `Settings::ensureSchema()` self-heal | add migration runner framework | No migration runner exists; round trip `setup.php` only runs `schema.sql`; self-heal guards existing un-migrated installs from 500s |
| 7 | Slider CSS load | `/css/components/menu-slider.css` loaded when `$pageName === 'menu'` (always) | load only when slider active | Layout branches on `$pageName` not `$pageData`; file also holds fallback hero styles; ~6KB, cache-first via SW |
| 8 | Slider JS load | imported in `main.js`, init guarded by `[data-menu-slider]` presence | per-page `<script>` injection | Matches `initMenuFilter` ESM guard pattern; payload only matters when element exists |
| 9 | Autoplay pause | `focusin`/`mouseenter`/`visibilitychange`/pointer interaction → pause; resume after 5s idle | `setInterval` only | WCAG 2.2.1 pause control; tab-invisible battery save |
| 10 | Reduced motion | `@media (prefers-reduced-motion: reduce)` + `matchMedia` JS gate | CSS only | Both autoplay disable and instant transitions need JS+CSS |

## Data Flow

```
GET /menu ─► Menu::render()
              ├─ Settings::get('menu_slider_enabled')  ─► SQLite settings (ensureSchema)
              ├─ glob('/img/menu-slider/*') ─► filter ext ─► sort()
              └─ show_slider = flag==='1' && count>0
                                                │
                ┌───────────────────────────────┴────────────────────┐
              true                                             false
                ▼                                                 ▼
      menu.php echoes slider markup                        menu.php echoes
      ([data-menu-slider], slides, dots, ARIA)              CSS fallback hero
                ▼
      main.js ➜ initMenuSlider() ➜ autoplay/swipe/keyboard

Admin:  PUT /api/admin/settings {menu_slider_enabled:'1'}
        ─► Auth::requireToken + validateCsrfToken
        ─► Settings::set (whitelist) ─► SQLite ─► JSON
        GET /admin/settings ─► Settings::render() ─► admin/settings.php (toggle UI)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/backend/db/repositories/settings.php` | Create | `Settings` repo: `get(key,default)`, `all()`, `set(key,value)`, `ensureSchema()` (CREATE TABLE IF NOT EXISTS + INSERT OR IGNORE default) |
| `src/backend/pages/admin/settings.php` | Create | `Settings::render()` — `Auth::requireSession()`, load `menu_slider_enabled` + image count (for UX copy), pass to template |
| `src/backend/api/AdminSettings.php` | Create | `get()` / `update()` — token+CSRF; whitelist `menu_slider_enabled` to `'0'|'1'`; reject unknown keys with 400 |
| `src/frontend/templates/pages/admin/settings.php` | Create | Admin layout + `.admin-field` toggle (checkbox `is_active`-style) + alert placeholders; calls `admin.api('PUT', '/api/admin/settings', body)` |
| `src/frontend/templates/pages/menu.php` | Modify | Replace `.menu-hero` with `if($show_slider)` slider markup else fallback hero; keep filter-bar + products untouched |
| `src/backend/pages/menu.php` | Modify | Compute `show_slider` + `slider_images`; pass into `$data` |
| `public/js/menu-slider.js` | Create | ~150-line ESM module: autoplay 5s, Pointer Events swipe, ArrowLeft/Right + Home/End, dots, `aria-live`, `matchMedia('reduce')` gate |
| `public/js/main.js` | Modify | `import { initMenuSlider } from './menu-slider.js'`; call in `init()` |
| `public/css/components/menu-slider.css` | Create | BEM `.menu-slider__*` + `.menu-hero--fallback`; `aspect-ratio:16/9`, `object-fit:cover`, `@media (prefers-reduced-motion)`, `touch-action:pan-y` |
| `src/frontend/templates/layouts/default.php` | Modify | Load `menu-slider.css` when `$pageName === 'menu'` |
| `src/frontend/templates/partials/admin-nav.php` | Modify | Add "Ajustes" link `/admin/settings` |
| `public/index.php` | Modify | `use AdminSettings`; register `GET /admin/settings`, `GET /api/admin/settings`, `PUT /api/admin/settings` |
| `db/schema.sql` | Modify | Add `settings` table + `INSERT OR IGNORE` default `menu_slider_enabled='0'` |
| `db/migrations/002_settings.sql` | Create | Idempotent copy of table+seed for existing DBs |
| `src/shared/i18n/{ca,es,en}.php` | Modify | Add `admin.settings.*`, `menu.slider.*` (carousel aria label, slide N of N, prev/next, dots) |
| `public/sw.js` | Modify | Bump `static` cache name → `static-v2`; no new strategy (slider images already covered by `images-v1` cache-first) |
| `public/img/menu-slider/` | Create | Empty dir + `.gitkeep` (owner uploads WebP/JPEG/PNG manually) |

## Interfaces / Contracts

```php
// Settings repo
final class Settings {
  public static function ensureSchema(): void;
  public static function get(string $key, string $default = ''): string;
  public static function all(): array;             // ['menu_slider_enabled' => '1', ...]
  public static function set(string $key, string $value): void;
}

// AdminSettings API envelope
// GET  /api/admin/settings        → {error:false, data:{menu_slider_enabled:'1', image_count:3}}
// PUT  /api/admin/settings        body {menu_slider_enabled:'1'}
//                                → {error:false, data:{...}}  (400 on unknown key/non-binary)
```

```html
<!-- Slider markup (emitted only when show_slider) -->
<section class="menu-slider section" data-menu-slider
  role="region" aria-roledescription="carousel"
  aria-label="<?= __('menu.slider.aria') ?>">
  <div class="menu-slider__viewport" tabindex="0">
    <div class="menu-slider__track">
      <?php foreach ($slider_images as $i => $img): ?>
        <figure class="menu-slider__slide" role="group"
                aria-roledescription="slide"
                aria-label="<?= __('menu.slider.slide_n', [$i+1, count($slider_images)]) ?>">
          <img src="<?= htmlspecialchars($img, ENT_QUOTES) ?>"
               alt="<?= __('menu.slider.image_alt', [$i+1]) ?>"
               loading="<?= $i===0 ? 'eager' : 'lazy' ?>"
               decoding="async" width="1200" height="675">
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
  <ol class="menu-slider__dots" aria-label="<?= __('menu.slider.dots') ?>"><?php /* buttons w/ aria-current */ ?></ol>
</section>
```

```js
// menu-slider.js public surface
export function initMenuSlider(): void; // noop if [data-menu-slider] absent
```

## Testing Strategy

| Layer | What | Approach |
|-------|------|----------|
| Unit | Settings repo get/set/ensureSchema idempotency | PHP: temp SQLite, assert default `'0'`, set/get round-trip, re-call `ensureSchema` no error |
| Unit | AdminSettings input validation | Unknown key → 400; non `'0'/'1'` → 422; CSRF missing → 403 |
| Integration | Conditional rendering | Boot `Menu::render` with flag=0/1 and slider dir empty/filled → assert slider HTML present/absent and CSS fallback present/absent |
| Integration | Auth gate | Unauthenticated `GET /admin/settings` redirects to `/admin/login`; `GET /api/admin/settings` → 401 |
| E2E manual | Autoplay 5s, swipe 50px, ArrowKeys wrap, dots, reduced-motion disables autoplay | Browser DevTools + `prefers-reduced-motion` emulation |
| Static | `php -l` on every new/changed PHP file | CI/CLI gate |
| A11y | ARIA carousel pattern + focus order | axe DevTools pass |

## Migration / Rollout

1. Deploy code. 2. `Settings::ensureSchema()` lazily creates `settings` table + seeds `menu_slider_enabled='0'` on first menu/settings load — existing DBs self-heal, no manual SQL needed; `002_settings.sql` provided for explicit migrations. 3. Slider stays OFF by default (fallback hero) until admin toggles ON **and** uploads images to `/img/menu-slider/`. Rollback: toggle OFF in Settings, or `git revert`; `settings` table additive (safe to drop).

## Open Questions

- [ ] Should the Settings page also surface `image_count` / a "upload hint" (no upload UI in scope) — recommend show count + hint text only.
- [ ] Preferred first-slide preload (`<link rel="preload">`) vs rely on `loading="eager"` — recommend eager only, defer preload perf-op until measured.