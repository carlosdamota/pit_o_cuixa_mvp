## Exploration: Hero Menu Slider for Menu Page

### Current State

The menu page (`GET /menu`) currently renders a **text-only hero section** via `<section class="menu-hero section">` in `src/frontend/templates/pages/menu.php`. It contains:
- `<h1 class="section__title">` — "La nostra carta" (i18n key: `menu.heading`)
- `<p class="section__subtitle">` — subtitle (i18n key: `menu.subtitle`)

Below the hero sits the sticky `.filter-bar` (category tabs + search), then `.menu-products` (product groups rendered SSR).

The **home page** (`/`) has a separate fullscreen yellow landing with 3 category buttons — this MUST remain unchanged.

There is **no existing slider or carousel code** anywhere in the project. No third-party dependencies (zero JS/CSS libs). The project uses **vanilla ESM JS**, vanilla CSS with BEM, and CSS custom properties (design tokens).

The `/img/menu-slider/` directory does **not exist yet** — images will need to be manually uploaded there.

### Affected Areas

| File | Why affected |
|------|-------------|
| `src/frontend/templates/pages/menu.php` | Replace current `.menu-hero` section with slider markup |
| `public/js/menu-slider.js` | **NEW** — Vanilla JS ESM module: autoplay + touch swipe + keyboard nav |
| `public/js/main.js` | Import and init `menuSlider()` from the new module |
| `public/css/components/menu-slider.css` | **NEW** — BEM styles for slider (transitions, responsive, reduced motion) |
| `src/backend/pages/menu.php` | Possibly pass a `show_slider` flag or slider images data to template |
| `src/shared/config.php` | Optional: menu availability flag or schedule config |
| `.env` | Optional: `MENU_AVAILABLE=true/false` toggle |
| `src/shared/i18n/ca.php` | Add slider-specific i18n keys (e.g. `menu.slider.aria`) |
| `src/shared/i18n/es.php` | Same |
| `src/shared/i18n/en.php` | Same |
| `public/sw.js` | Update cache strategy for new slider images (LRU cap may need adjusting) |
| `public/img/menu-slider/` | **NEW** — Static images manually uploaded here |

### Approaches

#### 1. Vanilla JS Slider with CSS Transitions (recommended)

**Description:** A lightweight, fully custom slider built with vanilla JS. Uses CSS `transform: translateX()` for slide transitions with `transition` for smooth animation. JS handles: autoplay timer (5s), touch swipe detection (touchstart/touchmove/touchend), and keyboard navigation (ArrowLeft/ArrowRight). HTML uses a container `<div class="menu-slider__track">` with individual `<figure class="menu-slider__slide">` elements.

**Pros:**
- Zero external dependencies — aligns with project constraints
- Full control over behavior, accessibility, and performance
- ~150 lines of JS, clean ESM module
- CSS transitions are GPU-accelerated (smooth on mobile)
- Can use `prefers-reduced-motion` media query to disable auto-play
- Touch swipe works natively with Pointer Events API (touch + mouse unified)
- Works with existing ESM pattern in `main.js`

**Cons:**
- Must implement touch detection manually (no `swipe` library)
- Need to handle edge cases: tab visibility (pause autoplay), resize, image load timing
- Slightly more code than approach 2 but still under 200 lines

**Effort:** Medium

#### 2. CSS-Only Slider with `scroll-snap`

**Description:** Uses CSS `scroll-snap-type: x mandatory` on a horizontally scrollable container. Images are laid out in a flex row. Autoplay requires JS to manipulate `scrollLeft`. Touch swipe is native (browser handles it).

**Pros:**
- Very little JS needed (only autoplay timer, dots navigation, and scroll reset)
- Native scroll behavior is smooth and accessible
- Touch swipe works out of the box
- Scroll position is naturally preserved

**Cons:**
- `scroll-snap` doesn't provide slide "transition" effects (no fade/crossfade)
- Autoplay requires polling `scrollLeft` or using `scroll-behavior: smooth` + JS interval
- Less control over animation timing and easing
- Scrollbar appearance inconsistency across browsers
- Harder to implement slide counter / active dot indicator cleanly

**Effort:** Low-Medium

#### 3. Hybrid: CSS scroll-snap + JS Enhancement

**Description:** Base slider uses CSS `scroll-snap` for default behavior. JS layer adds autoplay timer, dot navigation, keyboard arrows, and pause-on-hover. JS uses `element.scrollBy({ left, behavior: 'smooth' })` for programmatic navigation.

**Pros:**
- Best of both worlds: native scroll behavior + JS enhancements
- Touch works natively without `touchstart` listeners
- Less JS than approach 1 (~100 lines)
- Degrades gracefully if JS fails (still scrollable)

**Cons:**
- Two animation systems competing (scroll + CSS transition) — can feel janky
- Hard to achieve a true "slide" feel with crossfade or slide-in effects
- Autoplay with `scrollBy` + smooth behavior can queue and build up
- No built-in "wrap around" behavior (endless loop)
- Dots synchronization with scroll position requires `IntersectionObserver` or polling

**Effort:** Medium

### Recommendation

**Approach 1: Vanilla JS Slider with CSS Transitions** — it gives full control, matches the project's vanilla discipline, avoids the scroll-snap jank issues, and produces a polished restaurant-slider experience. The JS module would be ~150 lines and follows the existing ESM pattern.

Key design decisions:
- **Autoplay**: `setInterval` at 5000ms, paused when tab is hidden (`visibilitychange`), paused on `mouseenter`/`focusin`, resumed on `mouseleave`/`focusout`
- **Swipe**: Pointer Events API (`pointerdown`/`pointermove`/`pointerup`) for unified touch + mouse, with 50px threshold
- **Loop**: Infinite carousel — wrap from last to first and vice versa
- **Transitions**: CSS `transition: transform 0.4s ease` on `.menu-slider__track`
- **Dots**: `<ol>` with `<li>` buttons for slide indicator + navigation (accessible)
- **Reduced motion**: `prefers-reduced-motion: reduce` disables autoplay and shrinks transition duration
- **Lazy loading**: `loading="lazy"` on images, slide rendering can be done on-demand
- **Responsive**: Single-column slider at all breakpoints (full-width container), images use `object-fit: cover` with `aspect-ratio`

### Conditional Display Options

| Option | Mechanism | Pros | Cons | Effort |
|--------|-----------|------|------|--------|
| **Always show** | Slider always renders at `/menu` | Simplest, no admin toggle | Images may go stale, no off-switch | Low |
| **DB flag** | New `config` table or `settings` row: `menu_slider_enabled` | Admin-controllable, persists | Requires schema migration + admin UI | Medium |
| **ENV flag** | `MENU_SLIDER_ENABLED=true/false` in `.env` | Simple, deploy-controlled | Requires env change to toggle, no admin UI | Low |
| **File-based** | Presence of images in `/img/menu-slider/` | Self-detect, zero config | Cannot disable without deleting images | Low |
| **Schedule** | DB column for `active_from`/`active_until` | Time-based auto show/hide | Over-engineered for current needs | High |

**Recommended conditional display:** **File-based detection** — PHP checks if `/img/menu-slider/` has images (via `glob()` or `is_dir()`). If empty, don't render the slider HTML/css/js. This is zero-config and self-managing. If an admin toggle is needed later, add a DB flag.

### Image Optimization Strategy

| Concern | Approach |
|---------|----------|
| **Format** | Use **WebP** as primary, JPEG as fallback (`<picture>` element) |
| **Responsive** | Provide 3 breakpoints: 360px, 768px, 1200px (mobile-first) |
| **Lazy loading** | `loading="lazy"` on all slides, `decoding="async"` |
| **Dimensions** | Fixed `aspect-ratio: 16/9` (e.g., 1200×675) to prevent CLS |
| **Object fit** | `object-fit: cover` for consistent framing |
| **Max size** | Keep files under 200KB each at highest res |
| **SW caching** | Images already cached via `images-v1` with LRU cap of 30. Cap is sufficient for ~5-8 slider images. If adding many images over time, consider increasing cap. |
| **Preload** | Preload the first slide image via `<link rel="preload" as="image">` in the layout |

### Accessibility Considerations

| Requirement | Implementation |
|-------------|---------------|
| **ARIA** | `role="region"` with `aria-roledescription="carousel"`, `aria-label` on the slider container |
| **Slide labels** | `aria-roledescription="slide"` + `aria-label="Slide {n} of {total}"` on each slide |
| **Keyboard nav** | `ArrowLeft`/`ArrowRight` for prev/next, `Home`/`End` for first/last |
| **Focus trap** | Focusable elements inside slider should be reachable; arrow keys navigate slides when focus is on slider |
| **Dot nav** | `<button>` elements with `aria-label="Go to slide {n}"` and `aria-current="true"` |
| **Pause** | Autoplay pauses on `mouseenter`, `focusin` within slider, and tab `visibilitychange` |
| **Reduced motion** | `@media (prefers-reduced-motion: reduce)` disables autoplay, sets transition to 0.001s |
| **Alt text** | Every image MUST have meaningful `alt` text (descriptive, not just decorative) |
| **Tab order** | Slider must not trap tab navigation; tab proceeds through slide content naturally |
| **Announce** | Use `aria-live="polite"` to announce current slide changes to screen readers |

### Performance Impact

| Metric | Impact | Mitigation |
|--------|--------|------------|
| **Initial load** | 5-8 extra images = ~200KB-1.6MB additional load | Lazy loading, WebP, responsive `srcset` |
| **LCP** | Hero image may compete with first product card | Preload the first slide image |
| **CLS** | No layout shift if `aspect-ratio` is set on slides | Fixed `aspect-ratio: 16/9` on `.menu-slider__slide` |
| **JS** | ~150 lines (minified ~4KB) | ESM module loaded only on menu page via `init()` guard |
| **CSS** | ~200 lines (minified ~5KB) | Page-specific CSS loaded conditionally |
| **Memory** | DOM: 5-8 slide elements + dots + arrows (negligible) | Not a concern |
| **Battery** | CSS transitions are GPU-accelerated; autoplay uses `setInterval` | Pause on invisible tab |
| **SW cache** | Images cached via `images-v1` with LRU cap of 30 | Cap is sufficient, monitor if images exceed ~25 |

### Risks

1. **Image loading**: If images are large or slow to load, the slider can appear broken. Mitigation: preload first slide, lazy load others, keep files under 200KB.
2. **Swipe on desktop**: Pointer Events API handles this best, but some trackpad gestures may conflict. Mitigation: threshold-based activation, don't prevent default page scroll.
3. **Autoplay annoyance**: Auto-rotating content can be disorienting. Mitigation: pause on interaction, `prefers-reduced-motion`, clear pause button per WCAG 2.2.1.
4. **CSR flash**: If JS fails, the slider won't render. Mitigation: SSR fallback (render first image as static if slider is disabled or JS fails), progressive enhancement.
5. **Mobile viewport**: Slide height needs to be proportional. Mitigation: `aspect-ratio` + `max-height: 50vh` so it doesn't dominate the viewport.
6. **No `/img/menu-slider/` directory yet**: Images don't exist. Mitigation: file-based detection will gracefully show the old static hero until images are uploaded.

### Ready for Proposal

**Yes.** The exploration is complete. The orchestrator should tell the user that:
- A vanilla JS slider is feasible and aligns with the project's zero-dependency discipline
- Approach 1 (Vanilla JS + CSS Transitions) provides the best UX and control
- Conditional display can use simple file-based detection (PHP checks `/img/menu-slider/`)
- The change touches ~8 files plus 2 new files
- Implementation requires: slider JS module, slider CSS, template update, main.js import, SW cache consideration, i18n keys
- Images must be manually prepared as WebP + JPEG in 3 resolutions before or during implementation
- The home page is explicitly NOT affected
