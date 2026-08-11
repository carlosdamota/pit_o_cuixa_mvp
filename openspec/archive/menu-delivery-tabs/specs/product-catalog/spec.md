# Delta for product-catalog

## MODIFIED Requirements

### PC-002: Menu Page with Category Filter

The system MUST render a menu page at `/menu` displaying all active products grouped by category, with a sticky filter bar allowing the visitor to filter by category. The filter bar MUST NOT include an "All" / "Todo" tab. The "Mas vendidos" tab SHALL appear first (unselected by default). The first category tab SHALL appear second and SHALL be selected by default.

(Previously: PC-002 included an "All" / "Todo" tab as the default selection; now the first category is selected by default and the "All" tab is removed.)

#### Scenario: Menu page shows all categories

- GIVEN a visitor navigates to `/menu`
- WHEN the page renders
- THEN all active categories SHALL appear as filter tabs
- AND products SHALL be grouped under their respective category headings

#### Scenario: Category filter selection

- GIVEN the visitor is on the menu page
- WHEN they tap/click a category tab
- THEN only products in that category SHALL be visible
- AND the tapped tab SHALL be visually highlighted as active

#### Scenario: Default tab selection on page load

- GIVEN a visitor navigates to `/menu` without a `?cat=` parameter
- WHEN the page renders
- THEN the "Mas vendidos" tab SHALL appear first and SHALL NOT be selected
- AND the first category tab SHALL appear second and SHALL be selected by default
- AND only products in the first category SHALL be visible

#### Scenario: ScrollSpy highlights active category

- GIVEN the visitor is on the menu page with a category tab selected
- WHEN the visitor scrolls through product sections
- THEN the filter bar SHALL highlight the category tab matching the currently visible section
- AND only products in the highlighted category SHALL remain visible

#### Scenario: Valid `?cat=` URL parameter

- GIVEN a visitor navigates to `/menu?cat=valid-slug`
- WHEN the page renders
- THEN the tab matching `valid-slug` SHALL be selected
- AND only products in that category SHALL be visible

#### Scenario: Unknown or legacy `?cat=all` URL parameter

- GIVEN a visitor navigates to `/menu?cat=all` or `/menu?cat=unknown-slug`
- WHEN the page renders
- THEN the first category tab SHALL be selected by default
- AND only products in the first category SHALL be visible
- AND no error SHALL be displayed
