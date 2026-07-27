## Exploration: UI Improvements for Home Page — Hero Slider, FAQ Accordion, and Navigation Redesign

### Current State
The current home page (`src/frontend/templates/pages/home.php`) is a fullscreen landing section with a yellow background (`--color-primary: #f7e721`), an SVG logo (96px-128px), and 3 large category buttons (Pollos a l'ast, Plats combinats, Pica-pica) that link to `/menu?cat=...`. It's minimal, functional, but static — no hero carousel, no featured products, no FAQ, no content beyond the three buttons.

The navigation (`src/frontend/templates/partials/nav.php`) uses a classic hamburger menu toggle on mobile (<640px) via CSS pseudo-elements (`header__menu-icon` ::before/::after), with the menu dropdown positioned absolutely below the sticky header. On desktop (≥640px), the menu shows as a horizontal row of links.

The design system tokens are clean (BEM, CSS custom properties, mobile-first), but the home page content density is extremely low — a single section with only a logo and three links.

### Affected Areas
- `src/frontend/templates/pages/home.php` — Complete hero section rewrite (slider), new FAQ accordion section
- `src/frontend/templates/partials/nav.php` — Navigation markup restructure for the new menu pattern
- `src/frontend/templates/partials/header.php` — May need updates if nav structure changes
- `src/frontend/templates/layouts/default.php` — CSS additions for new components
- `public/css/pages/home.css` — Replace current landing styles with hero slider styles
- `public/css/layouts/header.css` — Significant changes to mobile menu pattern
- `public/css/components/` — New component CSS for: hero-slider.css, faq-accordion.css
- `public/js/main.js` — New JS modules for slider, accordion, and navigation behavior
- `src/backend/pages/home.php` — May need to pass additional data (featured products, FAQ content)
- `src/shared/i18n/{ca,es,en}.php` — New translation strings for FAQ, slider copy

### Approaches

1. **Hero Slider: CSS-only fade carousel vs JS-driven slider**
   - *CSS-only*: Uses `@keyframes` and `animation-delay` to cycle through background images/overlay text. No JS dependency, lightweight. Limited interactivity (no user swipe, no pause).
   - *JS-driven*: Vanilla JS carousel with `setInterval`, swipe support, pause-on-hover, dot indicators. More interactive, accessible (aria-live region), but heavier.
   - **Effort**: Medium

2. **FAQ Accordion: `<details>/<summary>` native vs JS toggle**
   - *Native HTML*: Uses `<details>` / `<summary>` elements — zero JS, fully accessible, built-in open/close. Styling is limited across browsers but fully functional. Perfect for a content-driven restaurant FAQ.
   - *JS toggle*: Custom div-based accordion with JS click handlers, CSS transitions for smooth height animation. More control over animation but more code.
   - **Effort**: Low

3. **Navigation: Restyle hamburger vs visual category bar**
   - *Hamburger restyle*: Keep the hamburger concept but improve animation (morph to X), add overlay, smoother dropdown, better touch targets.
   - *Visual category bar*: Replace the hamburger with a persistent horizontal scroll of icon+label pills (like food category chips: 🐔 Pollos, 🥘 Combinados, 🍢 Pica-pica, 📋 Carta). Visible on both mobile and desktop, reducing taps.
   - **Effort**: Medium

### Recommendation
**Use a combined JS approach**: (1) JS-driven hero slider with 4-5 slides showing featured menu items with food imagery overlaid text, auto-advance + dot navigation. (2) Native `<details>/<summary>` accordion for FAQ — simpler, accessible, perfect for this content. (3) Replace the hamburger with a horizontal scrollable category pill bar (icon + label) inspired by food delivery apps and the Picapica reference — always visible, reduces cognitive load, shows the restaurant's categories immediately.

This leverages the existing CSS token system and BEM conventions while significantly improving home page content density and visual interest.

### Risks
- **Performance**: Adding a slider means loading images. All slides must use lazy-loading and properly sized images (WebP with fallback). The current data must support image URLs for featured products.
- **Accessibility**: Slider auto-rotation must respect `prefers-reduced-motion` and pause on hover/focus. `<details>` accordion must be tested with screen readers.
- **Mobile-first carousel touch**: Swipe gesture detection in vanilla JS is non-trivial. Consider touch events for mobile slider interaction.
- **Backend data**: The home controller currently passes only `locale`. A slider needs featured products data — a new DB query or API call will be required.
- **i18n expansion**: FAQ content needs to be added to all 3 language files.

### Ready for Proposal
Yes — the user has clearly stated what they want. The approaches are well-understood and map to existing patterns in the codebase (vanilla JS, modular ESM, BEM CSS, 3-locale i18n). The orchestrator should tell the user that exploration is complete and we're ready to draft a proposal with concrete implementation details, effort estimation per feature, and a rollback plan.
