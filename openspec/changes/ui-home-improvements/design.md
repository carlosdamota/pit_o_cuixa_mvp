# Technical Design — UI Home Improvements

## Overview

This design covers 4 capabilities for the mobile-first PWA experience:
1. **FAQ Page** (new) — Dedicated `/faq` route with accordions + JSON-LD
2. **Mobile Navigation** (new) — Redesigned hamburger menu with home links + language selector
3. **Product Catalog** (modified) — Category slider on menu page (mobile only)
4. **SEO/GEO** (modified) — FAQ meta tags + FAQPage JSON-LD + sitemap update

All implementations follow existing patterns: vanilla PHP/CSS/JS, no frameworks, no external dependencies.

---

## Architecture Decisions

### 1. FAQ Page Architecture

**Approach**: New route + controller + template following existing `home.php` and `menu.php` patterns.

**File Structure**:
```
src/backend/pages/faq.php          (NEW — controller)
src/frontend/templates/pages/faq.php (NEW — template)
src/shared/i18n/{ca,es,en}.php     (MODIFIED — add FAQ content)
public/css/pages/faq.css           (NEW — FAQ-specific styles)
```

**Controller** (`src/backend/pages/faq.php`):
- Follow `Home::render()` pattern
- Build `$meta` array with:
  - `title`: `__('faq.title')`
  - `description`: `__('faq.desc')`
  - `canonical`: `Config::siteUrl() . '/faq'`
  - `langs`: array of locale-prefixed URLs (`/faq`, `/es/faq`, `/en/faq`)
  - `jsonld`: FAQPage schema (see SEO section)
- Call `renderPage('faq', $meta, $data)`

**Template** (`src/frontend/templates/pages/faq.php`):
- Render FAQ title from `__('faq.title')`
- Loop through `__('faq.items')` array (structure below)
- Use native `<details>/<summary>` for accordions (no JS required for basic functionality)
- Optional: progressive enhancement JS to allow only-one-open behavior

**i18n Structure** (add to each locale file):
```php
'faq.title' => 'Preguntas frecuentes',
'faq.desc'  => 'Respuestas a las preguntas más comunes sobre Pit o Cuixa.',
'faq.items' => [
    [
        'q' => '¿Hacéis pedidos para llevar?',
        'a' => 'Sí, puedes pedir por teléfono y recoger en tienda.',
    ],
    [
        'q' => '¿Tenéis opciones sin gluten?',
        'a' => 'Sí, consulta nuestra carta de combinados.',
    ],
    // ... more items
],
```

**CSS** (`public/css/pages/faq.css`):
- Style `<details>` with border, padding, hover states
- `<summary>` with chevron indicator (CSS `::marker` or custom)
- Mobile-first: full-width accordions, comfortable tap targets
- Load conditionally in `default.php` when `$pageName === 'faq'`

**Router Registration** (in `public/index.php` or bootstrap):
```php
$router->add('GET', '/faq', [\Pit\Cuixa\Backend\Pages\Faq::class, 'render']);
$router->add('GET', '/{lang}/faq', function($params) {
    // Set locale from $params['lang'], then render FAQ
});
```

---

### 2. Mobile Navigation Architecture

**Approach**: CSS-only redesign scoped to `<640px` media query. No HTML structure changes to toggle button. No JS changes.

**File Structure**:
```
src/frontend/templates/partials/header.php (MODIFIED — add mobile nav content)
public/css/layouts/header.css              (MODIFIED — mobile nav styles)
```

**HTML Changes** (`header.php`):
- Inside existing hamburger dropdown (`.nav-menu` or similar), add:
  - Category links: Pollos, Combinados, Pica-pica (anchor links to home sections)
  - Language selector: 3 links (ca/es/en) using existing locale switch mechanism
- Wrap mobile-specific content in a class like `.mobile-only` for CSS targeting

**Example HTML structure**:
```php
<nav class="nav-menu" data-menu-toggle>
    <!-- Existing desktop nav items -->
    <ul class="desktop-nav">...</ul>
    
    <!-- Mobile-only content (hidden on desktop via CSS) -->
    <div class="mobile-nav">
        <ul class="mobile-links">
            <li><a href="/#pollos"><?= __('home.landing.pollos') ?></a></li>
            <li><a href="/#combinados"><?= __('home.landing.combinados') ?></a></li>
            <li><a href="/#picapica"><?= __('home.landing.picapica') ?></a></li>
            <li><a href="/faq"><?= __('nav.faq') ?></a></li>
        </ul>
        <div class="mobile-lang-selector">
            <a href="?lang=ca">CA</a>
            <a href="?lang=es">ES</a>
            <a href="?lang=en">EN</a>
        </div>
    </div>
</nav>
```

**CSS Changes** (`header.css`):
- Desktop (`>=640px`): `.mobile-nav { display: none; }`
- Mobile (`<640px`):
  - `.desktop-nav { display: none; }`
  - `.mobile-nav { display: block; }`
  - Style `.mobile-links` as vertical list with comfortable spacing
  - Style `.mobile-lang-selector` as inline flex row
  - Ensure tap targets are at least 44x44px (WCAG)

**Language Selector Integration**:
- Use existing `?lang=xx` query parameter mechanism
- No new JS required — links just navigate with `?lang=ca|es|en`
- Locale persists via existing session/cookie logic

---

### 3. Product Catalog — Mobile Category Slider

**Approach**: CSS-only enhancement to existing filter bar. Reuse existing `data-attribute` filter logic. No HTML/PHP template changes.

**File Structure**:
```
public/css/components/filter-bar.css (MODIFIED — mobile slider styles)
```

**CSS Changes** (`filter-bar.css`):
- Mobile (`<640px`):
  - `.filter-bar` container: `display: flex; flex-direction: column;`
  - `.search-bar`: `width: 100%;` (full-width, stationary)
  - `.category-tabs`: `display: flex; overflow-x: auto; scroll-snap-type: x mandatory;`
  - `.category-pill`: `scroll-snap-align: start; flex-shrink: 0;`
  - Hide scrollbar: `::-webkit-scrollbar { display: none; }` + `scrollbar-width: none;`
- Desktop (`>=640px`):
  - No changes — existing layout remains

**Scroll Behavior**:
- Native CSS scroll (no JS carousel)
- `scroll-snap-type: x mandatory` for smooth pill alignment
- `scroll-snap-align: start` on each pill
- Swipe gesture works automatically on touch devices

**Filter Logic**:
- Existing JS (presumably in `main.js`) handles category pill clicks
- No changes needed — just ensure CSS doesn't break existing `data-*` attributes or event listeners

---

### 4. SEO/GEO — FAQ Meta Tags + JSON-LD + Sitemap

**Approach**: Extend existing SEO patterns from `Home` and `Menu` controllers. Add FAQPage JSON-LD to FAQ page. Update sitemap.

**File Structure**:
```
src/backend/pages/faq.php            (MODIFIED — add FAQPage JSON-LD to $meta)
src/backend/pages/sitemap.php        (MODIFIED — add FAQ URLs)
```

**FAQPage JSON-LD** (in `faq.php` controller):
```php
$faqItems = __('faq.items');
$faqJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($item) => [
        '@type' => 'Question',
        'name' => $item['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['a'],
        ],
    ], $faqItems),
];

$meta['jsonld'] = $faqJsonLd;
```

**Meta Tags**:
- Already handled by `default.php` layout (title, description, canonical, OG, hreflang)
- Just ensure FAQ controller passes correct `$meta` array

**Sitemap Update** (`sitemap.php`):
- Add entries for `/faq`, `/es/faq`, `/en/faq`
- Include hreflang annotations for each FAQ URL
- Follow existing sitemap XML structure

**Example sitemap entry**:
```xml
<url>
    <loc>https://pitucaixa.com/faq</loc>
    <xhtml:link rel="alternate" hreflang="ca" href="https://pitucaixa.com/faq"/>
    <xhtml:link rel="alternate" hreflang="es" href="https://pitucaixa.com/es/faq"/>
    <xhtml:link rel="alternate" hreflang="en" href="https://pitucaixa.com/en/faq"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="https://pitucaixa.com/faq"/>
</url>
```

---

## Component Architecture

### FAQ Page Components

```
FAQ Page
├── Header (existing)
├── Main Content
│   ├── FAQ Title (h1)
│   └── FAQ Accordion List
│       └── <details> (per item)
│           ├── <summary> (question)
│           └── <div> (answer)
└── Footer (existing, with FAQ link)
```

### Mobile Navigation Components

```
Header
├── Logo
├── Hamburger Button (existing)
└── Nav Menu (existing container)
    ├── Desktop Nav (hidden on mobile)
    └── Mobile Nav (hidden on desktop)
        ├── Category Links
        │   ├── Pollos
        │   ├── Combinados
        │   └── Picapica
        ├── FAQ Link
        └── Language Selector
            ├── CA
            ├── ES
            └── EN
```

### Product Catalog Slider Components

```
Menu Page Filter Bar
├── Search Bar (full-width, stationary)
└── Category Tabs Container (horizontal scroll on mobile)
    ├── Category Pill 1
    ├── Category Pill 2
    └── ... (scrollable)
```

---

## Data Flow

### FAQ Page Flow

1. User navigates to `/faq`
2. Router matches route → `Faq::render()`
3. Controller loads FAQ items from i18n (`__('faq.items')`)
4. Controller builds `$meta` array with FAQPage JSON-LD
5. Controller calls `renderPage('faq', $meta, $data)`
6. `renderPage()` renders `templates/pages/faq.php` with layout
7. Layout injects meta tags + JSON-LD into `<head>`
8. Template renders accordion list
9. Browser displays page with accordions

### Mobile Navigation Flow

1. User on mobile viewport (`<640px`)
2. CSS hides `.desktop-nav`, shows `.mobile-nav`
3. User taps hamburger button (existing `data-menu-toggle` JS)
4. CSS transitions `.nav-menu` open
5. User sees category links + FAQ link + language selector
6. User taps category link → navigates to home section
7. User taps language link → navigates with `?lang=xx`
8. Locale persists via existing mechanism

### Category Slider Flow

1. User on menu page, mobile viewport (`<640px`)
2. CSS applies horizontal scroll to `.category-tabs`
3. User swipes left/right → pills scroll horizontally
4. User taps pill → existing JS filters products (no changes)
5. Search bar remains stationary (CSS ensures it's outside scroll container)

---

## File Changes Summary

### New Files (4)
- `src/backend/pages/faq.php` — FAQ controller
- `src/frontend/templates/pages/faq.php` — FAQ template
- `public/css/pages/faq.css` — FAQ styles
- `openspec/changes/ui-home-improvements/design.md` — this file

### Modified Files (5)
- `src/shared/i18n/ca.php` — add FAQ content
- `src/shared/i18n/es.php` — add FAQ content
- `src/shared/i18n/en.php` — add FAQ content
- `src/frontend/templates/partials/header.php` — add mobile nav content
- `src/frontend/templates/partials/footer.php` — add FAQ link
- `src/frontend/templates/layouts/default.php` — conditionally load FAQ CSS
- `public/css/layouts/header.css` — mobile nav styles
- `public/css/components/filter-bar.css` — mobile slider styles
- `src/backend/pages/sitemap.php` — add FAQ URLs
- `public/index.php` (or bootstrap) — register FAQ route

**Total**: 4 new + 10 modified = **14 files**

---

## Performance Considerations (PWA Mobile-First)

### FAQ Page
- **No external dependencies** — native `<details>/<summary>` (zero JS for basic functionality)
- **CSS loaded conditionally** — only on FAQ page (saves bandwidth on other pages)
- **FAQ content in i18n files** — no database query, fast render
- **JSON-LD inline** — no additional HTTP request

### Mobile Navigation
- **CSS-only changes** — no JS overhead
- **No new images/icons** — use text links (fast load)
- **Language selector as links** — no dropdown JS, just navigation

### Category Slider
- **CSS-only slider** — no JS carousel library
- **Native scroll** — hardware-accelerated on mobile
- **No lazy-loading needed** — pills are text, not images

### General
- **No new HTTP requests** — all changes are inline CSS/HTML
- **No new fonts** — use existing system fonts
- **No new images** — text-only UI elements

---

## Accessibility Strategy

### FAQ Page
- **Keyboard navigation**: `<details>/<summary>` natively keyboard-accessible
- **Screen readers**: `<summary>` announced as button, `<details>` as region
- **Focus indicators**: ensure `:focus-visible` styles on `<summary>`
- **ARIA**: optional `aria-expanded` on `<summary>` (progressive enhancement)

### Mobile Navigation
- **Keyboard navigation**: hamburger button must be focusable, menu items tabbable
- **Screen readers**: `aria-label="Menú"` on hamburger button, `aria-expanded` state
- **Focus trap**: when menu open, focus stays within menu (optional enhancement)
- **Touch targets**: minimum 44x44px for all links

### Category Slider
- **Keyboard navigation**: pills must be focusable, Enter/Space activates filter
- **Screen readers**: `aria-current="true"` on active pill
- **Focus indicators**: visible `:focus-visible` on pills
- **Scroll indication**: optional visual cue that slider is scrollable (fade edges)

---

## Testing Strategy

### Manual Testing
- **FAQ page**: navigate to `/faq`, test accordions, verify JSON-LD with Google Rich Results Test
- **Mobile nav**: test on real mobile device (iOS Safari, Android Chrome), verify language switch
- **Category slider**: test swipe gesture on mobile, verify filter still works
- **SEO**: check meta tags, hreflang, sitemap with Google Search Console

### Automated Testing (if applicable)
- **Unit tests**: FAQ controller renders without errors
- **Integration tests**: FAQ route returns 200, JSON-LD validates
- **E2E tests**: mobile nav opens on tap, language switch persists

### Validation
- **HTML**: W3C validator (no errors)
- **CSS**: no console errors, responsive at 360px–1280px+
- **Accessibility**: axe-core scan (no critical violations)
- **SEO**: Google Rich Results Test (FAQPage passes)

---

## Rollback Plan

Each feature is independently revertable:

### FAQ Page Rollback
1. Delete `src/backend/pages/faq.php`
2. Delete `src/frontend/templates/pages/faq.php`
3. Delete `public/css/pages/faq.css`
4. Remove FAQ route from router
5. Remove FAQ link from footer + mobile nav
6. Remove FAQ content from i18n files

### Mobile Navigation Rollback
1. Revert `header.php` to previous version
2. Revert `header.css` to previous version

### Category Slider Rollback
1. Revert `filter-bar.css` to previous version

### SEO Rollback
1. Remove FAQPage JSON-LD from FAQ controller
2. Revert sitemap to previous version

---

## Success Criteria

### FAQ Page
- [ ] `/faq` renders with accordion Q&A in all 3 locales
- [ ] FAQPage JSON-LD passes Google Rich Results Test
- [ ] FAQ link present in footer and mobile nav
- [ ] Accordions expand/collapse via click and keyboard
- [ ] All FAQ text localized in ca/es/en

### Mobile Navigation
- [ ] Mobile hamburger shows category links (Pollos, Combinados, Pica-pica)
- [ ] Language selector (ca/es/en) visible in mobile menu
- [ ] All changes scoped to `<640px` — desktop unaffected
- [ ] No JavaScript changes required
- [ ] Language selection persists across navigation

### Category Slider
- [ ] Menu page category slider scrolls horizontally on mobile (<640px)
- [ ] Search bar remains full-width and stationary above slider
- [ ] Category pills tappable and activate existing filter logic
- [ ] Desktop layout unchanged

### SEO/GEO
- [ ] FAQ page has unique localized `<title>` and `<meta description>`
- [ ] FAQ page OG tags present and valid
- [ ] FAQPage JSON-LD passes Google Rich Results Test
- [ ] Hreflang tags on FAQ page are bidirectional
- [ ] Sitemap includes `/faq` with hreflang annotations

---

## Implementation Order

### Phase 1: FAQ Page (Core)
1. Add FAQ content to i18n files (ca/es/en)
2. Create FAQ controller (`src/backend/pages/faq.php`)
3. Create FAQ template (`src/frontend/templates/pages/faq.php`)
4. Create FAQ CSS (`public/css/pages/faq.css`)
5. Register FAQ route in router
6. Load FAQ CSS conditionally in `default.php`

### Phase 2: Mobile Navigation
7. Add mobile nav HTML to `header.php`
8. Add mobile nav CSS to `header.css`
9. Add FAQ link to footer

### Phase 3: Category Slider
10. Add mobile slider CSS to `filter-bar.css`

### Phase 4: SEO Enhancements
11. Add FAQPage JSON-LD to FAQ controller
12. Update sitemap with FAQ URLs

### Phase 5: Testing & Validation
13. Manual testing on mobile devices
14. Validate FAQPage JSON-LD with Google Rich Results Test
15. Check accessibility with axe-core
16. Verify hreflang and sitemap with Google Search Console

---

## Risks & Mitigations

### Risk 1: FAQ content quality
- **Mitigation**: Start with 5–7 high-quality FAQs, iterate based on user feedback

### Risk 2: Mobile nav breaks desktop layout
- **Mitigation**: All changes scoped to `<640px` media query, test at 640px+ to ensure no leakage

### Risk 3: Slider scroll conflicts with page scroll
- **Mitigation**: Use `scroll-snap-type: x mandatory` to constrain horizontal scroll, test on real devices

### Risk 4: FAQPage JSON-LD validation fails
- **Mitigation**: Test with Google Rich Results Test before merge, fix any errors immediately

### Risk 5: Language selector doesn't persist
- **Mitigation**: Rely on existing locale mechanism (session/cookie), test across browsers

---

## Future Enhancements (Out of Scope)

- **Admin UI for FAQ**: allow editing FAQ content via admin panel (currently hardcoded in i18n)
- **FAQ search**: add search box to filter FAQs by keyword
- **FAQ categories**: group FAQs by topic (e.g., "Pedidos", "Horarios", "Alérgenos")
- **Mobile nav animations**: add smooth transitions for menu open/close
- **Category slider images**: add category thumbnails to pills (currently text-only)
- **FAQ analytics**: track which FAQs are expanded most often

---

## Conclusion

This design delivers 4 capabilities with minimal complexity:
- **FAQ Page**: new route + template + CSS, native accordions, JSON-LD for SEO
- **Mobile Navigation**: CSS-only redesign, no JS changes, language selector integrated
- **Category Slider**: CSS-only enhancement, reuses existing filter logic
- **SEO Enhancements**: FAQPage JSON-LD, meta tags, sitemap update

All implementations follow existing patterns (vanilla PHP/CSS/JS), require no external dependencies, and are independently revertable. Total effort: ~14 files, ~400 lines of code (within review budget).
