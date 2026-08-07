# Language Switcher Specification

## Purpose

Shared custom dropdown for language selection across header and footer instances. Provides a compact, accessible disclosure toggle (flag + chevron) that opens a menu of language links, replacing the inline 4-flag row on the home footer and matching the existing header dropdown pattern.

## Requirements

### LS-001: Disclosure Toggle

The system MUST render a `<button>` toggle displaying the current language flag and a chevron. The toggle MUST set `aria-expanded` (`"false"` closed, `"true"` open), `aria-haspopup="listbox"`, and `aria-controls` referencing the menu `id`.

#### Scenario: Toggle opens menu

- GIVEN a `[data-lang-dropdown]` container with a closed menu
- WHEN the user clicks `[data-lang-toggle]`
- THEN `aria-expanded` SHALL become `"true"`
- AND `[data-lang-menu]` SHALL have `hidden` removed

#### Scenario: Toggle closes menu

- GIVEN an open dropdown (`aria-expanded="true"`)
- WHEN the user clicks the toggle again
- THEN `aria-expanded` SHALL become `"false"`
- AND the menu SHALL have `hidden` set

### LS-002: Menu Options

The system MUST render a `<ul>` with one `<a>` per supported locale (ca, es, en, uk). Each link MUST display the locale flag and label. The active locale link MUST carry `aria-current="true"`.

#### Scenario: Active locale indicated

- GIVEN current locale is `es`
- WHEN the menu renders
- THEN the `es` link SHALL have `aria-current="true"`
- AND other links SHALL NOT have `aria-current`

#### Scenario: Option navigates

- GIVEN the dropdown on page `/`
- WHEN the user clicks the `ca` option
- THEN navigation SHALL go to `/?lang=ca`

### LS-003: Close on Outside Click

The system MUST close any open dropdown when a click occurs outside its `[data-lang-dropdown]` container.

#### Scenario: Outside click closes

- GIVEN an open dropdown
- WHEN the user clicks outside the container
- THEN `aria-expanded` SHALL be `"false"` and menu SHALL be hidden

### LS-004: Close on Escape

The system MUST close any open dropdown on `Escape` and return focus to the toggle.

#### Scenario: Escape closes and focuses

- GIVEN an open dropdown
- WHEN the user presses `Escape`
- THEN the menu SHALL close AND focus SHALL return to `[data-lang-toggle]`

### LS-005: Multiple Instances

The system MUST support multiple `[data-lang-dropdown]` elements on the same page. Each instance MUST operate independently.

#### Scenario: Header and footer coexist

- GIVEN a page with header and footer dropdowns
- WHEN the user opens the footer dropdown
- THEN only the footer menu SHALL open

#### Scenario: Outside click targets one

- GIVEN two dropdowns, one open
- WHEN the user clicks outside the open dropdown
- THEN only the open dropdown SHALL close

### LS-006: Footer Dropdown Positioning

The footer dropdown menu MUST open upward and right-aligned to avoid clipping within the fixed `overflow: hidden` landing.

#### Scenario: Upward opening

- GIVEN the footer dropdown on the onboarding page
- WHEN opened
- THEN the menu SHALL appear above the toggle, not clipped by any ancestor

### LS-007: Footer Grid Layout

`.onboarding__footer` MUST use CSS grid `1fr auto 1fr`. Column 1: FAQ link. Column 2: PWA Install CTA (in-flow, centered). Column 3: language dropdown.

#### Scenario: CTA centered

- GIVEN the three-column footer
- WHEN rendered
- THEN the CTA SHALL be horizontally centered regardless of side widths

#### Scenario: Hidden CTA preserves symmetry

- GIVEN the CTA has `hidden`
- WHEN rendered
- THEN left and right `1fr` columns SHALL remain symmetric

### LS-008: Touch Target

The toggle MUST have a minimum touch target of 44×44px.

#### Scenario: Minimum size met

- GIVEN any toggle
- WHEN measured
- THEN height and width SHALL each be ≥ 44px

### LS-009: Reduced Motion

When `prefers-reduced-motion: reduce` is active, open/close animation MUST be disabled.

#### Scenario: No animation

- GIVEN `prefers-reduced-motion: reduce`
- WHEN the dropdown opens
- THEN the menu SHALL appear instantly (no transition)

### LS-010: Narrow Viewport

At ≤ 360px, the footer grid MUST NOT overflow. Long labels MUST use `min-width: 0` with text ellipsis.

#### Scenario: 360px Ukrainian locale

- GIVEN 360px viewport and `uk` locale
- WHEN the footer renders
- THEN no horizontal overflow SHALL occur
- AND truncated text SHALL use ellipsis

### LS-011: No-JS Fallback

Without JavaScript the menu remains `hidden`. Home language switching is unavailable without JS (accepted).

#### Scenario: JS disabled

- GIVEN JavaScript is disabled
- WHEN the home page loads
- THEN the footer toggle SHALL be non-functional
