# Design: Backoffice UI Modernization

## Technical Approach

Two parallel source trees (`src/frontend/` = modern source, `public/` = served copy) are maintained manually with **no build step** (verified: no `package.json`). Phase 1 therefore is a **manual copy + reconcile**, not a pipeline fix. The modern `src/frontend/css/pages/admin.css` (552 lines, BEM, sidebar layout) becomes the single served CSS; some legacy classes used live by templates (`admin-container`, `admin-card`, `admin-stat`, login states) are ported into the modern file to avoid regressions. Admin JS moves from per-page inline `<script type="module">` to one shared ES module (`public/js/admin.js`) exporting an `AdminApi`, `Toast`, `Modal`, `Loading` primitives.

## Architecture Decisions

| Decision | Option | Tradeoff → Chosen Rationale |
|---|---|---|
| CSS source of truth | (a) Replace `public/` with `src/frontend/` copy (b) Build pipeline (c) Symlink | (b)/(c) rejected: no tooling precedent, vanilla stack. (a) chosen but **reconciled** — port legacy-only classes (`admin-container`, `admin-card__title`, `admin-stat__label`, `--secondary` btn) into modern file before overwrite. |
| Admin JS module | (a) Extend `api-client.js` (b) New `admin.js` ES module | (a) rejected: public client has no CSRF/credentials, different headers, ES `export` already module-type. New `public/js/admin.js` exports `AdminApi`, `Toast`, `Modal`, `Loading`; loaded once per admin page. |
| Import/Export wiring | (a) New page+controller (b) Modal-only | (a): matches existing pattern (`src/backend/pages/admin/{Name}.php` → `\renderPage('admin/{name}')`, route in `public/index.php`) and gives CSV download a real URL + nav entry. |
| Modal vs native `confirm()` | Custom modal with focus-trap | Replaces `confirm()` in delete handlers; ARIA dialog, Escape/overlay close, restore focus, `prefers-reduced-motion` transitions. |
| Post-CRUD refresh | (a) In-place DOM patch (b) Keep reload | (b) Phase 1 keeps `window.location.reload()`; (a) is Phase 3 via `AdminApi` returning row HTML / client render. Keeps phases independently shippable. |

## Data Flow

```
Browser ──GET /admin/products──▶ index.php ──▶ AdminProductsPage::render()
                                                │
                  default.php ──▶ /css/pages/admin.css  (modern, reconciled)
                                  /js/admin.js          (shared module)
                                                ▼
products.php (templates) ──import──▶ admin.js ──▶ AdminApi.{create,update,delete}
                                                       │ fetch + X-CSRF-Token
                  ◀── Toast / Modal / Loading ◀── /api/admin/products/{id}
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `public/css/pages/admin.css` | Modify | Overwrite legacy 285-line with reconciled modern BEM CSS (porting legacy-only classes first). |
| `src/frontend/css/pages/admin.css` | Modify | (optional) add the ported legacy classes so source/served parity holds. |
| `public/js/admin.js` | Create | Shared ES module: `AdminApi`, `Toast`, `Modal`, `Loading`, `ImagePreview`, `validateForm`. |
| `src/frontend/templates/partials/admin-nav.php` | Modify | Add Import/Export nav link (active match on `/admin/import-export`). |
| `src/frontend/templates/pages/admin/products.php` | Modify | Remove inline `api()`/`showAlert()`, import `admin.js`, wire `Modal` for delete, `Toast` for feedback, `Loading` on submit, `ImagePreview` on `image_url`, remove `data-alert-*` divs. |
| `src/frontend/templates/pages/admin/categories.php` | Modify | Same de-dup + module adoption as products; no image preview. |
| `src/frontend/templates/pages/admin/import-export.php` | Create | Upload form (`enctype="multipart/form-data"`, hidden csrf), export button, results region. |
| `src/backend/pages/admin/ImportExport.php` | Create | Controller `ImportExport::render()` mirroring `Products::render()`: `Auth::requireSession()`, builds `$meta` + `$data` (csrf_token, last import summary if stored). |
| `public/index.php` | Modify | Register `GET /admin/import-export` route → `ImportExport::render()`. API routes already exist (lines 122-123). Keep `use AdminIO` (line 24). |
| `public/index.php` use block | Modify | Add `use Pit\Cuixa\Backend\Pages\Admin\ImportExport as AdminImportExportPage;` alias. |

## Component Specs

**Module API** (`public/js/admin.js`, `export`-based):
- `AdminApi.request(method, url, { json, form }) → Promise<{ok, status, body}>` — reads `meta[name=csrf-token"]`, sends `X-CSRF-Token` + `credentials:'same-origin'`; uses `FormData` for import (multipart).
- `Toast.show(msg, variant)` — variants `success|error|info`; auto-dismiss 4s; stack container `[data-toast-region]`.
- `Modal.confirm({ title, message, confirmText }) → Promise<boolean>` — ARIA `role=dialog` `aria-modal`, focus-trap, Escape closes (cancel), overlay click cancel.
- `Loading.withButton(btn, promise)` — sets `btn.busy`/`aria-busy`, swaps label, disables.
- `ImagePreview.bind(inputEl, previewEl)` — `input` URL → `<img>` thumbnail; hide on empty.
- `validateForm(form)` — toggles `[aria-invalid]` + `.admin-field--error` on `:invalid`.

**Element catalogs (BEM)** added to admin.css: `.admin-modal`, `.admin-modal__overlay`, `.admin-modal__dialog`, `__title`, `__body`, `__actions`, `__close`; `.admin-toast`, `--success/--error/--info`, `.admin-toast-region`; `.admin-btn[aria-busy]` + `.admin-spinner`; `.admin-image-preview`, `.admin-image-preview__img`; `.admin-field--error`, `.admin-field__error`.

**Accessibility** (each component): modal `role=dialog aria-modal aria-labelledby`, first focusable autofocus, restore previous `document.activeElement`, `prefers-reduced-motion` skips transitions; toast `role=status` (success) / `role=alert` (error), `aria-live`; loading `aria-busy="true"` + visible spinner, button text preserved for SR; image preview hidden by default, decorative alt.
**Progressive enhancement**: import/export form submits natively to `/api/admin/import` if JS absent (server handles JSON+multipart); CRUD forms keep current JS-required contract — see Open Questions.

## Testing Strategy

| Layer | What | Approach |
|-------|------|----------|
| Manual unit | AdminApi, Toast, Modal isolated | Console smoke in browser (no test infra exists) |
| Integration | Each CRUD flow (products/cats create/edit/delete), import upload, export download | Manual click-through per page; verify CSRF + reload behaviour |
| Visual QA | All admin pages render with reconciled CSS | Side-by-side screenshots legacy→modern pre/post |
| A11y | Modal focus trap, toast aria-live, keyboard nav | Manual keyboard + axe-core (added as optional dev bookmarklet) |

No automated suites exist (verified). Phase 1 deliverable can add a minimal Playwright config — out of current scope unless requested as a strategy addendum.

## Migration / Rollout

Single deploy. Phase order is itself the rollout safety: (1) CSS solidifies visuals, (2) JS module is additive (falls back to inline until removed), (3) DOM updates remove reloads. Rollback = revert `admin.css` overwrite + remove `admin.js` script tags + remove `/admin/import-export` route. Git history retains legacy CSS for diff/restore.

## Open Questions

- [ ] **No-JS fallback for CRUD**: success criteria says "pages work without JS" but current forms use `e.preventDefault()` + fetch with no `action` attribute. Do we (a) accept JS-required for admin CRUD, or (b) add `action` + token-based no-JS submit? Recommend (a) given internal-only surface.
- [ ] CSS parity: do we keep `src/frontend/css/pages/admin.css` as canonical source going forward, or delete it post-copy as dead code? Recommend keeping + reconciling.
- [ ] Should legacy-only `admin-container`/`admin-card` classes used by dashboard/login be kept or refactored to `admin-form-panel`/`admin-section`? Recommend keep to limit blast radius in Phase 1.