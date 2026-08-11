# Delivery Map CTA Specification

## Purpose

Directions affordance for the menu page delivery map: a prominent CTA button linking to Google Maps directions, a "View on Google Maps" link in the hub marker popup, and localized labels across all 4 active locales (es, ca, en, uk).

This change also enriches the FoodEstablishment JSON-LD block on the menu page with `address` and `geo` properties, ensuring structured data aligns with the canonical address and coordinates (SG-003).

## Requirements

### DMC-001: CTA Button Rendering

The system MUST render a CTA link inside `.delivery-map-card`, positioned after `.delivery-map-towns`.

The CTA MUST be an `<a>` element with class `delivery-map-card__cta`, `target="_blank"`, `rel="noopener"`, and `href` pointing to `https://www.google.com/maps/dir/?api=1&destination={lat},{lng}` using coordinates from `$localBusinessJsonLd.geo` (41.1413, 1.3894).

#### Scenario: CTA renders with correct link

- GIVEN the menu page is rendered
- WHEN `.delivery-map-card` is inspected
- THEN an `<a class="delivery-map-card__cta">` SHALL be present after `.delivery-map-towns`
- AND its `href` SHALL contain `destination=41.1413,1.3894`

#### Scenario: CTA security attributes

- GIVEN the CTA link is rendered
- WHEN its attributes are inspected
- THEN it SHALL have `target="_blank"` AND `rel="noopener"`

### DMC-002: CTA Internationalization

The system MUST provide the CTA label via i18n key `menu.map.cta` in all 4 active locales.

| Locale | Value |
|--------|-------|
| es | Cómo llegar al restaurante |
| ca | Com arribar al restaurant |
| en | Get directions to the restaurant |
| uk | Як дістатися до ресторану |

#### Scenario: Localized label rendered

- GIVEN the current locale is one of es, ca, en, uk
- WHEN the menu page renders
- THEN the CTA text content SHALL equal the `menu.map.cta` translation for that locale

#### Scenario: All locale keys present

- GIVEN the 4 locale files (es.php, ca.php, en.php, uk.php)
- WHEN `menu.map.cta` is looked up in each
- THEN a non-empty string SHALL be returned in all 4 files

### DMC-003: Popup Directions Link

The system MUST include a "View on Google Maps" link inside the hub marker popup in `delivery-map.js`. The link label MUST be passed via a `data-` attribute on the `#delivery-map` container (JS has no i18n layer).

#### Scenario: Popup contains directions link

- GIVEN the delivery map is initialized with JS
- WHEN the user clicks the hub marker
- THEN the popup SHALL contain a link to Google Maps directions for 41.1413, 1.3894

#### Scenario: Popup label from data attribute

- GIVEN `#delivery-map` has a `data-popup-link-label` attribute
- WHEN the popup renders
- THEN the link text SHALL match that attribute's value

### DMC-004: CTA Styling

The system MUST style the CTA using BEM conventions and design tokens in `delivery-map.css`. The CTA SHOULD use the secondary color (#d32f2f) and 8px border radius.

#### Scenario: Design token usage

- GIVEN the CTA is rendered
- WHEN computed styles are inspected
- THEN it SHALL use design token values for color, border-radius, and spacing

#### Scenario: Focus-visible indicator

- GIVEN a keyboard user tabs to the CTA
- WHEN the CTA receives focus
- THEN a `:focus-visible` outline SHALL be visible

### DMC-005: CTA Accessibility

The CTA MUST meet WCAG 2.1 AA requirements.

#### Scenario: Accessible label

- GIVEN the CTA is rendered
- WHEN inspected by assistive technology
- THEN it SHALL have an `aria-label` matching the visible localized text

#### Scenario: Contrast ratio

- GIVEN the CTA text and background colors
- WHEN contrast ratio is measured
- THEN it SHALL meet WCAG AA minimum (4.5:1 for normal text)

### DMC-006: Progressive Enhancement

The CTA MUST function without JavaScript.

#### Scenario: CTA works with JS disabled

- GIVEN JavaScript is disabled in the browser
- WHEN the user clicks the CTA
- THEN the Google Maps directions page SHALL open

#### Scenario: Popup degrades gracefully

- GIVEN JavaScript is disabled
- WHEN the delivery map section renders
- THEN the CTA button SHALL remain functional (popup is JS-dependent and MAY be unavailable)

### SG-003: JSON-LD Structured Data

The system MUST embed JSON-LD structured data for rich search results.

#### Scenario: Restaurant schema on home page

- GIVEN the home page
- WHEN the HTML is inspected
- THEN a `<script type="application/ld+json">` block SHALL contain a `Restaurant` schema
- AND it SHALL include: `@type: "Restaurant"`, `name`, `address`, `telephone`, `openingHours`, `priceRange`, `image`, `url`

#### Scenario: Menu page FoodEstablishment schema

- GIVEN the menu page
- WHEN the HTML is inspected
- THEN a JSON-LD block SHALL contain a `FoodEstablishment` schema
- AND it SHALL include `address` (`PostalAddress` with `streetAddress`: "Carrer Hort de l'Oca, 12", `addressLocality`: "Torredembarra", `postalCode`: "43830", `addressCountry`: "ES")
- AND it SHALL include `geo` (`GeoCoordinates` with `latitude`: 41.1413, `longitude`: 1.3894)
- AND these values SHALL match the layout's Restaurant schema coordinates

#### Scenario: LocalBusiness schema

- GIVEN any public page
- THEN a `LocalBusiness` JSON-Ld block SHALL be present
- AND it SHALL include: `geo` (latitude/longitude for Torredembarra), `address`, `openingHours`
