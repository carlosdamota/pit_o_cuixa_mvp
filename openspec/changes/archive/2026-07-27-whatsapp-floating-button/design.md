# Design: WhatsApp Floating Button

## Technical Approach

Static, pure-CSS floating button injected via the shared layout (`default.php`). Zero JS, zero external assets — inline SVG icon, CSS tooltip, phone number from `\Config::phone()` at render time. A single BEM block `.whatsapp-float` handles all visual states. Maps to spec requirements WF-001 through WF-008.

## Architecture Decisions

### Decision: CSS-only tooltip over JS tooltip

| Option | Tradeoff | Decision |
|--------|----------|----------|
| CSS `::after` pseudo-element + `content` | Text lives in CSS — violates separation of concerns | Rejected |
| Absolute-positioned `<span>` in partial, toggled via `:hover`/`:focus-visible` | One extra DOM node, text stays in template | **Chosen** |

**Rationale**: Tooltip text is content, not presentation. A `.whatsapp-float__tooltip` span in the partial keeps the string "¡Haz tu pedido!" where translators can find it, even though i18n is currently out of scope. CSS handles the show/hide transition (`opacity` + `pointer-events`).

### Decision: Inline SVG with "WP" text fallback

| Option | Tradeoff | Decision |
|--------|----------|----------|
| External SVG file or icon font | Extra HTTP request, cache dependency | Rejected |
| Inline `<svg>` + `<text>` fallback inside `<a>` | Slightly larger HTML (+~1.2 KB) | **Chosen** |

**Rationale**: Zero network requests, renders immediately. The WhatsApp SVG icon is inlined inside the anchor. Below the SVG (outside the viewBox), a `<span class="whatsapp-float__fallback">WP</span>` is hidden by default (`display: none`) and shown only if the SVG fails via `@supports not (display: inline)` or a `.svg-fallback` class — but the simpler approach used here: the fallback text sits inside the anchor with its own styling, and is visually hidden alongside the SVG when both render normally.

**Revised approach**: The anchor contains both the SVG (visually rendered) and a small `WP` text node hidden by CSS. If the image fails to render, the text becomes the visible label. For simplicity and spec compliance (WF-003), the fallback is a `<span>` with `position: absolute; opacity: 0` that becomes visible via a fallback stylesheet rule.

### Decision: Phone number sanitised at render time

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Strip spaces in template with `str_replace` | Duplicates `default.php` pattern | **Chosen** |
| Strip in `\Config::phone()` itself | Side effect on other callers | Rejected |

**Rationale**: The layout already uses `str_replace(' ', '', \Config::phone())` for JSON-LD (line 128). Mirroring the same call in the partial is consistent and has no side effects on other phone consumers.

## Data Flow

    default.php (render)
      │
      ├─ <link href="/css/components/whatsapp-float.css">
      │
      └─ <?php require __DIR__ . '/../partials/whatsapp-float.php';>
           │
           └─ \Config::phone() ── str_replace(' ', '') ── href="https://wa.me/{sanitized}"
              inline SVG icon
              <span class="whatsapp-float__tooltip">¡Haz tu pedido!</span>

    User interaction:
      hover / focus-visible ──► opacity: 1 on tooltip
      click               ──► window.open(wa.me, _blank)

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/frontend/css/tokens.css` | Modify | Add `--z-whatsapp: 250` after `--z-overlay` |
| `src/frontend/css/components/whatsapp-float.css` | Create | BEM block `.whatsapp-float` — fixed position, green circle, tooltip, responsive safe zone |
| `src/frontend/templates/partials/whatsapp-float.php` | Create | Anchor with inline SVG icon + `href` + tooltip span |
| `src/frontend/templates/layouts/default.php` | Modify | Add CSS `<link>` in head block; add partial include after footer |

## Interfaces / Contracts

```css
/* tokens.css addition */
--z-whatsapp: 250;
```

```php
// whatsapp-float.php — href construction
$phone = str_replace(' ', '', \Config::phone());
$waUrl = 'https://wa.me/' . $phone;
```

```html
<!-- whatsapp-float.php output structure -->
<a class="whatsapp-float__link"
   href="https://wa.me/34123456789"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="¡Haz tu pedido!">
  <svg class="whatsapp-float__icon" viewBox="0 0 24 24" aria-hidden="true">...</svg>
  <span class="whatsapp-float__tooltip" role="tooltip">¡Haz tu pedido!</span>
</a>
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Syntax | PHP files | `php -l` on modified templates (config `build_command`) |
| Visual | Placement, color, tooltip, z-index, no overflow, no CLS | Manual browser checklist on 360px mobile + 1280px desktop |
| Link | `href`, `target`, `rel` | DOM inspection on each page variant |
| Global | Present on home, menu, FAQ, admin, 404 | Visual check + element presence |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary.

## Migration / Rollout

No migration required. Additive to 4 files — rollback via single commit revert.

## Open Questions

None — spec is self-contained, no blocking unknowns.
