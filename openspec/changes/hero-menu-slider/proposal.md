# Proposal: Hero Menu Slider

## Intent

Replace the text-only hero on `/menu` with an image slider (autoplay 5s, swipe). Admin toggle in Settings controls visibility. When disabled OR no images, a CSS-only decorative hero renders. Home unchanged.

## Scope

### In Scope
- Vanilla JS slider (CSS `translateX`, autoplay 5s, Pointer Events swipe, keyboard nav)
- Admin Settings page with toggle (new SQLite `settings` table)
- Conditional: slider when flag ON + images exist, CSS fallback otherwise
- CSS-only fallback hero (gradient + pattern + styled text)
- Accessible (ARIA carousel, `prefers-reduced-motion`), i18n (ca/es/en)

### Out of Scope
- Home page, image upload UI (manual FTP), third-party libs

## Capabilities

### New
- `menu-slider`: Slider — autoplay, swipe, keyboard nav, conditional display, CSS fallback, accessibility

### Modified
- `admin-panel`: Settings section with slider toggle (`settings` table + page + API)

## Approach

Vanilla JS + CSS Transitions. ~150-line ESM module, `translateX()` + `transition: 0.4s ease`. Pointer Events swipe (50px). Infinite loop + dot nav.

**Settings**: SQLite `settings(key, value)`. `menu_slider_enabled` default `'0'`.
**Conditional**: `show_slider = flag AND images exist`.
**Fallback**: CSS gradient + pattern. Zero image dependency.
**Budget**: ~350-400 lines. Single PR.

## Affected Areas

| Area | Impact |
|------|--------|
| `menu.php` template | Modified — conditional slider/fallback |
| `public/js/menu-slider.js` | New — ESM slider |
| `public/js/main.js` | Modified — init |
| `public/css/components/menu-slider.css` | New — styles |
| `src/backend/pages/menu.php` | Modified — settings + image scan |
| `admin/settings.php` (backend + template) | New — controller + UI |
| `api/AdminSettings.php` | New — GET/PUT API |
| `router.php` | Modified — routes |
| `db/schema.sql` + migration `002` | New — settings table |
| `i18n/{ca,es,en}.php` | Modified — keys |
| `sw.js` | Modified — cache |

## Risks

| Risk | Mitigation |
|------|------------|
| No images uploaded | CSS fallback renders |
| Swipe vs scroll conflict | 50px threshold + `touch-action: pan-y` |
| Admin forgets toggle | Clear UI copy on conditions |

## Rollback Plan

1. Toggle OFF in Settings → fallback immediately
2. `git revert` → original hero restored
3. `settings` table additive — safe to drop

## Dependencies

- Owner uploads images to `/img/menu-slider/`

## Success Criteria

- [ ] Autoplay 5s + CSS transitions
- [ ] Touch swipe (Pointer Events, 50px)
- [ ] Keyboard nav (arrows)
- [ ] Admin toggle ON/OFF
- [ ] Flag OFF / no images → CSS fallback
- [ ] Flag ON + images → slider
- [ ] Home unchanged
- [ ] `prefers-reduced-motion`
- [ ] ARIA passes axe
- [ ] No deps added
- [ ] `php -l` passes
