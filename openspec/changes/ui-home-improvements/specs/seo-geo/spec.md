# Delta for SEO/GEO

## ADDED Requirements

### SG-008: FAQ Page Meta Tags

The FAQ page MUST include complete HTML meta tags consistent with existing pages: localized `<title>`, `<meta description>`, canonical URL, hreflang alternates, and Open Graph tags.

#### Scenario: FAQ page meta tags

- GIVEN the FAQ page at `/faq`
- WHEN the HTML `<head>` is rendered
- THEN it SHALL include: `<title>`, `<meta name="description">`, `<link rel="canonical">`
- AND `<title>` and `description` SHALL be localized per current locale
- AND the canonical URL SHALL point to the current locale's FAQ URL

#### Scenario: FAQ page OG tags

- GIVEN the FAQ page
- WHEN the HTML `<head>` is rendered
- THEN it SHALL include: `og:title`, `og:description`, `og:url`, `og:type`, `og:locale`
- AND `og:url` SHALL point to the FAQ page URL

#### Scenario: FAQ page hreflang

- GIVEN the FAQ page in Spanish (`/faq` or `/es/faq`)
- WHEN the `<head>` is inspected
- THEN it SHALL include hreflang alternates for es, en, and x-default
- AND the English alternate SHALL point to `/en/faq`

### SG-009: FAQPage JSON-LD on FAQ Page

The FAQ page MUST embed a `FAQPage` JSON-LD schema block. This is in addition to the existing `LocalBusiness` schema present on all pages.

#### Scenario: FAQPage schema on FAQ page

- GIVEN the FAQ page is rendered
- WHEN the HTML is inspected
- THEN a `<script type="application/ld+json">` block SHALL contain `@type: "FAQPage"`
- AND `mainEntity` SHALL be an array of `Question` objects
- AND each `Question` SHALL have `name` (the question) and `acceptedAnswer` with `text` (the answer)
- AND the content SHALL match the visible FAQ entries on the page

#### Scenario: FAQ schema validates with Google

- GIVEN the FAQ page JSON-LD
- WHEN validated with Google Rich Results Test
- THEN it SHALL pass without errors
- AND the FAQ rich result SHALL be eligible

### SG-010: Sitemap Includes FAQ Page

The XML sitemap MUST include the FAQ page URL with hreflang annotations.

#### Scenario: Sitemap includes FAQ

- GIVEN the sitemap at `/sitemap.xml`
- WHEN the XML is inspected
- THEN it SHALL include entries for `/faq`, `/es/faq`, `/en/faq`
- AND each entry SHALL include hreflang alternates for es, en, and x-default

## MODIFIED Requirements

### SG-001: Meta Tags

Every page MUST include complete HTML meta tags for SEO. This now includes the FAQ page.

(Previously: Applied to home and menu pages only.)

#### Scenario: Page meta tags

- GIVEN any public page (home, menu, faq)
- WHEN the HTML `<head>` is rendered
- THEN it SHALL include: `<title>`, `<meta name="description">`, `<meta name="viewport">`, `<link rel="canonical">`
- AND `<title>` and `description` SHALL be localized per current locale

#### Scenario: Unique titles per page

- GIVEN the home page, menu page, and FAQ page
- THEN their `<title>` values SHALL all be different
- AND each SHALL accurately describe the page content

## Constraints

- FAQPage JSON-LD MUST validate against Google Rich Results Test
- FAQ page meta tags MUST follow same patterns as existing pages
- Sitemap MUST be updated to include FAQ URLs

## Acceptance Criteria

- [ ] FAQ page has unique localized `<title>` and `<meta description>`
- [ ] FAQ page OG tags present and valid
- [ ] FAQPage JSON-LD passes Google Rich Results Test
- [ ] Hreflang tags on FAQ page are bidirectional
- [ ] Sitemap includes `/faq` with hreflang annotations
