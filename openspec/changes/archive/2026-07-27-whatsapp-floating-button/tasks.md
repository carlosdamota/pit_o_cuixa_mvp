# Tasks: WhatsApp Floating Button

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~85-115 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|----------------------|-----------------|-------------------|
| 1 | Full feature: token, CSS, partial, layout wiring | Single PR | `php -l src/frontend/templates/partials/whatsapp-float.php && php -l src/frontend/templates/layouts/default.php` | Browser visual check at 360px + 1280px | Single commit revert of 4 files |

## Phase 1: Foundation (Token)

- [x] 1.1 Add `--z-whatsapp: 250;` to `src/frontend/css/tokens.css` after `--z-overlay: 200;` (between overlay and modal)

## Phase 2: Core (CSS + Partial)

- [x] 2.1 Create `src/frontend/css/components/whatsapp-float.css` — BEM block `.whatsapp-float` with `__link`, `__icon`, `__tooltip`; fixed positioning bottom 20px right 20px; green circle (#25D366, radius-full); SVG size 24x24; tooltip spans opacity transition on hover/focus-visible; responsive safe zone using `max-width: 100vw` and `overflow: hidden`
- [x] 2.2 Create `src/frontend/templates/partials/whatsapp-float.php` — anchor with `href="https://wa.me/"` + `str_replace(' ', '', \Config::phone())`, `target="_blank"`, `rel="noopener noreferrer"`; inline SVG icon; `aria-label="¡Haz tu pedido!"`; tooltip span with `role="tooltip"`; "WP" fallback span hidden by default

## Phase 3: Integration (Layout Wiring)

- [x] 3.1 Add `<link rel="stylesheet" href="/css/components/whatsapp-float.css">` to the CSS block in `src/frontend/templates/layouts/default.php` (after existing component CSS, before page-specific conditionals)
- [x] 3.2 Add `<?php require __DIR__ . '/../partials/whatsapp-float.php'; ?>` to `src/frontend/templates/layouts/default.php` after the footer partial require, before the JS script tag

## Phase 4: Verification

- [x] 4.1 Run `php -l` on both PHP files to validate syntax
- [x] 4.2 Manual browser checklist: verify button renders on all page variants (home, menu, FAQ, admin, 404), at 360px and 1280px viewports — confirm no overflow, tooltip appears on hover/focus, link opens correct wa.me URL in new tab, z-index stacks above overlays and below modals
