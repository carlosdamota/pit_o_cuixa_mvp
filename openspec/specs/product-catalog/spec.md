# Product Catalog Specification

## Purpose

Public-facing product browsing experience: home page with hero/featured items, full menu page with category filtering, product cards with bilingual content, and "Order now" deep links to last.shop.

## Requirements

### PC-001: Home Page Rendering

The system MUST render a home page at `/` containing: hero section with business imagery, featured products carousel/grid, business info (hours, location, phone), and navigation to the full menu.

#### Scenario: Home page loads successfully

- GIVEN a visitor accesses `https://pitocuixa.es/`
- WHEN the page renders
- THEN the hero section, at least 3 featured products, and business info SHALL be visible
- AND all text SHALL display in the visitor's selected locale (ES or EN)

#### Scenario: Featured products link to last.shop

- GIVEN a featured product card is displayed on the home page
- WHEN the visitor clicks "Order now" / "Pedir ahora"
- THEN the browser SHALL navigate to the product's `last_shop_url`

### PC-002: Menu Page with Category Filter

The system MUST render a menu page at `/menu` displaying all active products grouped by category, with a sticky filter bar allowing the visitor to filter by category.

#### Scenario: Menu page shows all categories

- GIVEN a visitor navigates to `/menu`
- WHEN the page renders
- THEN all active categories SHALL appear as filter tabs
- AND products SHALL be grouped under their respective category headings

#### Scenario: Category filter selection

- GIVEN the visitor is on the menu page
- WHEN they tap/click the "Croquetas" category tab
- THEN only products in the "Croquetas" category SHALL be visible
- AND the "Croquetas" tab SHALL be visually highlighted as active

#### Scenario: "All" filter resets view

- GIVEN a specific category filter is active
- WHEN the visitor taps "All" / "Todo"
- THEN all active products SHALL be displayed again

### PC-003: Product Card Display

The system MUST render each product as a card containing: product image, bilingual name, bilingual description, price (EUR), and an "Order now" CTA button.

#### Scenario: Product card shows complete information

- GIVEN a product with all fields populated
- WHEN the card renders
- THEN image, name, description, price formatted as `€X,XX`, and CTA button SHALL be visible

#### Scenario: Product without image

- GIVEN a product with `image_url` = NULL
- WHEN the card renders
- THEN a placeholder image or category-colored background SHALL display

### PC-004: Bilingual Content (ES/EN)

The system MUST serve all user-facing text in Spanish or English based on the visitor's locale selection. Default locale SHALL be Spanish.

#### Scenario: Locale switch

- GIVEN the visitor is on any page in Spanish
- WHEN they toggle the language selector to English
- THEN all visible text SHALL update to English within the current page
- AND the selection SHALL persist across navigation (via URL parameter or cookie)

#### Scenario: Default locale

- GIVEN a first-time visitor with no locale preference
- WHEN any page loads
- THEN Spanish content SHALL be displayed by default

### PC-005: Mobile-First Responsive Layout

The system MUST render correctly at viewport widths from 360px to 1280px+. The menu page SHALL use a single-column layout on mobile and a multi-column grid on tablet/desktop.

#### Scenario: Mobile layout (360px)

- GIVEN a viewport width of 360px
- WHEN the menu page renders
- THEN product cards SHALL stack in a single column
- AND the category filter bar SHALL be horizontally scrollable

#### Scenario: Desktop layout (1280px)

- GIVEN a viewport width of 1280px
- WHEN the menu page renders
- THEN product cards SHALL display in a multi-column grid (3-4 columns)

## Contracts

### Data Dependencies

| Source | Field | Usage |
|--------|-------|-------|
| `GET /api/products` | `products[]` | Populate product cards |
| `GET /api/categories` | `categories[]` | Populate filter tabs |
| `GET /api/menu` | `groups[]` | Pre-grouped menu data |

### Product Card Template Contract

```
ProductCard {
  image: string | null
  name: string          (localized)
  description: string   (localized)
  price: string         (formatted "€X,XX")
  orderUrl: string      (last_shop_url)
}
```

## Constraints

- No cart/checkout — ordering redirects to last.shop
- Images are read-only Cloudinary URLs (no upload in Sprint 1)
- ~45 products, 11 categories — no pagination needed in Sprint 1
- Must work without JavaScript for basic rendering (progressive enhancement)

## Acceptance Criteria

- [ ] Home page renders hero + featured products + business info
- [ ] Menu page displays all ~45 products grouped by 11 categories
- [ ] Category filter shows/hides products correctly
- [ ] Product cards display image, name, description, price, CTA
- [ ] "Order now" buttons link to correct last.shop URLs
- [ ] ES/EN toggle works on all pages
- [ ] Layout is usable at 360px viewport width
- [ ] Pages load without JavaScript (server-rendered HTML)
