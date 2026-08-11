# Tasks: Delivery Map CTA & Geo JSON-LD Sync

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~55 (7 modified, 0 new) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | auto-chain |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 — Backend | i18n keys + menu.php (CTA, data bridge, JSON-LD) | PR 1 | `php -l src/frontend/templates/pages/menu.php` + 4 dicts | `php -S localhost:8000`, render `/menu?lang={es,ca,en,uk}` | Revert menu.php + 4 i18n files (pure HTML revert) |
| 2 — Frontend | delivery-map.js popup link + CSS | PR 1 | `php -l` (no JS runner); console error check | Manual browser `/menu` (Leaflet needs network) | Revert delivery-map.js + delivery-map.css |

## Phase 1: Backend — i18n keys (foundation)

- [x] 1.1 `src/shared/i18n/es.php` L450: add `'menu.map.cta' => 'Cómo llegar al restaurante'`, `'menu.map.cta_view' => 'Ver en Google Maps'` after `menu.map.delivery_note`. Verify: `php -l` + `grep menu.map.cta`. (~2)
- [x] 1.2 `src/shared/i18n/ca.php` L449: add `'Com arribar al restaurant'` / `'Veure a Google Maps'`. Verify: `php -l`. (~2)
- [x] 1.3 `src/shared/i18n/en.php` L451: add `'Get directions to the restaurant'` / `'View on Google Maps'`. Verify: `php -l`. (~2)
- [x] 1.4 `src/shared/i18n/uk.php` L321: add `'Як дістатися до ресторану'` / `'Подивитися в Google Maps'`. Verify: `php -l` (risk: missing uk key → fallback). (~2)

## Phase 2: Backend — menu.php template

- [x] 2.1 After `.delivery-map-towns` (L325) add `<a class="delivery-map-card__cta" href="https://www.google.com/maps/dir/?api=1&destination=41.1413,1.3894" target="_blank" rel="noopener" aria-label="<?= htmlspecialchars(__('menu.map.cta'), ENT_QUOTES, 'UTF-8') ?>">` (DMC-001, DMC-005, DMC-006). Verify: grep `destination=41.1413,1.3894`. (~7)
- [x] 2.2 Add `data-popup-link-label="<?= htmlspecialchars(__('menu.map.cta_view'), ENT_QUOTES, 'UTF-8') ?>"` to `#delivery-map` div (L312) (DMC-003). Verify: grep in rendered HTML. (~1)
- [x] 2.3 Extend FoodEstablishment JSON-LD (L334-368): add `address` (PostalAddress: streetAddress "Carrer Hort de l'Oca, 12", locality Torredembarra, postalCode 43830, country ES) + `geo` (41.1413, 1.3894) after `telephone` (SG-003). Verify: CI JSON validation; matches layout Restaurant coords. (~12)

## Phase 3: Frontend — delivery-map.js

- [x] 3.1 In `initDeliveryMap(container)` read `const popupLabel = container.dataset.popupLinkLabel || 'View on Google Maps'` (DMC-003). Verify: console shows no error. (~1)
- [x] 3.2 Hub branch of `bindPopup` (L78): append `<br><a href="https://www.google.com/maps/dir/?api=1&destination=41.1413,1.3894" target="_blank" rel="noopener">${popupLabel}</a>` (DMC-003). Verify: click 🍗 marker → link with attribute label. (~3)

## Phase 4: Frontend — delivery-map.css

- [x] 4.1 Add `.delivery-map-card__cta`: `display:inline-flex`, `background:var(--color-secondary)`, `color:#fff`, `border-radius:var(--radius)`, padding, `font-weight:var(--font-weight-semibold)` (DMC-004). Verify: computed styles use tokens. (~10)
- [x] 4.2 Add `:hover` lift + `:focus-visible` outline `var(--color-primary)` (DMC-004, DMC-005). Verify: tab to CTA shows outline. (~6)

## Phase 5: Verification

- [x] 5.1 Run `php -l` on menu.php + 4 dictionaries; run `php scripts/test-sync.php` (regression). 
- [x] 5.2 Manual: `/menu?lang={es,ca,en,uk}` — CTA text per DMC-002 table; JS off → CTA opens Maps (DMC-006); JS on → hub popup link (DMC-003). 
- [x] 5.3 A11y: CTA `:focus-visible` outline visible; contrast #d32f2f/#fff ≥ 4.5:1 (DMC-004, DMC-005). 
- [x] 5.4 SEO: Google Rich Results Test on `/menu` — FoodEstablishment has `address`+`geo` matching layout (SG-003). 
