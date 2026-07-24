# Delta for seo-geo

## MODIFIED Requirements

### SG-002: Open Graph Tags

Every page MUST include Open Graph meta tags for social media sharing.

#### Scenario: OG tags present

- GIVEN any public page
- WHEN the HTML `<head>` is rendered
- THEN it SHALL include: `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, `og:locale`
- AND `og:image` SHALL point to a valid image URL (minimum 1200x630px)

#### Scenario: og:locale 3-way mapping

- GIVEN the active locale is `ca`, `es`, or `en`
- WHEN `og:locale` is rendered
- THEN it SHALL output `ca_ES`, `es_ES`, or `en_US` respectively

(Previously: `og:locale` mapped only 2 locales — `es` → `es_ES`, `en` → `en_US`)

#### Scenario: Social share preview

- GIVEN the page URL is shared on a social platform
- WHEN the platform fetches OG metadata
- THEN the preview SHALL show the correct title, description, and image

### SG-004: XML Sitemap

The system MUST generate and serve a valid XML sitemap.

#### Scenario: Sitemap is accessible

- GIVEN the site is deployed
- WHEN a client requests `/sitemap.xml`
- THEN the response SHALL be valid XML with `Content-Type: application/xml`
- AND it SHALL list all public pages: `/`, `/es/`, `/en/`

#### Scenario: Sitemap includes hreflang annotations

- GIVEN the sitemap contains the home page URL
- THEN it SHALL include `<xhtml:link>` entries for `hreflang="ca"`, `hreflang="es"`, and `hreflang="en"`

(Previously: hreflang annotations included only `es` and `en`)

### SG-005: Hreflang Trilingual Links

Every public page MUST declare its alternate-language versions via hreflang for all 3 supported locales.

#### Scenario: Hreflang in HTML

- GIVEN any public page in any locale (`ca`, `es`, or `en`)
- WHEN the `<head>` is inspected
- THEN it SHALL include `<link rel="alternate" hreflang="ca" href="...">`, `hreflang="es"`, and `hreflang="en"`
- AND an `x-default` hreflang SHALL point to the Catalan (`ca`) version

(Previously: hreflang declared only `es` and `en`; `x-default` pointed to Spanish version)

#### Scenario: Hreflang bidirectional

- GIVEN any version of a page in any locale
- THEN its hreflang links SHALL point to the equivalent page in all other 2 locales
- AND all 3 locales SHALL reference each other (full trilingual mesh)
