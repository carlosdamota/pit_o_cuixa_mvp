# Mobile Navigation Specification

## Purpose

Redesigned mobile hamburger menu with home-page category links (Pollos, Combinados, Pica-pica) and integrated language selector. CSS-only changes scoped to `<640px` viewport. Existing `data-menu-toggle` JS unchanged.

## Requirements

### MN-001: Mobile Hamburger Content

The system MUST display home-page category links inside the mobile hamburger dropdown when viewport is below 640px. Links MUST point to the home page category sections (anchor links).

#### Scenario: Mobile menu shows category links

- GIVEN a viewport width below 640px
- WHEN the visitor opens the hamburger menu
- THEN the dropdown SHALL show links: Pollos, Combinados, Pica-pica
- AND each link SHALL navigate to the corresponding home page section

#### Scenario: Desktop menu unchanged

- GIVEN a viewport width of 640px or above
- WHEN the page renders
- THEN the hamburger menu SHALL NOT display the mobile-specific content
- AND the existing desktop navigation SHALL remain unchanged

### MN-002: Language Selector in Mobile Nav

The system MUST include an inline language selector within the mobile hamburger dropdown. The selector MUST allow switching between ca/es/en.

#### Scenario: Language selector in mobile menu

- GIVEN a viewport width below 640px and the hamburger menu is open
- WHEN the visitor views the dropdown
- THEN a language selector with ca/es/en options SHALL be visible
- AND selecting a language SHALL update the page locale

#### Scenario: Language selection persists

- GIVEN the visitor selects a language from the mobile nav
- WHEN they navigate to another page
- THEN the selected locale SHALL persist

### MN-003: CSS-Only Redesign

All mobile navigation changes MUST be CSS-only, scoped to `@media (max-width: 639px)`. No HTML structure changes to the toggle button. No JavaScript changes required.

#### Scenario: No JS dependency for mobile nav

- GIVEN the mobile nav CSS has been updated
- WHEN JavaScript is disabled
- THEN the hamburger toggle SHALL still function (existing `data-menu-toggle` behavior unchanged)
- AND the mobile menu content SHALL be styled correctly when opened

#### Scenario: Desktop layout unaffected

- GIVEN a viewport width of 640px or above
- WHEN the page renders
- THEN no mobile nav styles SHALL apply
- AND the desktop layout SHALL be identical to before this change

## Constraints

- All changes scoped to `<640px` media query
- No changes to `data-menu-toggle` JS behavior
- No changes to desktop navigation layout
- Language selector MUST integrate with existing locale switching mechanism

## Acceptance Criteria

- [ ] Mobile hamburger shows category links (Pollos, Combinados, Pica-pica)
- [ ] Language selector (ca/es/en) visible in mobile menu
- [ ] All changes scoped to `<640px` — desktop unaffected
- [ ] No JavaScript changes required
- [ ] Language selection persists across navigation
