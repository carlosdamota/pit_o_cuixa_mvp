# Delta for Product Catalog

## ADDED Requirements

### PC-007: Mobile Category Slider

The system MUST render the menu page filter bar as a horizontal scrollable category slider on mobile viewports (below 640px). The slider SHALL display category pills that can be swiped/scroll horizontally. The existing search bar SHALL remain full-width above the slider and MUST NOT participate in horizontal scrolling.

#### Scenario: Mobile horizontal category slider

- GIVEN a viewport width below 640px on the menu page
- WHEN the filter bar renders
- THEN the category tabs SHALL display as a horizontally scrollable row of pills
- AND the visitor SHALL be able to swipe/scroll through categories horizontally
- AND the search bar SHALL remain full-width above the slider, stationary

#### Scenario: Slider scroll does not affect search bar

- GIVEN the viewport is below 640px and category pills exceed available width
- WHEN the visitor swipes the category slider
- THEN only the pills row SHALL scroll
- AND the search bar SHALL remain fully visible and stationary

#### Scenario: Category pill tap activates filter

- GIVEN the mobile category slider is displayed
- WHEN the visitor taps a category pill
- THEN the pill SHALL be visually highlighted as active
- AND products SHALL filter to that category (existing filter logic)

#### Scenario: Desktop filter bar unchanged

- GIVEN a viewport width of 640px or above
- WHEN the menu page renders
- THEN the filter bar SHALL display as before (search bar above tabs row)
- AND no horizontal slider behavior SHALL apply

## MODIFIED Requirements

### PC-005: Mobile-First Responsive Layout

The system MUST render correctly at viewport widths from 360px to 1280px+. The menu page SHALL use a single-column layout on mobile and a multi-column grid on tablet/desktop. On mobile viewports (below 640px), the filter bar SHALL display as a horizontal scrollable category slider beneath a full-width search bar. On desktop (640px+), the filter tabs SHALL remain in a single horizontal row below the search bar without slider behavior.

(Previously: The filter bar stacked search bar above category tabs vertically at ALL viewport widths, with horizontal scroll scoped to tabs row below 640px.)

#### Scenario: Mobile layout (360px)

- GIVEN a viewport width of 360px
- WHEN the menu page renders
- THEN product cards SHALL stack in a single column
- AND the search bar SHALL display full-width above the filter slider
- AND the category pills SHALL be horizontally scrollable as a slider

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
- AND the search bar SHALL NOT share a horizontal row with the filter tabs/pills

## Constraints

- No JavaScript changes — existing data-attribute filter logic is reusable
- No HTML/PHP template changes required — CSS-only enhancement
- Slider MUST use native CSS scroll (no JS carousel library)

## Acceptance Criteria

- [ ] Menu page category slider scrolls horizontally on mobile (<640px)
- [ ] Search bar remains full-width and stationary above slider
- [ ] Category pills tappable and activate existing filter logic
- [ ] Desktop layout unchanged
