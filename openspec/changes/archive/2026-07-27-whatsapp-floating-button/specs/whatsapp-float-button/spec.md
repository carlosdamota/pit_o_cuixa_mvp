# WhatsApp Floating Button Specification

## Purpose

Persistent WhatsApp contact button on all pages via shared layout. One-click access to `wa.me/{phone}` using `\Config::phone()`. BEM block `.whatsapp-float`, mobile-first fixed positioning, project design tokens.

## Requirements

### WF-001: Z-index Token

`--z-whatsapp: 250` MUST be added to `tokens.css` between overlay (200) and modal (300).

#### Scenario: Defined in correct position

- GIVEN `tokens.css` is loaded
- WHEN inspecting z-index properties
- THEN `--z-whatsapp` SHALL be `250`
- AND it SHALL appear after `--z-overlay` and before `--z-modal`

### WF-002: Fixed Positioning

The component MUST use `position: fixed` with `bottom: 20px` and `right: 20px`.

#### Scenario: Bottom-right placement

- GIVEN the button renders
- THEN computed `position` SHALL be `fixed`
- AND `bottom` SHALL be `20px`, `right` SHALL be `20px`

#### Scenario: No overflow on mobile

- GIVEN any viewport width (including 360px)
- WHEN the button renders
- THEN no horizontal scrollbar SHALL be introduced

### WF-003: Visual Identity

The button MUST be circular with WhatsApp green (`#25D366`) background and contain an inline SVG icon.

#### Scenario: Round shape and brand color

- GIVEN the button renders
- THEN `border-radius` SHALL be `var(--radius-full)`
- AND `background-color` SHALL be `#25D366`

#### Scenario: SVG fallback text

- GIVEN the SVG fails to render
- THEN visible text "WP" SHALL display inside the circle

### WF-004: Link Target and Safety

The anchor SHALL point to `https://wa.me/` + `\Config::phone()`, open in a new tab, and include `rel="noopener noreferrer"`.

#### Scenario: Correct href and target

- GIVEN the page renders
- WHEN inspecting the anchor
- THEN `href` SHALL match `https://wa.me/{phone}`
- AND `target` SHALL be `_blank`

#### Scenario: Security attributes

- GIVEN `target="_blank"`
- THEN `rel` SHALL include `noopener` and `noreferrer`

### WF-005: Tooltip

The tooltip "¡Haz tu pedido!" MUST appear on hover and keyboard focus.

#### Scenario: Tooltip on hover

- GIVEN the user hovers over the button
- THEN the tooltip text "¡Haz tu pedido!" SHALL be visible

#### Scenario: Tooltip on focus

- GIVEN the button receives keyboard focus
- THEN the tooltip "¡Haz tu pedido!" SHALL be visible

### WF-006: Global Rendering

The component MUST be included in `default.php` after the footer, before `</body>`, making it present on every page.

#### Scenario: Present on all pages

- GIVEN any page (home, menu, FAQ, admin, 404)
- WHEN the DOM finishes loading
- THEN an element with class `whatsapp-float` SHALL exist

### WF-007: Z-index Layering

The button SHALL stack above overlay content but below modals.

#### Scenario: Stacking order preserved

- GIVEN overlay content (`--z-overlay: 200`)
- AND a visible modal (`--z-modal: 300`)
- WHEN comparing z-index values
- THEN `--z-whatsapp: 250` SHALL be greater than `200` and less than `300`

### WF-008: No Layout Shift

The button MUST NOT affect page layout beyond its fixed position.

#### Scenario: Zero CLS contribution

- GIVEN the button is present on a page
- WHEN measuring Cumulative Layout Shift
- THEN the button SHALL contribute zero CLS

## Contracts

### CSS File

`src/frontend/css/components/whatsapp-float.css` — BEM block `.whatsapp-float`, elements: `__link`, `__icon`, `__tooltip`.

### Partial File

`src/frontend/templates/partials/whatsapp-float.php` — Inline SVG, no external asset requests, phone from `\Config::phone()`.

## Constraints

- Vanilla CSS only (no preprocessor, no framework)
- No external icon libraries or HTTP requests
- Tooltip text static Spanish, no i18n
- Single phone number, no pre-filled message template

## Acceptance Criteria

- [ ] `--z-whatsapp: 250` in `tokens.css` between overlay and modal
- [ ] Button at bottom-right on every page
- [ ] Click opens `https://wa.me/{phone}` in new tab
- [ ] Tooltip "¡Haz tu pedido!" on hover and focus
- [ ] Stacks above overlays, below modals
- [ ] No horizontal overflow or layout shift
- [ ] SVG icon renders; "WP" fallback if SVG fails
