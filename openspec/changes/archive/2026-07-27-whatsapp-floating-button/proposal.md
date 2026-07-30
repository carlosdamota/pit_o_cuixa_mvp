# Proposal: WhatsApp Floating Button

## Intent

Restaurant customers visiting the site need a quick way to start a WhatsApp conversation for orders or inquiries — but currently must navigate away or manually copy the phone number. A persistent floating button reduces friction and increases conversion for direct contact.

## Scope

### In Scope
- Floating WhatsApp button (SVG icon, round, WhatsApp green) on ALL pages
- Tooltip "¡Haz tu pedido!" on hover/focus
- `position: fixed` at bottom-right, 20px offset
- Link opens `wa.me` with number from `\Config::phone()`
- New z-index token `--z-whatsapp: 250` (between overlay 200 and modal 300)

### Out of Scope
- Click tracking / analytics
- A/B testing or placement alternatives
- Multi-language or dynamic tooltip text
- Multiple phone numbers or pre-filled message templates
- Mobile app deep link detection / fallback

## Capabilities

### New Capabilities
- `whatsapp-float-button`: Persistent WhatsApp floating button rendered on all pages via the shared layout. Pulls target number from `\Config::phone()`.

### Modified Capabilities
None

## Approach

1. Add `--z-whatsapp: 250` to `tokens.css` (z-index section)
2. Create `src/frontend/css/components/whatsapp-float.css` — BEM block `.whatsapp-float`, `position: fixed`, bottom 20px right 20px, round button (`--radius-full`), WhatsApp green (`#25D366`), tooltip "¡Haz tu pedido!" on hover
3. Create `src/frontend/templates/partials/whatsapp-float.php` — `<a>` with inline SVG icon, `href="https://wa.me/{phone}"`, `target="_blank"`, tooltip span
4. Include partial in `default.php` after footer, before `</body>`
5. Add CSS `<link>` in the layout's CSS block (unconditional, all pages)

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `src/frontend/css/tokens.css` | Modified | Add `--z-whatsapp: 250` |
| `src/frontend/css/components/whatsapp-float.css` | New | Component styles |
| `src/frontend/templates/partials/whatsapp-float.php` | New | Button partial |
| `src/frontend/templates/layouts/default.php` | Modified | Include partial + CSS link |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| z-index overlap with overlays/modals | Low | `--z-whatsapp: 250` cleanly between overlay (200) and modal (300) |
| SVG renders incorrectly on old browsers | Low | Green circle + "WP" text fallback via CSS if SVG fails |

## Rollback Plan

Revert the two added lines in `default.php` (CSS `<link>` and partial include). Delete or keep the two new files — no data or config impact. Zero side effects.

## Dependencies

None — `\Config::phone()` already exists and is used elsewhere.

## Success Criteria

- [ ] WhatsApp button visible on every page (home, menu, FAQ, admin, 404)
- [ ] Button stays at bottom-right on all viewports, does not overlap footer
- [ ] Click opens `https://wa.me/{phone}` in a new tab
- [ ] Tooltip "¡Haz tu pedido!" appears on hover / keyboard focus
- [ ] Button is visually above overlay content but below modals
- [ ] No layout shifts or horizontal overflow caused by the button
