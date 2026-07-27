# FAQ Page Specification

## Purpose

Dedicated FAQ page with accordion Q&A, FAQPage JSON-LD schema, i18n content (ca/es/en), and navigation entry points from footer and mobile hamburger menu.

## Requirements

### FP-001: FAQ Route and Controller

The system MUST serve a FAQ page at `/faq` (and locale-prefixed `/es/faq`, `/en/faq`). The controller MUST load FAQ entries from i18n files and inject FAQPage JSON-LD into the page head.

#### Scenario: FAQ page renders

- GIVEN a visitor navigates to `/faq`
- WHEN the page renders
- THEN the FAQ title, accordion list, and navigation chrome SHALL be visible
- AND the page SHALL display in the visitor's current locale

#### Scenario: Locale-prefixed FAQ URL

- GIVEN a visitor navigates to `/en/faq`
- WHEN the page renders
- THEN all FAQ content SHALL display in English

### FP-002: Accordion UI

The system MUST render FAQ entries as collapsible accordions using native `<details>/<summary>` elements. Only one accordion item MAY be open at a time (progressive enhancement via JS optional).

#### Scenario: Accordion expand/collapse

- GIVEN the FAQ page is rendered with N questions
- WHEN the visitor taps a `<summary>` element
- THEN the corresponding `<details>` SHALL expand to reveal the answer
- AND tapping again SHALL collapse it

#### Scenario: Accordion keyboard accessible

- GIVEN the visitor is using keyboard navigation
- WHEN they Tab to a `<summary>` element and press Enter
- THEN the accordion SHALL toggle open/closed

### FP-003: FAQPage JSON-LD Schema

The controller MUST embed a `<script type="application/ld+json">` block containing a valid `FAQPage` schema with all visible Q&A pairs.

#### Scenario: FAQ schema validates

- GIVEN the FAQ page is rendered
- WHEN the HTML is inspected
- THEN a JSON-LD block SHALL contain `@type: "FAQPage"` with `mainEntity` array
- AND each entry SHALL have `@type: "Question"`, `name`, and `acceptedAnswer.text`
- AND the schema SHALL pass Google Rich Results Test

### FP-004: i18n FAQ Content

FAQ questions and answers MUST be stored in `src/shared/i18n/{ca,es,en}.php` under a `faq` key. The system MUST render all entries in the current locale.

#### Scenario: FAQ content localized

- GIVEN the i18n files contain FAQ entries in ca, es, and en
- WHEN the visitor switches locale
- THEN all FAQ questions and answers SHALL update to the selected locale

### FP-005: Navigation Entry Points

The system MUST include a link to `/faq` in the footer partial and in the mobile hamburger menu.

#### Scenario: FAQ link in footer

- GIVEN any page with the footer partial
- WHEN the footer renders
- THEN a "FAQ" link pointing to `/faq` SHALL be visible

#### Scenario: FAQ link in mobile nav

- GIVEN a viewport width below 640px
- WHEN the hamburger menu is opened
- THEN a "FAQ" link SHALL be visible among the navigation items

## Constraints

- FAQ content is hardcoded in i18n files (no admin UI in this change)
- Native `<details>/<summary>` — no JS framework required for basic expand/collapse
- FAQPage JSON-LD MUST validate against Google's Rich Results Test
- All FAQ text MUST be localized in ca/es/en

## Acceptance Criteria

- [ ] `/faq` renders with accordion Q&A in all 3 locales
- [ ] FAQPage JSON-LD passes Google Rich Results Test
- [ ] FAQ link present in footer and mobile nav
- [ ] Accordions expand/collapse via click and keyboard
- [ ] All FAQ text localized in ca/es/en
