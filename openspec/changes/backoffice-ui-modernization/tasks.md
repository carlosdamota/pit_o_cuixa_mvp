# Tasks: Backoffice UI Modernization

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 1000–1200 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1: CSS + Shared JS → PR 2: Import/Export + Wire → PR 3: Components → PR 4: Architecture |
| Delivery strategy | ask-always (→ ask-on-risk) |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | CSS reconcile + `admin.js` module | PR 1 | ~600 lines (CSS declarations, low cognitive load); base: main |
| 2 | Import/Export page + JS wiring in templates | PR 2 | ~250 lines; base: main |
| 3 | Modal, Toast, Loading, Preview, Validation | PR 3 | ~200 lines; base: main |
| 4 | In-place DOM, pagination, drawer, shortcuts | PR 4 | ~300 lines; base: main |

## Phase 1: Foundation

- [x] 1.1 Port legacy classes (`admin-container`, `admin-card`, `admin-stat`, `-btn--secondary/--danger`, `:disabled`) into modern CSS, then overwrite `public/css/pages/admin.css`
- [x] 1.2 Create `public/js/admin.js` — ES module exporting `AdminApi.request()`, `Toast.show()`, `Modal.confirm()`, `Loading.withButton()`, `ImagePreview.bind()`, `validateForm()`
- [x] 1.3 Wire `admin.js` into `products.php`: remove inline `api()`/`showAlert()`, import module, wire delete confirm modal, toast feedback, loading on submit, image preview on `image_url`
- [x] 1.4 Wire `admin.js` into `categories.php`: same module adoption (no image preview)
- [x] 1.5 Create `ImportExport.php` controller (`Auth::requireSession()`, `$meta`/`$data` with `csrf_token`)
- [x] 1.6 Create `import-export.php` template (upload form `enctype=multipart`, export button, results region, loading/toast)
- [x] 1.7 Add Import/Export nav link to `admin-nav.php`, register route + `use` alias in `index.php`

## Phase 2: Interaction Components

- [x] 2.1 Add `.admin-modal` BEM (`__overlay`, `__dialog`, `__title`, `__body`, `__actions`, `__close`) to admin.css + focus-trap, Escape/overlay close, restore `activeElement`, `prefers-reduced-motion`
- [x] 2.2 Add `.admin-toast` + `.admin-toast-region` to admin.css + stacking, auto-dismiss (4s), manual close on error, `role=status`/`role=alert`
- [x] 2.3 Add `.admin-btn[aria-busy]` + `.admin-spinner` to admin.css + `Loading.withButton()` swaps label, disables, sets `aria-busy`
- [x] 2.4 Add `.admin-image-preview` BEM to admin.css + `ImagePreview.bind()` URL→thumbnail (80px, radius 4px) with error placeholder
- [x] 2.5 Add `.admin-field--error` + `.admin-field__error` + `:valid/:invalid` to admin.css + `validateForm()` toggles `aria-invalid` on blur and submit

## Phase 3: Architecture Uplift

- [ ] 3.1 Refactor CRUD products/categories: replace `window.location.reload()` with `AdminApi` → DOM row insert/update/remove, maintain sort/page state
- [ ] 3.2 Add pagination to products table (page nav prev/next, limit param, server-side SQL `LIMIT/OFFSET`, client-side row batch swap)
- [ ] 3.3 Build slide-out drawer form panel (`.admin-drawer` BEM + JS toggle + overlay + focus-trap)
- [ ] 3.4 Add keyboard shortcuts: `Ctrl+N` new product, `Ctrl+F` search focus, `Esc` close drawer/modal (global handler)

### Dependencies

- Phase 2 cannot start before Phase 1 (CSS component classes and admin.js module needed).
- Phase 3 cannot start before Phase 2 (Modal, Toast, Loading needed for UX continuity, but in-place DOM + pagination can be developed independently from drawer/shortcuts).
