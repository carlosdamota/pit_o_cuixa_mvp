# Delta for SEO/GEO

## MODIFIED Requirements

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

(Previously: Menu page JSON-LD referenced the restaurant as `provider` without `address` or `geo` properties on the FoodEstablishment block.)

#### Scenario: LocalBusiness schema

- GIVEN any public page
- THEN a `LocalBusiness` JSON-LD block SHALL be present
- AND it SHALL include: `geo` (latitude/longitude for Torredembarra), `address`, `openingHours`
