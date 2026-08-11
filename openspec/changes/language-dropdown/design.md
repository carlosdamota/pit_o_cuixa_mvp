# Design: Language Dropdown for Home Footer

## Technical Approach

Replace the home footer's inline 4-flag row with the same custom dropdown the header already uses (`nav.php` + `header.css` + `initLangDropdown()`), styled as a footer BEM variant that opens **upward** to dodge the fixed `100dvh` + `overflow: hidden` landing. Restructure `.onboarding__footer` from `flex` to grid `1fr auto 1fr` so the PWA Install CTA moves from `position: fixed` into in-flow centered middle child. Harden the JS to `querySelectorAll` so header + footer instances run independently from the same function. No PHP controller changes — the `$languages`/`$currentFlag` block already lives in `nav.php`; we duplicate the same 5-line array into `home.php` (mirroring how `home.php` already re-derives `$baseUri`/`$langSeparator` instead of sharing partials).

## Architecture Decisions

### Decision: Footer dropdown opens upward
**Choice**: `bottom: calc(100% + 8px); right: 0` on `.onboarding__lang-menu`.
**Alternatives**: `top`-anchored popup; `position: fixed` escape container.
**Rationale**: `.landing--onboarding` is `position: fixed; height: 100dvh; overflow: hidden` — a downward menu clips at the viewport bottom. Upward stays inside the footer's 100dvh box. `position: fixed` would lose right-alignment on resize.

### Decision: CSS grid `1fr auto 1fr` over flexbox
**Choice**: `.onboarding__footer { display: grid; grid-template-columns: 1fr auto 1fr; }` with FAQ `justify-self: start`, CTA centered, dropdown `justify-self: end`.
**Alternatives**: `flex` with `margin: auto` centering; absolute-centered CTA.
**Rationale**: The two `1fr` tracks stay equal width even when CTA is `[hidden]` (auto track collapses to 0), satisfying LS-007 symmetry. Flex centering shifts when the CTA disappears.

### Decision: Re-derive `$languages` in `home.php` instead of extracting a shared partial
**Choice**: Copy the 5-line `$languages` + `$currentFlag` block into `home.php` (it already copies `$baseUri` logic from `nav.php`).
**Alternatives**: Extract `lang-data.php` partial; move to a controller.
**Rationale**: Project convention — `home.php` is a standalone onboarding template that re-derives its own URI helpers rather than depending on partials. Introducing a shared partial is out of scope for this change and would touch `nav.php` loading order. Follow existing pattern.

### Decision: Footer uses footer-specific BEM classes, shared `data-lang-*` hooks
**Choice**: Classes `.onboarding__lang-dropdown`/`-toggle`/`-menu`/`-option`/`-flag`/`-arrow`; JS hooks stay `data-lang-dropdown`/`data-lang-toggle`/`data-lang-menu` (shared).
**Alternatives**: Reuse `.header__lang-*` classes directly; single shared class.
**Rationale**: Footer needs distinct styling (white-on-dark landing, upward menu, 44px touch target). Shared JS hooks keep `initLangDropdown()` unchanged in logic; footer-specific classes keep styling independent.

## Data Flow

```
PHP home.php  →  $languages[LANG] → toggle flag + 4 <a> options (aria-current on active)
                              │
main.js initLangDropdown()  ──querySelectorAll('[data-lang-dropdown]')──┐
       │ click toggle ⇄ aria-expanded/hidden    outside-click ⇄ close    Escape ⇄ close+focus
       └── each instance runs in its own closure (LS-005)
CSS home.css  →  .onboarding__lang-menu { bottom: 100%+8px; right:0 }  (upward, LS-006)
              →  .onboarding__footer { grid 1fr auto 1fr }            (LS-007)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `src/frontend/templates/pages/home.php` | Modify | Add `$languages`/`$currentLang`/`$currentFlag` (lines 11-14 area). Replace `.onboarding__lang-group` (166-215) with `[data-lang-dropdown]` markup (button flag+chevron, `ul>li>a` per locale, `aria-current`). Move `#pwa-install-container` as middle grid child (same markup, drop `position: fixed` reliance). |
| `src/frontend/css/pages/home.css` | Modify | `.onboarding__footer` flex → grid `1fr auto 1fr`. Add `.onboarding__lang-*` (mirror header, upward, 44px min, fade+rise 160ms, reduced-motion-off). Delete `.onboarding__lang-group`/`-btn`/`-btn--active` (779-828). Delete fixed `.onboarding__pwa-wrapper` positioning (637-646) + `@media max-width:767px` CTA rule (688-693); keep `[hidden]` + `pwaBounce` + pwa-btn. |
| `public/js/main.js` | Modify | `initLangDropdown()`: `querySelector('[data-lang-dropdown]')` → `querySelectorAll` + `forEach` loop; listeners scoped per-instance (outside-click & Escape can stay document-level but check `any open`). |

## Interfaces / Contracts

```php
// home.php top (after line 14)
$languages = [
    'ca' => ['label' => 'Català',     'flag' => 'favicon_CAT.webp'],
    'es' => ['label' => 'Castellano', 'flag' => 'favicon_ES.webp'],
    'en' => ['label' => 'English',    'flag' => 'favicon_UK.webp'],
    'uk' => ['label' => 'Українська', 'flag' => 'favicon_UKR.webp'],
];
$currentLang = LANG;
$currentFlag = $languages[$currentLang]['flag'] ?? 'favicon_CAT.webp';
```
```html
<div class="onboarding__lang-dropdown" data-lang-dropdown>
  <button type="button" class="onboarding__lang-toggle"
          aria-haspopup="listbox" aria-expanded="false"
          aria-controls="footer-lang-menu" data-lang-toggle
          aria-label="<?= __('lang.switch') ?>">
    <img src="/img/icons/<?= $currentFlag ?>" alt="" class="onboarding__lang-flag">
    <span class="onboarding__lang-arrow" aria-hidden="true">▼</span>
  </button>
  <ul class="onboarding__lang-menu" id="footer-lang-menu"
      data-lang-menu hidden>
    <?php foreach ($languages as $code => $info): ?>
      <li><a href="<?= htmlspecialchars($baseUri . $langSeparator . 'lang=' . $code, ENT_QUOTES, 'UTF-8') ?>"
             class="onboarding__lang-option"
             <?= $code === $currentLang ? 'aria-current="true"' : '' ?>>
        <img src="/img/icons/<?= $info['flag'] ?>" alt="" class="onboarding__lang-option-flag">
        <span><?= $info['label'] ?></span></a></li>
    <?php endforeach; ?>
  </ul>
</div>
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Manual | Open/close via toggle, outside-click, Escape (header+footer coexist) | Browser DevTools, keyboard-only |
| A11y | `aria-expanded`/`aria-controls`/`aria-current` correct; focus returns on Escape; SR announces "listbox" | VoiceOver/NVDA + axe DevTools |
| Responsive | 360px `uk` no overflow + ellipsis; CTA centered; CTA hidden keeps symmetry | DevTools 360px; toggle `[hidden]` |
| Build | `php -l home.php`; `php scripts/test-sync.php` | CLI |
| Reduced motion | Menu appears instantly when RM active | DevTools rendering emulation |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. Language links are existing `?lang=` GET navigation already in production; behavior unchanged.

## Migration / Rollout

No migration. `git revert` the 3-file change restores the flag row and fixed CTA; reverting `main.js` (2 lines: `querySelector`→`querySelectorAll`+loop) has no effect on the header. No DB/cache/SEO side effects — same `?lang=` URLs, same `hreflang` (untouched, SG-005).

## Open Questions

- [ ] iOS "Add to Home Screen" toast (z 9995) renders under landing (z 9999) — verify opportunistically at apply; separate fix if confirmed (out of scope here).
- [ ] Should the toggle chevron be the `▼` glyph (header uses glyph) or an inline SVG for crispness? Default: glyph, to match header exactly.