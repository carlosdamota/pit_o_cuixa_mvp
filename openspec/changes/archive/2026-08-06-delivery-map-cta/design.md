# Design: Delivery Map CTA & Geo JSON-LD Sync

## Technical Approach

Two pure-SSR additions to the menu page: a plain `<a>` CTA below `.delivery-map-towns` and a JS-injected "View on Google Maps" link inside the existing Leaflet hub popup. i18n flows PHP → HTML → JS via a `data-popup-link-label` attribute on `#delivery-map` (delivery-map.js has no i18n layer — PHP owns localization, existing convention). The FoodEstablishment JSON-LD block in `menu.php` gains `address` + `geo` matching the layout's `Restaurant` schema (SG-003 delta). No new dependencies, no API key, no build step.

## Architecture Decisions

| Decision | Choice | Alternatives | Rationale |
|---|---|---|---|
| CTA element | Plain `<a target="_blank" rel="noopener">` | JS button, form POST | Progressive enhancement (DMC-006); matches project pattern |
| Popup i18n bridge | `data-popup-link-label="…"` on `#delivery-map` | Import i18n into JS, hardcode ES | JS has no i18n layer; PHP owns all localization. Spec DMC-003 mandates this |
| Coordinates source | Literal `41.1413,1.3894` in CTA + JSON-LD | `Config::geo()` shared method | `$localBusinessJsonLd` is built in layout AFTER page template is captured (`renderPage()` ob_start → layout require), so the var is NOT in scope at menu.php render time. Literal keeps the PR at the proposal's 4-file scope; comment marks the sync point. Followup refactoring to `Config::geo()`/`Config::address()` is recommended but out of scope |
| Second i18n key | Add `menu.map.cta_view` (popup link label, e.g. "Ver en Google Maps") distinct from `menu.map.cta` (CTA button label) | Reuse `menu.map.cta` for popup | DMC-003 needs "View on Google Maps" text ≠ "Cómo llegar" CTA. One key per string is the project convention |
| CSS approach | `.delivery-map-card__cta` BEM element + `:focus-visible` + tokens (`--color-secondary`, `--radius`) | Inline styles, utility classes | Match existing BEM pattern in delivery-map.css |

## Data Flow

    i18n/{es,ca,en,uk}.php
       │  'menu.map.cta'      'menu.map.cta_view'
       ▼  __() lookup
    menu.php  ──►  <a class="delivery-map-card__cta" href="…#destination=41.1413,1.3894">CTA label</a>
                   <div id="delivery-map" data-popup-link-label="…"></div>
                                  │
                                  ▼  DOMContentLoaded
    delivery-map.js  ──►  mapEl.dataset.popupLinkLabel
                          marker.bindPopup(`<strong>Hub</strong><br><a href="…maps/dir/?api=1&destination=41.1413,1.3894" target="_blank" rel="noopener">${label}</a>`)

## File Changes

| File | Action | Description |
|---|---|---|
| `src/frontend/templates/pages/menu.php` | Modify | Add CTA `<a>` after `.delivery-map-towns` (DMC-001); add `data-popup-link-label="<?= __('menu.map.cta_view') ?>"` to `#delivery-map` (DMC-003); extend FoodEstablishment JSON-LD with `address` (`PostalAddress`: streetAddress "Carrer Hort de l'Oca, 12", locality "Torredembarra", postalCode "43830", country "ES") + `geo` (`GeoCoordinates` latitude 41.1413, longitude 1.3894) — SG-003 |
| `public/js/delivery-map.js` | Modify | Read `container.dataset.popupLinkLabel`; inject `<a target="_blank" rel="noopener">` into hub marker popup (line 78 `bindPopup`) (DMC-003) |
| `public/css/components/delivery-map.css` | Modify | Add `.delivery-map-card__cta` block: `display:inline-flex`, `background: var(--color-secondary)`, `color:#fff`, `border-radius: var(--radius)`, `padding`, `font-weight: var(--font-weight-semibold)`; `:hover` lift; `:focus-visible` outline using `--color-primary` (DMC-004, DMC-005) |
| `src/shared/i18n/es.php` | Modify | Add `'menu.map.cta' => 'Cómo llegar al restaurante'` and `'menu.map.cta_view' => 'Ver en Google Maps'` near `menu.map.delivery_note` |
| `src/shared/i18n/ca.php` | Modify | `'menu.map.cta' => 'Com arribar al restaurant'`, `'menu.map.cta_view' => 'Veure a Google Maps'` |
| `src/shared/i18n/en.php` | Modify | `'menu.map.cta' => 'Get directions to the restaurant'`, `'menu.map.cta_view' => 'View on Google Maps'` |
| `src/shared/i18n/uk.php` | Modify | `'menu.map.cta' => 'Як дістатися до ресторану'`, `'menu.map.cta_view' => 'Подивитися в Google Maps'` |

**Totals:** 7 modified, 0 new, 0 deleted.

## Interfaces / Contracts

CTA HTML (menu.php):

```html
<a class="delivery-map-card__cta"
   href="https://www.google.com/maps/dir/?api=1&destination=41.1413,1.3894"
   target="_blank" rel="noopener"
   aria-label="<?= htmlspecialchars(__('menu.map.cta'), ENT_QUOTES, 'UTF-8') ?>">
   <?= htmlspecialchars(__('menu.map.cta'), ENT_QUOTES, 'UTF-8') ?>
</a>
```

Container attribute bridge:

```html
<div id="delivery-map" class="delivery-map-container"
     role="region" aria-label="<?= __('menu.map.title') ?>"
     data-popup-link-label="<?= htmlspecialchars(__('menu.map.cta_view'), ENT_QUOTES, 'UTF-8') ?>"></div>
```

JS popup injection (delivery-map.js, hub branch):

```js
const popupLabel = container.dataset.popupLinkLabel || 'View on Google Maps';
// …
marker.bindPopup(
  `<strong>${t.name}</strong><br>${t.hub ? '📍 Local principal' : '🛵 Zona con servicio'}` +
  (t.hub ? `<br><a href="https://www.google.com/maps/dir/?api=1&destination=41.1413,1.3894" target="_blank" rel="noopener">${popupLabel}</a>` : '')
);
```

JSON-LD extension (menu.php, FoodEstablishment block):

```json
"address": {
  "@type": "PostalAddress",
  "streetAddress": "Carrer Hort de l'Oca, 12",
  "addressLocality": "Torredembarra",
  "postalCode": "43830",
  "addressCountry": "ES"
},
"geo": { "@type": "GeoCoordinates", "latitude": 41.1413, "longitude": 1.3894 }
```

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Syntax | `php -l` clean on modified PHP files | `php -l src/frontend/templates/pages/menu.php` + 4 dictionaries (CI already runs this) |
| Unit | n/a | No unit runner in project (testing.unit.available=false) |
| Integration | i18n keys resolve in 4 locales | Manual: render `/menu?lang={es,ca,en,uk}`, assert CTA text matches spec table |
| E2E | CTA opens Maps directions to 41.1413,1.3894 with JS off (DMC-006); hub popup link works with JS on (DMC-003) | Manual browser; verify `destination=41.1413,1.3894` in href, popup `<a>` present |
| SEO | FoodEstablishment validates with `address` + `geo` matching layout's Restaurant | Google Rich Results Test on `/menu` |
| A11y | CTA `:focus-visible` outline visible; contrast ≥ 4.5:1 (#d32f2f bg / #fff text ≈ 4.6:1) | DevTools computed styles + contrast checker (DMC-004, DMC-005) |
| JS | No console errors; popup link opens correct Maps URL | Browser console on `/menu` after clicking hub marker |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. Pure template/CSS/JS edit confined to the menu page.

## Migration / Rollout

No migration required. Revert = remove CTA + JSON-LD fields from `menu.php`, popup link from `delivery-map.js`, two keys from 4 dictionaries, CSS block. 7-file pure HTML/CSS/JS revert, versioned static assets.

## Open Questions

- [ ] Confirm adding the 2nd i18n key `menu.map.cta_view` (proposal listed only `menu.map.cta`). Reviewer may consolidate into one key if they prefer "Get directions to the restaurant" inside the popup too.
- [ ] Out-of-scope followup: extract `address`/`geo` into `Config::address()` / `Config::geo()` so layout's `$localBusinessJsonLd` and menu.php share a single source of truth (mirrors existing `Config::phone()` pattern). Tracked as a separate change to keep this PR at the 4-file proposal scope.