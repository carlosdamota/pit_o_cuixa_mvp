# Delta for menu-search

## ADDED Requirements

### Requirement: Search Input Rendering

The system MUST render a search input above the category filter tabs inside the sticky filter bar on the menu page. The input MUST be `<input type="search">` with an associated `<label>` and `aria-live="polite"` region for result announcements.

#### Scenario: Search input visible on menu page

- GIVEN a visitor navigates to `/menu`
- WHEN the page renders
- THEN a search input SHALL be visible above the category filter tabs
- AND the input SHALL have a visible label (visually hidden or inline)
- AND the placeholder text SHALL display in the visitor's locale (ES: "Buscar productos...", EN: "Search products...")

#### Scenario: Search input accessible without JavaScript

- GIVEN JavaScript is disabled
- WHEN the menu page loads
- THEN the search input SHALL be present in the DOM
- AND all products SHALL remain visible via category tabs (progressive enhancement)

### Requirement: Client-Side Text Matching

The system MUST filter product cards in real-time based on text input. Matching SHALL be case-insensitive against the product's name and description (both locales) using the `data-search-text` attribute. Minimum query length: 2 characters.

#### Scenario: Search filters products by name

- GIVEN the visitor is on the menu page with all products visible
- WHEN they type "croqueta" in the search input
- THEN only cards whose `data-search-text` contains "croqueta" SHALL be visible
- AND filtering SHALL occur within 16ms per keystroke

#### Scenario: Search matches description text

- GIVEN a product with description containing "pollo" (chicken)
- WHEN the visitor searches "pollo"
- THEN that product card SHALL be visible
- AND the match SHALL be case-insensitive

#### Scenario: Minimum character threshold

- GIVEN the search input is empty
- WHEN the visitor types 1 character
- THEN no filtering SHALL occur (all products remain visible)
- WHEN they type a 2nd character
- THEN filtering SHALL activate

### Requirement: Search AND Category Filter Integration

The system MUST apply search filtering within the currently active category tab. Search and category SHALL use AND logic.

#### Scenario: Search within active category

- GIVEN the "Bocadillos" category tab is active
- WHEN the visitor searches "pollo"
- THEN only bocadillo products matching "pollo" SHALL be visible
- AND products from other categories SHALL remain hidden

#### Scenario: Clear search restores category view

- GIVEN a search query is active within a category
- WHEN the visitor clicks the clear button (×)
- THEN the search input SHALL be cleared
- AND all products in the active category SHALL be visible again

### Requirement: No-Results State

The system MUST display a localized message when search + filter yields zero matching products.

#### Scenario: No results found

- GIVEN the visitor searches for "xyz123" (no matches)
- WHEN zero cards match the query
- THEN a no-results message SHALL be displayed (ES: "No se encontraron productos", EN: "No products found")
- AND the message SHALL be announced via `aria-live` region

### Requirement: i18n Support

The system MUST provide search-related strings in Spanish and English. Keys: `menu.search.placeholder`, `menu.search.no_results`.

#### Scenario: Locale switch updates search strings

- GIVEN the visitor is on the menu page in Spanish
- WHEN they toggle to English
- THEN the search placeholder SHALL update to "Search products..."
- AND the no-results message SHALL update to "No products found"

---

# Delta for product-catalog

## MODIFIED Requirements

### Requirement: PC-002: Menu Page with Category Filter

The system MUST render a menu page at `/menu` displaying all active products grouped by category, with a sticky filter bar containing a search input above the category tabs allowing the visitor to filter by text search and/or category.

#### Scenario: Menu page shows search input and all categories

- GIVEN a visitor navigates to `/menu`
- WHEN the page renders
- THEN a search input SHALL appear above the category filter tabs
- AND all active categories SHALL appear as filter tabs below the search
- AND products SHALL be grouped under their respective category headings

#### Scenario: Category filter selection with search

- GIVEN the visitor is on the menu page
- WHEN they type "croqueta" in the search input
- AND they tap/click the "Croquetas" category tab
- THEN only products in "Croquetas" matching "croqueta" SHALL be visible
- AND the "Croquetas" tab SHALL be visually highlighted as active

#### Scenario: "All" filter resets category view

- GIVEN a specific category filter is active with a search query
- WHEN the visitor taps "All" / "Todo"
- THEN all active products matching the search query SHALL be displayed
- AND the search input SHALL retain its value

(Previously: Menu page had only category filter tabs without search input)

### Requirement: PC-005: Mobile-First Responsive Layout

The system MUST render correctly at viewport widths from 360px to 1280px+. The menu page SHALL use a single-column layout on mobile and a multi-column grid on tablet/desktop. The sticky filter bar SHALL accommodate the search input without overflow.

#### Scenario: Mobile layout (360px) with search

- GIVEN a viewport width of 360px
- WHEN the menu page renders
- THEN product cards SHALL stack in a single column
- AND the search input and category filter tabs SHALL share the same horizontal row (compact layout)
- OR the search input SHALL stack vertically above the tabs if horizontal space is insufficient
- AND the filter bar SHALL remain horizontally scrollable for tabs

#### Scenario: Desktop layout (1280px) with search

- GIVEN a viewport width of 1280px
- WHEN the menu page renders
- THEN product cards SHALL display in a multi-column grid (3-4 columns)
- AND the search input SHALL appear inline with or above the category tabs

(Previously: Mobile layout had only horizontally scrollable category tabs without search input)
