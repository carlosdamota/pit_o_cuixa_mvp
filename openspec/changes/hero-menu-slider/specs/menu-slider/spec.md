# Menu Slider Specification

## Purpose

Image slider component for the `/menu` page hero section. Supports autoplay, touch swipe, keyboard navigation, and a CSS-only decorative fallback when the slider is disabled or no images are available.

## Requirements

### MS-001: Conditional Slider Display

The system MUST display the image slider on the menu page when the admin flag `menu_slider_enabled` is `'1'` AND at least one image exists in `/img/menu-slider/`. The system MUST display the CSS-only fallback hero when the flag is `'0'` OR no images exist. The home page MUST NOT be affected.

#### Scenario: Slider shown when enabled with images

- GIVEN the admin flag `menu_slider_enabled` is `'1'`
- AND `/img/menu-slider/` contains at least one image file
- WHEN a visitor loads `/menu`
- THEN the system SHALL render the image slider component
- AND SHALL NOT render the CSS fallback hero

#### Scenario: Fallback when flag is off

- GIVEN the admin flag `menu_slider_enabled` is `'0'`
- WHEN a visitor loads `/menu`
- THEN the system SHALL render the CSS-only decorative hero
- AND SHALL NOT load the slider JavaScript module

#### Scenario: Fallback when no images exist

- GIVEN the admin flag `menu_slider_enabled` is `'1'`
- AND `/img/menu-slider/` is empty or does not exist
- WHEN a visitor loads `/menu`
- THEN the system SHALL render the CSS-only decorative hero

### MS-002: Autoplay

The slider MUST automatically advance to the next slide every 5 seconds. Autoplay MUST pause when the user interacts with the slider (swipe, keyboard, or hover) and resume after 5 seconds of inactivity.

#### Scenario: Autoplay advances slides

- GIVEN the slider is visible with 3 images
- WHEN 5 seconds elapse without user interaction
- THEN the slider SHALL transition to the next slide
- AND SHALL loop back to the first slide after the last

#### Scenario: Autoplay pauses on interaction

- GIVEN the slider is autoplaying
- WHEN the user swipes or presses an arrow key
- THEN the current transition SHALL complete
- AND autoplay SHALL resume after 5 seconds of inactivity

### MS-003: Touch Swipe Navigation

The slider MUST support touch swipe navigation using Pointer Events. A horizontal swipe of at least 50px SHALL advance or rewind one slide. The slider MUST NOT interfere with vertical page scrolling (`touch-action: pan-y`).

#### Scenario: Swipe left advances to next slide

- GIVEN the slider is on slide 1 of 3
- WHEN the user swipes left by 50px or more
- THEN the slider SHALL transition to slide 2

#### Scenario: Swipe right rewinds to previous slide

- GIVEN the slider is on slide 2 of 3
- WHEN the user swipes right by 50px or more
- THEN the slider SHALL transition to slide 1

#### Scenario: Short swipe does not trigger navigation

- GIVEN the slider is visible
- WHEN the user swipes horizontally by less than 50px
- THEN the slider SHALL NOT change slides

### MS-004: Keyboard Navigation

The slider MUST support keyboard navigation. When the slider has focus, `ArrowRight` SHALL advance to the next slide and `ArrowLeft` SHALL go to the previous slide.

#### Scenario: Arrow key navigation

- GIVEN the slider has keyboard focus
- WHEN the user presses `ArrowRight`
- THEN the slider SHALL advance to the next slide
- AND the new slide's content SHALL receive focus management

#### Scenario: Wrap-around on keyboard

- GIVEN the slider is on the last slide
- WHEN the user presses `ArrowRight`
- THEN the slider SHALL transition to the first slide

### MS-005: CSS Transition Animation

Slide transitions MUST use CSS `translateX()` with a `transition` duration of approximately 0.4s ease. No JavaScript animation libraries SHALL be used.

#### Scenario: Smooth slide transition

- GIVEN the slider is transitioning from slide 1 to slide 2
- WHEN the transition triggers
- THEN the visual change SHALL use CSS `translateX()` animation
- AND the duration SHALL be approximately 400ms

### MS-006: CSS-Only Fallback Hero

When the slider is not displayed, the system MUST render a CSS-only decorative hero using a gradient background, pattern, and styled text. The fallback MUST NOT depend on any image files.

#### Scenario: Fallback renders without images

- GIVEN the slider conditions are not met
- WHEN the menu page loads
- THEN the hero section SHALL display a gradient background
- AND SHALL display the menu title and subtitle with styled typography
- AND SHALL NOT reference any image files

### MS-007: Accessibility

The slider MUST implement ARIA carousel patterns (`role="region"`, `aria-roledescription="carousel"`, `aria-live`). The system MUST respect `prefers-reduced-motion` by disabling autoplay and transitions when the user preference is set.

#### Scenario: ARIA attributes present

- GIVEN the slider is rendered
- WHEN inspecting the DOM
- THEN the slider container SHALL have `role="region"` and `aria-roledescription="carousel"`
- AND each slide SHALL have `role="group"` and `aria-roledescription="slide"`

#### Scenario: Reduced motion preference

- GIVEN the user has `prefers-reduced-motion: reduce` enabled
- WHEN the menu page loads with the slider active
- THEN autoplay SHALL be disabled
- AND slide transitions SHALL be instant (no animation)

### MS-008: Dot Navigation Indicator

The slider MUST display dot indicators showing the current slide position. Each dot MUST be clickable to navigate to the corresponding slide.

#### Scenario: Dot navigation

- GIVEN the slider has 3 slides and 3 dots
- WHEN the user clicks the third dot
- THEN the slider SHALL transition to slide 3
- AND the third dot SHALL be visually highlighted as active

### MS-009: Image Discovery

The system MUST scan `/img/menu-slider/` on the server side at page load to discover available images. Only common image extensions (`.jpg`, `.jpeg`, `.png`, `.webp`) SHALL be included. Images MUST be rendered in alphabetical order by filename.

#### Scenario: Images discovered and ordered

- GIVEN `/img/menu-slider/` contains `b.jpg`, `a.png`, `c.webp`
- WHEN the menu page loads
- THEN the slider SHALL render slides in order: `a.png`, `b.jpg`, `c.webp`

#### Scenario: Non-image files ignored

- GIVEN `/img/menu-slider/` contains `readme.txt` and `photo.jpg`
- WHEN the menu page loads
- THEN only `photo.jpg` SHALL appear as a slide

## Contracts

### Image Directory

| Path | Format | Upload Method |
|------|--------|---------------|
| `/img/menu-slider/` | `.jpg`, `.jpeg`, `.png`, `.webp` | Manual FTP |

### Slider Data Flow

```
PHP scans /img/menu-slider/ → filters image extensions → sorts alphabetically
→ passes ordered list to template → JS module initializes slider
```

## Constraints

- No external dependencies (vanilla JS ESM only)
- Images are static — no upload UI
- Slider only on `/menu` page — home page untouched
- Mobile-first responsive design
- Total JS budget: ~150 lines ESM module

## Acceptance Criteria

- [ ] Autoplay 5s with CSS transitions (~400ms)
- [ ] Touch swipe with 50px threshold via Pointer Events
- [ ] Keyboard nav (ArrowLeft / ArrowRight) with wrap-around
- [ ] Dot navigation indicators (clickable)
- [ ] Admin flag OFF → CSS fallback hero
- [ ] No images → CSS fallback hero
- [ ] `prefers-reduced-motion` disables autoplay and transitions
- [ ] ARIA carousel pattern passes axe audit
- [ ] Vertical scroll not blocked on touch devices
- [ ] No external dependencies added
- [ ] Home page unchanged
