# Exploration: Language Dropdown for Home Footer

## Current State

The home page (`/`, `src/frontend/templates/pages/home.php:166-216`) renders a fullscreen onboarding landing (`<section class="landing landing--onboarding">`, `position: fixed; 100dvh; overflow: hidden`). The bottom footer (`.onboarding__footer`) contains:

- **FAQ button** (left) — pill link to `/faq`
- **PWA Install CTA** — `#pwa-install-container` is `position: fixed; bottom: 80px; left: 32px; z-index: 9990` (NOT in footer flow; bounces with `pwaBounce` infinite animation)
- **Language selector** (right) — `.onboarding__lang-group`: 4 inline flag links (CAT/ES/EN/UKR), 34px buttons with 12px gaps ≈ **172px of horizontal space**

The user's proposal: replace the 4-flag row with a compact dropdown (opening right-to-left), and move the Install CTA into the footer **centered** between FAQ (left) and the language dropdown (right).

**Critical discovery — an equivalent dropdown already exists in the codebase:**

The header navigation (`src/frontend/templates/partials/nav.php:45-61`) already implements a custom language dropdown with favicon flags, only rendered on menu/faq pages:

```html
<div class="header__lang-dropdown" data-lang-dropdown>
  <button class="header__lang-toggle" aria-haspopup="true" aria-expanded="false" data-lang-toggle aria-label="...">
    <img src="/img/icons/{currentFlag}" class="header__lang-flag"> <span class="header__lang-arrow">▼</span>
  </button>
  <ul class="header__lang-menu" data-lang-menu hidden>
    <li><a href="...?lang=ca" class="header__lang-option ...">…flag + label…</a></li>
    … 4 options …
  </ul>
</div>
```

Wired by `initLangDropdown()` in `public/js/main.js:52-85` (toggle click, outside-click close, Escape close + focus return) and styled by `.header__lang-*` in `src/frontend/css/layouts/header.css:180-273` (menu is `position: absolute; right: 0` — **already right-aligned / "opens from right"**).

The home page does **not** render this header dropdown (the shared header is hidden on home; home has its own `.onboarding__top` with a phone button). The footer flag row is the **only** language switcher on home.

## Affected Areas

| File | Why affected |
|------|-------------|
| `src/frontend/templates/pages/home.php:166-216` | Replace `.onboarding__lang-group` with dropdown markup; move `#pwa-install-container` into footer flow |
| `src/frontend/css/pages/home.css` | Add `.onboarding__lang-dropdown/__toggle/__menu/__option` styles; switch `.onboarding__footer` to grid (`1fr auto 1fr`); drop fixed positioning of `.onboarding__pwa-wrapper` (lines 637-693) |
| `public/js/main.js:52-85` | Harden `initLangDropdown()` from `querySelector` → `querySelectorAll` so the home footer instance binds (it currently binds only the first match) |
| `public/js/home-onboarding.js` | No change required — PWA controller (`pwaContainer.hidden` toggling) is positioning-agnostic |
| `src/frontend/css/layouts/header.css` | Reference only — pattern to mirror; no edit needed |
| i18n (`lang.switch`, `lang.code`, `nav.faq`, `pwa.install`) | No new keys strictly needed; `lang.switch` exists in all 4 locales |

## Approaches

### 1. Custom dropdown mirroring the existing header pattern (recommended)

**Description:** New BEM block `.onboarding__lang-dropdown` on the home footer, structurally identical to `nav.php`'s dropdown (same `data-lang-dropdown` / `data-lang-toggle` / `data-lang-menu` hooks, 4 link options with flag + label, `aria-haspopup` / `aria-expanded` on toggle). Differs from the header in three ways:

- **Opens upward**: `bottom: calc(100% + 8px); right: 0` — the footer sits at the bottom of the fixed 100dvh landing, and `.landing--onboarding` has `overflow: hidden`, so a downward menu would clip off-viewport.
- **Home-styled toggle**: pill with current flag + chevron (matches header visual language), ≥40-44px touch target.
- **Subtle open animation**: fade + rise (`opacity 0→1`, `translateY(8px→0)`, ~160ms), disabled under `prefers-reduced-motion: reduce`.

JS wiring is free: `main.js` is loaded unconditionally on home (`default.php:203`) and `initLangDropdown()` will pick up the only `[data-lang-dropdown]` on the page. Recommended hardening: `querySelectorAll` + loop (2 lines) so header and footer instances can coexist and the pattern becomes a true shared component.

Pros: zero dependencies; visual and behavioral consistency with the header; reuses tested interaction (outside-click, Escape); preserves the `?lang=` GET-switch URL mechanism and hreflang; compact (~44px vs ~172px). Cons: slightly more markup than a `<select>`; needs the upward-open CSS decision. Effort: **Low-Medium** (~1 focused session, 3 files).

### 2. Native `<select>` styled as a compact control

**Description:** Replace flags with `<select name="lang">` + `onchange` submit (the pattern already exists in the desktop hamburger, `nav.php:97-106`). Zero JS, fully keyboard/screen-reader accessible, mobile-native picker.

Pros: smallest code footprint; flawless a11y; mobile-native UX. Cons: no flag imagery (loses the brand's flag aesthetic); inconsistent with the header's custom dropdown; limited styling of the closed state and options. Effort: **Low**.

### 3. Single flag button that cycles languages on click

**Description:** One flag showing the current locale; clicking switches to the next language in the cycle.

Pros: most compact (~34px). Cons: poor discoverability (no menu), accidental multi-switches, weak current-language indication, questionable a11y (no standard pattern for "cycle on click"), and mismatch with the header dropdown. Effort: **Low** but **not recommended**.

### 4. Keep flags, smaller or stacked

**Description:** Shrink buttons to ~24px or stack them vertically.

Pros: keeps one-tap-per-language. Cons: smaller targets hurt touch ergonomics (WCAG 2.5.8) and readability; stacking looks alien in a horizontal footer; doesn't address the underlying clutter. Effort: Low. **Not recommended.**

## Recommendation

**Approach 1** — a custom `.onboarding__lang-dropdown` mirroring the proven header pattern, opening **upward + right-aligned**, wired by the hardened `initLangDropdown()`, with the footer switched to CSS grid for true centering of the Install CTA.

Key design decisions:

- **Toggle content**: current flag + chevron (shows current state at a glance; matches header). Not a bare globe — the flag is the brand's established language metaphor.
- **Footer layout**: `.onboarding__footer` → `display: grid; grid-template-columns: 1fr auto 1fr; align-items: center`. True centering of the middle CTA regardless of side widths (FAQ pill ≈ 100px vs dropdown ≈ 44px — `space-between` flex would NOT center it).
- **CTA relocation**: remove `position: fixed; bottom/left; z-index: 9990` from `.onboarding__pwa-wrapper` (and the `max-width: 767px` repositioning) — it becomes an in-flow grid child. Keep the `pwaBounce` animation and the `[hidden] { display: none !important }` rule (when the PWA is installed/hidden, the center cell empties and the sides stay symmetric in their `1fr` cells).
- **Animation**: fade + rise 160ms on open; `@media (prefers-reduced-motion: reduce)` → no animation.
- **Mobile**: same dropdown; tap toggles, outside tap closes (click events cover touch); toggle ≥ 44px tall.
- **Accessibility**:
  - Toggle: `type="button"`, `aria-haspopup="listbox"`, `aria-expanded`, `aria-controls="{menu-id}"` (the header version lacks `aria-controls` — add it here).
  - Menu: `<ul>` of `<a>` links; active locale gets `aria-current="true"`; links remain natively Tab-focusable (disclosure pattern is semantically correct for navigational links — no roving tabindex needed for 4 items).
  - Escape closes and returns focus to toggle (already in `initLangDropdown`).
  - Current header labels: `alt="Українська"` etc. — keep; add `lang` attribute on options if desired.
- **i18n/SEO**: links keep the `$baseUri . $langSeparator . 'lang=' . $code` construction (home.php:11-14) → cookie set by bootstrap, hreflang (SG-005) unaffected.

## Risks

1. **Clipping**: `.landing--onboarding` is `overflow: hidden` + fixed `100dvh`. A downward-opening menu clips off-viewport. Mitigation: open upward (`bottom: calc(100% + 8px)`).
2. **Narrow viewport with Ukrainian locale**: `nav.faq` = "Часті запитання" and `pwa.install` = "Встановити додаток" are long — FAQ + CTA + toggle can exceed ~320px. Mitigations: grid `1fr auto 1fr`, `min-width: 0` + ellipsis on the FAQ/CTA text, or shorten the CTA label on ≤360px.
3. **Single-instance JS init**: `initLangDropdown()` uses `document.querySelector` — fine today (home has exactly one `[data-lang-dropdown]`), but breaks silently if a second instance ever shares a page. Mitigation: harden to `querySelectorAll` within this change.
4. **Pre-existing z-index suspect (verify, don't assume)**: the iOS "Add to Home Screen" toast (`z-index: 9995`) is appended to `document.body`, while `.landing--onboarding` is `z-index: 9999` — the landing may already render **over** the toast on iOS. Not caused by this change, but the footer/PWA relocation is the right moment to verify and fix if confirmed.
5. **Footer rhythm change**: the CTA was a floating element; moving it in-flow changes the onboarding composition. The grid absorbs the hidden state, but visual QA is required (esp. with `pwaBounce` running inside the footer).
6. **Accessibility regression risk**: don't drop `aria-expanded`/`hidden` toggling parity with the header; the menu must be `hidden` by default in the SSR HTML so no-JS users still see nothing broken (flag links are gone — users without JS would lose the home language switcher; acceptable since the menu links are plain anchors, but note it).

## Ready for Proposal

**Yes.** The exploration is complete. Tell the user:

- Feasible with vanilla CSS/JS; the project **already ships this exact dropdown pattern in the header** (`nav.php` + `main.js initLangDropdown` + `header.css`) — the footer version reuses it, opening upward and right-aligned, and reuses the current-language flag + chevron.
- Install CTA centering is solved with `grid-template-columns: 1fr auto 1fr` on the footer; the fixed positioning (and its media query) is removed.
- The dropdown toggle shrinks the language selector from ~172px to ~44px, freeing the footer for the centered CTA.
- Recommended scope: 3 files (`home.php`, `home.css`, `main.js` — 2-line hardening), Low-Medium effort, one session; plus visual QA and a check of the iOS toast z-index.
- Next: `sdd-propose` → `sdd-spec` → `sdd-design` → `sdd-tasks`.
