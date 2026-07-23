# Design System Specification

## Purpose

Visual design language for Pit o Cuixa: CSS custom properties (design tokens), BEM naming methodology, mobile-first responsive strategy, and Quicksand typography. Ensures visual consistency across all pages and enables parallel frontend development by 8 team members.

## Requirements

### DS-001: Design Tokens

The system MUST define all visual constants as CSS custom properties in `tokens.css`. All components SHALL reference tokens instead of hardcoded values.

#### Scenario: Token definitions exist

- GIVEN the `tokens.css` file is loaded
- WHEN any component references `var(--color-primary)`
- THEN the resolved value SHALL be `#f7e721`

#### Scenario: Complete token coverage

- GIVEN the design system
- THEN tokens SHALL exist for: colors (primary, secondary, surface, on-surface), typography (font-family, sizes), spacing (xs–2xl), border-radius, shadows (sm, md, lg), and transitions (fast, normal)

### DS-002: BEM Naming Convention

All CSS class names MUST follow Block__Element--Modifier (BEM) methodology.

#### Scenario: Product card BEM structure

- GIVEN a product card component
- THEN its classes SHALL follow: `.card` (block), `.card__image`, `.card__title`, `.card__price` (elements), `.card--featured` (modifier)

#### Scenario: No non-BEM classes in components

- GIVEN any component CSS file
- WHEN inspected
- THEN all class names SHALL follow BEM pattern (except base/layout utilities)

### DS-003: Mobile-First Responsive Strategy

All component styles MUST be written mobile-first, using `min-width` media queries for progressive enhancement.

#### Scenario: Base styles target mobile

- GIVEN a component's CSS
- WHEN no media query is active (viewport < 640px)
- THEN the component SHALL render correctly at 360px width

#### Scenario: Tablet enhancement

- GIVEN a viewport width of 640px or wider
- WHEN media queries activate
- THEN the layout SHALL progressively enhance (e.g., grid columns increase)

### DS-004: Typography — Quicksand Font

The system MUST load and use the Quicksand font family for all text.

#### Scenario: Quicksand loads

- GIVEN any page loads
- WHEN the font is applied
- THEN all text SHALL render using `'Quicksand', sans-serif`

#### Scenario: Font loading strategy

- GIVEN the font is hosted externally (Google Fonts) or locally
- WHEN the page renders
- THEN a fallback `sans-serif` SHALL display while Quicksand loads (no FOIT)

### DS-005: Color Application Rules

The system MUST apply brand colors according to defined roles.

#### Scenario: Primary color usage

- GIVEN hero sections or CTA buttons
- THEN they SHALL use `--color-primary` (#f7e721)

#### Scenario: Secondary color usage

- GIVEN price tags or warning badges
- THEN they SHALL use `--color-secondary` (#d32f2f)

#### Scenario: Surface colors

- GIVEN card backgrounds
- THEN they SHALL use `--color-surface-container-lowest` (#ffffff)
- AND section backgrounds SHALL use `--color-surface-container-low` (#edf4ff)

### DS-006: Border Radius Consistency

All rounded corners MUST use the `--radius` token (8px).

#### Scenario: Uniform border radius

- GIVEN any component with rounded corners (cards, buttons, inputs)
- WHEN rendered
- THEN `border-radius` SHALL be `var(--radius)` (8px)

## Contracts

### Token Reference

```css
/* Colors */
--color-primary: #f7e721;
--color-secondary: #d32f2f;
--color-surface: #f7f9ff;
--color-surface-container-low: #edf4ff;
--color-surface-container-lowest: #ffffff;
--color-on-surface: #1a1c1e;

/* Typography */
--font-family: 'Quicksand', sans-serif;
--font-size-base: 16px;

/* Spacing */
--space-xs: 4px;  --space-sm: 8px;  --space-md: 16px;
--space-lg: 24px; --space-xl: 32px; --space-2xl: 48px;

/* Border */
--radius: 8px;

/* Shadows */
--shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
--shadow-md: 0 4px 6px rgba(0,0,0,0.07);
--shadow-lg: 0 10px 15px rgba(0,0,0,0.1);

/* Transitions */
--transition-fast: 150ms ease;
--transition-normal: 250ms ease;
```

### Breakpoints (min-width, mobile-first)

| Token | Value | Target |
|-------|-------|--------|
| — | 0–639px | Mobile (base styles) |
| `--bp-mobile` | 640px | Large mobile / small tablet |
| `--bp-tablet` | 1024px | Tablet |
| `--bp-desktop` | 1280px | Desktop |

### CSS File Structure

```
css/
├── tokens.css          # Design tokens (:root variables)
├── base.css            # Reset, body, typography
├── components/         # BEM component styles
├── layouts/            # Grid, header, section layouts
└── pages/              # Page-specific overrides
```

## Constraints

- No CSS preprocessor (no Sass, Less) — vanilla CSS only
- No build step — files served directly or concatenated manually
- No CSS framework (no Bootstrap, Tailwind)
- 8 team members editing CSS — BEM discipline prevents conflicts
- All colors must pass WCAG AA contrast ratio against their background

## Acceptance Criteria

- [ ] `tokens.css` defines all color, typography, spacing, shadow, radius, and transition tokens
- [ ] All component CSS uses BEM class names
- [ ] Base styles render correctly at 360px viewport
- [ ] Media queries use `min-width` (mobile-first)
- [ ] Quicksand font loads with `sans-serif` fallback
- [ ] Primary/secondary/surface colors applied per role rules
- [ ] All border-radius values use `var(--radius)` (8px)
- [ ] No hardcoded color/spacing values in component files
