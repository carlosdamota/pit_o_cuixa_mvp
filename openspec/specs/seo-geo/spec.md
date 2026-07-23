# SEO/GEO Specification

## Purpose

Search engine optimization and geographic discoverability: structured data (JSON-LD), Open Graph meta tags, XML sitemap, hreflang bilingual linking, and local business geo metadata. Targets Lighthouse SEO ≥ 90 and visibility in local search results for "pollería Torredembarra".

## Requirements

### SG-001: Meta Tags

Every page MUST include complete HTML meta tags for SEO.

#### Scenario: Page meta tags

- GIVEN any public page (home, menu)
- WHEN the HTML `<head>` is rendered
- THEN it SHALL include: `<title>`, `<meta name="description">`, `<meta name="viewport">`, `<link rel="canonical">`
- AND `<title>` and `description` SHALL be localized per current locale

#### Scenario: Unique titles per page

- GIVEN the home page and menu page
- THEN their `<title>` values SHALL be different
- AND each SHALL accurately describe the page content

### SG-002: Open Graph Tags

Every page MUST include Open Graph meta tags for social media sharing.

#### Scenario: OG tags present

- GIVEN any public page
- WHEN the HTML `<head>` is rendered
- THEN it SHALL include: `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, `og:locale`
- AND `og:image` SHALL point to a valid image URL (minimum 1200x630px)

#### Scenario: Social share preview

- GIVEN the page URL is shared on a social platform
- WHEN the platform fetches OG metadata
- THEN the preview SHALL show the correct title, description, and image

### SG-003: JSON-LD Structured Data

The system MUST embed JSON-LD structured data for rich search results.

#### Scenario: Restaurant schema on home page

- GIVEN the home page
- WHEN the HTML is inspected
- THEN a `<script type="application/ld+json">` block SHALL contain a `Restaurant` schema
- AND it SHALL include: `@type: "Restaurant"`, `name`, `address`, `telephone`, `openingHours`, `priceRange`, `image`, `url`

#### Scenario: Menu schema on menu page

- GIVEN the menu page
- WHEN the HTML is inspected
- THEN a JSON-LD block SHALL contain a `Menu` schema
- AND it SHALL reference the restaurant as `provider`

#### Scenario: LocalBusiness schema

- GIVEN any public page
- THEN a `LocalBusiness` JSON-LD block SHALL be present
- AND it SHALL include: `geo` (latitude/longitude for Torredembarra), `address`, `openingHours`

### SG-004: XML Sitemap

The system MUST generate and serve a valid XML sitemap.

#### Scenario: Sitemap is accessible

- GIVEN the site is deployed
- WHEN a client requests `/sitemap.xml`
- THEN the response SHALL be valid XML with `Content-Type: application/xml`
- AND it SHALL list all public pages: `/`, `/menu`, `/es/`, `/en/`

#### Scenario: Sitemap includes hreflang annotations

- GIVEN the sitemap contains the home page URL
- THEN it SHALL include `<xhtml:link rel="alternate" hreflang="es" href="...">` and `hreflang="en"` entries

### SG-005: Hreflang Bilingual Links

Every public page MUST declare its alternate-language version via hreflang.

#### Scenario: Hreflang in HTML

- GIVEN the Spanish version of the home page (`/es/` or `/`)
- WHEN the `<head>` is inspected
- THEN it SHALL include: `<link rel="alternate" hreflang="es" href="...">` and `<link rel="alternate" hreflang="en" href="...">`
- AND an `x-default` hreflang SHALL point to the Spanish version

#### Scenario: Hreflang bidirectional

- GIVEN the English version of a page
- THEN its hreflang links SHALL point back to the Spanish version
- AND vice versa (bidirectional confirmation)

### SG-006: Geographic Meta Tags

The system MUST include geo-specific meta tags for local search.

#### Scenario: Geo meta tags

- GIVEN any public page
- WHEN the `<head>` is rendered
- THEN it SHALL include: `<meta name="geo.region">`, `<meta name="geo.placename">`, `<meta name="geo.position">`, `<meta name="ICBM">`
- AND values SHALL reference Torredembarra, Tarragona, Spain (coordinates ~41.1412, 1.3939)

### SG-007: Robots and Crawlability

The system MUST allow search engine crawling and provide a robots.txt.

#### Scenario: robots.txt

- GIVEN a client requests `/robots.txt`
- THEN the response SHALL allow all crawlers: `User-agent: *` / `Allow: /`
- AND it SHALL reference the sitemap: `Sitemap: https://pitocuixa.es/sitemap.xml`

#### Scenario: No blocking of public pages

- GIVEN the site is deployed
- THEN no public page SHALL have `<meta name="robots" content="noindex">`

## Contracts

### JSON-LD: Restaurant (Home Page)

```json
{
  "@context": "https://schema.org",
  "@type": "Restaurant",
  "name": "Pit o Cuixa",
  "image": "https://pitocuixa.es/img/og-image.jpg",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "...",
    "addressLocality": "Torredembarra",
    "addressRegion": "Tarragona",
    "postalCode": "43800",
    "addressCountry": "ES"
  },
  "telephone": "+34...",
  "priceRange": "€€",
  "openingHours": "Mo-Su 11:00-23:00",
  "url": "https://pitocuixa.es",
  "servesCuisine": "Spanish",
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 41.1412,
    "longitude": 1.3939
  }
}
```

### Sitemap Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
  <url>
    <loc>https://pitocuixa.es/</loc>
    <xhtml:link rel="alternate" hreflang="es" href="https://pitocuixa.es/" />
    <xhtml:link rel="alternate" hreflang="en" href="https://pitocuixa.es/en/" />
    <xhtml:link rel="alternate" hreflang="x-default" href="https://pitocuixa.es/" />
  </url>
  <!-- ... more URLs ... -->
</urlset>
```

## Constraints

- Sitemap MUST be regenerated when products/pages change (or be dynamically generated via PHP)
- JSON-LD MUST validate against Google's Structured Data Testing Tool
- OG image must be a static asset (not dynamically generated in Sprint 1)
- Coordinates are approximate — exact address coordinates TBD
- No JavaScript-rendered content for SEO — all critical content in server-rendered HTML

## Acceptance Criteria

- [ ] Every page has unique `<title>` and `<meta description>` (localized)
- [ ] OG tags present and valid on all pages
- [ ] JSON-LD validates (Restaurant, Menu, LocalBusiness schemas)
- [ ] `/sitemap.xml` is accessible and lists all public URLs with hreflang
- [ ] Hreflang tags are bidirectional on every page
- [ ] Geo meta tags reference Torredembarra coordinates
- [ ] `/robots.txt` allows crawling and references sitemap
- [ ] Lighthouse SEO score ≥ 90
- [ ] Google Rich Results Test passes for Restaurant schema
