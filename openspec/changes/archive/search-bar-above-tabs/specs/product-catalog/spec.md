# Delta for Product Catalog

## MODIFIED Requirements

### Requirement: PC-005: Mobile-First Responsive Layout

The system MUST render correctly at viewport widths from 360px to 1280px+. The menu page SHALL use a single-column layout on mobile and a multi-column grid on tablet/desktop. The filter bar SHALL stack the search bar above the category tabs vertically at ALL viewport widths. The search bar SHALL occupy the full container width. Horizontal scroll SHALL be scoped to the tabs row only — the search bar MUST NOT participate in horizontal scrolling.

#### Scenario: Mobile layout (360px)

- GIVEN a viewport width of 360px
- WHEN the menu page renders
- THEN product cards SHALL stack in a single column
- AND the search bar SHALL display full-width above the filter tabs
- AND the filter tabs SHALL be horizontally scrollable beneath the search bar

#### Scenario: Desktop layout (1280px)

- GIVEN a viewport width of 1280px
- WHEN the menu page renders
- THEN product cards SHALL display in a multi-column grid (3-4 columns)
- AND the search bar SHALL display full-width above the filter tabs
- AND the filter tabs SHALL remain in a single horizontal row below the search bar

#### Scenario: Search bar full width at all breakpoints

- GIVEN any viewport width between 360px and 1280px+
- WHEN the filter bar renders
- THEN the search bar SHALL occupy the full container width
- AND the search bar SHALL NOT share a horizontal row with the filter tabs

#### Scenario: Horizontal scroll scoped to tabs

- GIVEN the viewport width is below 640px and the tabs exceed available width
- WHEN the visitor swipes or scrolls the filter bar area
- THEN only the tabs row SHALL scroll horizontally
- AND the search bar SHALL remain stationary and fully visible

(Previously: At 640px+ the search bar and tabs shared a horizontal flex row with fixed-width search; the entire filter bar scrolled horizontally on mobile via `overflow-x: auto` on `.filter-bar`)

## ADDED Requirements

### Requirement: PC-006: Filter Bar Layout Invariants

The filter bar layout changes MUST NOT alter search functionality, filter tab logic, or JavaScript behavior. All data-attribute selectors (`[data-menu-search]`, `[data-filter]`) SHALL continue to function independently of CSS layout changes. No HTML or PHP template changes SHALL be required.

#### Scenario: Search functionality unchanged

- GIVEN the search bar is positioned above the filter tabs
- WHEN the visitor types in the search input
- THEN product filtering SHALL behave identically to the previous layout

#### Scenario: Filter tab selection unchanged

- GIVEN the filter tabs are in a scrollable row below the search bar
- WHEN the visitor taps a category tab
- THEN the tab SHALL activate and filter products identically to the previous layout

#### Scenario: No JavaScript errors after layout change

- GIVEN the filter bar CSS has been updated
- WHEN the menu page loads and the visitor interacts with search and filters
- THEN the browser console SHALL NOT display any JavaScript errors
