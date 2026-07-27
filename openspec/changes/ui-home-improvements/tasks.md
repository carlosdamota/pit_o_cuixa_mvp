# Tasks: UI Home Improvements

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~380-420 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | auto-forecast |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Medium

## Phase 1: Foundation — i18n + Router

- [x] 1.1 Add FAQ i18n content (5-7 items) to `src/shared/i18n/ca.php`, `es.php`, `en.php`
- [x] 1.2 Register GET `/faq`, `/{lang}/faq` routes in `public/index.php`
- [x] 1.3 Load `faq.css` conditionally in `default.php` when `$pageName === 'faq'`

## Phase 2: FAQ Page (Core)

- [x] 2.1 Create `src/backend/pages/faq.php` — controller with `render()`, `$meta` array, FAQPage JSON-LD
- [x] 2.2 Create `src/frontend/templates/pages/faq.php` — title + `<details>/<summary>` accordion loop
- [x] 2.3 Create `public/css/pages/faq.css` — accordion styles, mobile-first, touch targets
- [ ] 2.4 Verify: navigate `/faq`, test accordions in 3 locales, inspect JSON-LD block

## Phase 3: Mobile Navigation

- [x] 3.1 Modify `nav.php` — add mobile nav items (Pollos, Combinados, Pica-pica), FAQ link, lang selector (ca/es/en)
- [x] 3.2 Modify `footer.php` — add FAQ link
- [x] 3.3 Modify `header.css` — mobile nav styles at `<640px`, hide `header__desktop-item`, 44px touch targets
- [ ] 3.4 Verify: mobile nav shows links, desktop unaffected, lang switcher persists

## Phase 4: Category Slider

- [x] 4.1 Modify `filter-bar.css` — scroll-snap, `scroll-snap-type: x mandatory` at `<640px`
- [ ] 4.2 Verify: swipe pills, search bar stationary, filter still works, desktop unchanged

## Phase 5: SEO/GEO

- [x] 5.1 Modify `sitemap.php` — add `/faq`, `/es/faq`, `/en/faq` with hreflang alternates
- [ ] 5.2 Verify: Google Rich Results Test, hreflang bidirectional, sitemap validates

## Phase 6: Final Verification

- [ ] 6.1 Manual testing on real mobile (iOS Safari, Android Chrome)
- [ ] 6.2 W3C HTML validation, axe-core accessibility scan
- [ ] 6.3 Lighthouse mobile score >= 90 after changes
