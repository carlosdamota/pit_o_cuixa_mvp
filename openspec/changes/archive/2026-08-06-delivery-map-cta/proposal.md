# Proposal: Delivery Map CTA & Geo JSON-LD Sync

## Intent

The menu page delivery map (`menu.php` L299-332) shows 6 towns and a 🍗 marker but offers no action: no directions affordance for visitors wanting to visit or order takeaway. Geo data exists only in the layout's `$localBusinessJsonLd` (41.1413, 1.3894). This adds a prominent "Cómo llegar" CTA so the map converts interest into footfall, and syncs the menu page's FoodEstablishment JSON-LD with `address`/`geo` for local SEO.

## Scope

### In Scope
- CTA button "Cómo llegar" inside `.delivery-map-card`, after the towns bar (shared across visitors)
- "View on Google Maps" link in the 🍗 hub marker popup (`delivery-map.js`)
- i18n key `menu.map.cta` in all **4 active locales** (es, ca, en, uk)
- `address`/`geo` added to menu.php FoodEstablishment JSON-LD

### Out of Scope
- Map redesign, extra pins, Leaflet upgrades
- CTA on other pages; Google Maps iframe embed
- Fixing pre-existing coordinate drift between map towns (41.1444) and layout geo (41.1413)

## Capabilities

### New Capabilities
- `delivery-map-cta`: directions affordance for the delivery map — CTA to Google Maps directions, popup "View on Google Maps" link, localized labels

### Modified Capabilities
- `seo-geo`: SG-003 Menu schema — FoodEstablishment JSON-LD gains `address` + `geo` matching the layout's Restaurant schema

## Approach

1. **CTA**: plain `<a class="delivery-map-card__cta" href="https://www.google.com/maps/dir/?api=1&destination=41.1413,1.3894" target="_blank" rel="noopener">` after `.delivery-map-towns` — no JS, no API key, opens Maps app on mobile (existing project pattern).
2. **i18n**: `menu.map.cta` → es "Cómo llegar al restaurante", ca "Com arribar al restaurant", en "Get directions to the restaurant", uk "Як дістатися до ресторану".
3. **Popup link**: extend `bindPopup` (L78) in `delivery-map.js`; expose label via `data-` attribute on `#delivery-map` (JS has no i18n layer).
4. **JSON-LD**: add `address` (Carrer Hort de l'Oca, 12, 43830) + `geo` (41.1413, 1.3894) to FoodEstablishment block.
5. **CSS**: BEM modifier + design tokens in `delivery-map.css`.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/frontend/templates/pages/menu.php` | Modified | CTA in `.delivery-map-card`; `address`/`geo` in JSON-LD |
| `public/js/delivery-map.js` | Modified | "View on Google Maps" link in hub popup |
| `src/shared/i18n/{es,ca,en,uk}.php` | Modified | `menu.map.cta` key (4 locales — exploration said 3; uk active) |
| `public/css/components/delivery-map.css` | Modified | CTA styles (BEM, tokens) |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Missing uk.php key → untranslated fallback | Med | Add key to all 4 dictionaries in one task |
| Maps URL stale if coordinates change | Low | Destination mirrors `$localBusinessJsonLd.geo` |
| Popup label hardcoded Spanish | Med | `data-` attribute; verify in 4 locales |

## Rollback Plan

Revert 4 files: remove CTA + JSON-LD fields from `menu.php`, popup link from `delivery-map.js`, key from 4 dictionaries, CSS block. Pure HTML/CSS revert — no data migration, versioned assets.

## Dependencies

- None (Google Maps `dir` endpoint needs no API key)

## Success Criteria

- [ ] CTA renders localized in es/ca/en/uk
- [ ] Clicking CTA opens directions to 41.1413, 1.3894 (no JS)
- [ ] Hub popup shows working "View on Google Maps" link
- [ ] FoodEstablishment JSON-LD validates (Rich Results Test)
- [ ] `php -l` clean; no JS console errors

> **Assumption (auto mode)**: uk locale included per active `['ca','es','en','uk']` config, correcting exploration's 3-language list. Reviewer may drop uk.
