# Proposal: Backoffice UI Modernization

## Intent

The admin backoffice has a modern BEM-based CSS (552 lines in `src/frontend/css/pages/admin.css`) that is **never served** — the legacy 285-line version in `public/css/pages/admin.css` is what browsers actually load. Import/Export API endpoints exist with full i18n keys but **zero UI**. Admin JS is duplicated inline across every template. The result: an admin experience that feels broken, incomplete, and inconsistent with the design system. This change fixes the CSS pipeline, builds the missing pages, and upgrades interactions — all vanilla, no dependencies.

## Scope

### In Scope
- Sync modern CSS to served path (fix dual-CSS problem)
- Extract shared JS module (`admin.js`) replacing inline duplication
- Build Import/Export UI page (API already exists)
- Custom modal component replacing native `confirm()`
- Toast notifications for success/error feedback
- Loading states during all AJAX operations
- Image preview for product `image_url` field
- Client-side validation styling with inline errors
- All admin pages: login, dashboard, products, categories, import/export

### Out of Scope
- Dashboard charts or complex analytics
- Authentication system changes
- Backend API refactoring or new endpoints
- Database schema changes
- Frontend/public-facing site changes

## Capabilities

### New Capabilities
- `admin-import-export`: UI page for CSV file upload, import results display, and CSV download trigger
- `admin-ui-components`: Shared JS module with modal, toast, loading state, and form validation primitives

### Modified Capabilities
- `admin-panel`: CSS sync fix, shared JS adoption, modal/toast integration replacing native confirm/alert, loading states on CRUD, image preview on product forms

## Approach

Three phases, all vanilla PHP/CSS/JS:

1. **Foundation**: Copy modern CSS to `public/`, extract `admin.js` shared module, build Import/Export page template + routing
2. **Interaction upgrades**: CSS-first modal, toast container, loading spinner, image preview, validation styling
3. **Architecture uplift**: In-place DOM updates after CRUD (reduce full reloads), pagination for product list, slide-out drawer for forms

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `public/css/pages/admin.css` | Modified | Replace legacy with modern BEM CSS |
| `src/frontend/templates/pages/admin/` | Modified | All admin templates adopt shared JS, new import/export template |
| `src/frontend/templates/partials/admin/` | Modified | Shared admin layout gets new JS/CSS references |
| `public/js/admin.js` | New | Extracted shared module (modal, toast, api, loading) |
| `src/backend/pages/admin/` | Modified | Import/export route registration |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| CSS sync breaks existing admin styling | Medium | Visual QA each page after sync; keep legacy CSS in git history |
| Shared JS extraction breaks existing CRUD | Medium | Test each CRUD flow manually after extraction |
| Import/Export page exposes API edge cases | Low | API already exists and is tested; UI just wraps it |

## Rollback Plan

Revert the CSS file to legacy version, remove `admin.js` script tag from admin layout, and remove import/export route. Each phase is independently revertable since Phase 1 (CSS fix) and Phase 2 (components) are additive.

## Dependencies

- None (all vanilla, no new dependencies)

## Success Criteria

- [ ] Modern BEM CSS served correctly on all admin pages
- [ ] Import/Export page functional with file upload + CSV download
- [ ] Shared `admin.js` module used by products, categories, and import/export
- [ ] Custom modal replaces native `confirm()` for delete operations
- [ ] Toast notifications shown for CRUD success/error
- [ ] Loading spinner visible during AJAX operations
- [ ] Image preview renders for product `image_url`
- [ ] All pages still work without JavaScript (progressive enhancement)
- [ ] All existing CRUD functionality preserved end-to-end
