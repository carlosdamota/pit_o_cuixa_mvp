# Proposal: UI Home Improvements

## Intent

Improve the mobile-first PWA experience across three areas: (1) add a horizontal category slider to the menu page for faster browsing, (2) create a dedicated `/faq` page with accordion Q&A and SEO schema markup, and (3) redesign the mobile hamburger menu to surface home-like navigation with integrated language switching. The home page landing remains unchanged — fast, intuitive, logo + 3 category buttons.

## Scope

### In Scope
- Category slider on `/menu` page (horizontal scrollable pills enhancing current filter bar)
- New `/faq` route with accordion Q&A page using native `<details>/<summary>`
- FAQPage JSON-LD schema markup for SEO
- Mobile hamburger menu redesign (home content links + language selector, Picapica-inspired)
- FAQ links from footer and mobile nav
- i18n strings for FAQ content (ca/es/en)

### Out of Scope
- Home page hero changes (keeping current landing as-is)
- FAQ on home page or menu page
- Slider on home page
- Desktop navigation changes (mobile-only hamburger redesign)
- Backend FAQ management UI (hardcoded in i18n for now)

## Capabilities

> Contract between proposal and specs phases.

### New Capabilities
- `faq-page`: Dedicated FAQ page with accordion UI, FAQPage JSON-LD schema, i18n content, and navigation entry points (footer + mobile nav)
- `mobile-navigation`: Redesigned mobile hamburger menu with home-page category links and integrated language selector

### Modified Capabilities
- `product-catalog`: Menu page filter bar enhanced with horizontal category slider on mobile
- `seo-geo`: FAQ page requires meta tags, canonical URL, hreflang, and FAQPage JSON-LD schema

## Approach

- **FAQ page**: New route `/faq` in `public/index.php`, backend controller `src/backend/pages/faq.php`, template `src/frontend/templates/pages/faq.php` with native `<details>/<summary>` accordions. FAQ content in i18n files. FAQPage JSON-LD in controller.
- **Menu slider**: Enhance existing filter bar CSS for horizontal scrollable category pills on mobile. No JS changes — existing data-attribute filter logic is reusable.
- **Mobile nav**: Restyle `header__menu` dropdown to show category links (Pollos, Combinados, Pica-pica) + language selector inline. CSS-only, scoped to `<640px`. Existing `data-menu-toggle` JS unchanged.
- **Footer**: Add FAQ link to footer partial.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `public/index.php` | Modified | Add `/faq` route registration |
| `src/backend/pages/faq.php` | New | FAQ controller with FAQPage JSON-LD |
| `src/frontend/templates/pages/faq.php` | New | FAQ template with `<details>` accordions |
| `src/frontend/templates/partials/nav.php` | Modified | Mobile menu content + lang selector |
| `src/frontend/templates/partials/footer.php` | Modified | Add FAQ link |
| `public/css/pages/menu.css` | Modified | Category slider scroll styles |
| `public/css/layouts/header.css` | Modified | Mobile hamburger redesign |
| `public/css/pages/faq.css` | New | FAQ accordion styles |
| `src/shared/i18n/{ca,es,en}.php` | Modified | FAQ strings + nav link strings |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| `<details>` marker styling inconsistency | Low | CSS reset for `::-webkit-details-marker`; test Safari/Chrome/Firefox |
| FAQ content needs frequent updates | Low | i18n files are editable; DB-backed FAQ can follow later |
| Mobile nav redesign breaks desktop layout | Low | All changes scoped to `<640px` media query |
| FAQ schema validation fails | Low | Validate with Google Rich Results Test before merge |

## Rollback Plan

Each feature is independently revertable:
1. **FAQ page**: Remove `/faq` route from `index.php`, delete `faq.php` controller + template + CSS — zero impact on existing pages
2. **Menu slider**: Revert CSS changes in `menu.css` — filter bar returns to previous layout
3. **Mobile nav**: Revert `nav.php` and `header.css` — previous hamburger behavior restored

Git revert per feature if chained PRs are used.

## Dependencies

- None external — all vanilla PHP/CSS/JS within existing stack

## Success Criteria

- [ ] `/faq` renders with accordions and passes Google Rich Results Test for FAQ schema
- [ ] Menu page category slider scrolls horizontally on mobile (<640px)
- [ ] Mobile hamburger shows home content links + language selector
- [ ] FAQ link present in footer and mobile nav
- [ ] All FAQ text localized in ca/es/en
- [ ] Home page unchanged — same layout and load time
- [ ] Lighthouse mobile score >= 90 after changes
