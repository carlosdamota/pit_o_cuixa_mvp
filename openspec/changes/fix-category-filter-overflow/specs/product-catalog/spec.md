# Delta for Product Catalog

## ADDED Requirements

### Requirement: Page-Level Horizontal Scroll Prevention

The menu page MUST NOT exhibit horizontal scroll at the page level. All horizontal overflow from category tabs MUST be contained within the tabs row via internal scrolling. The tabs container MUST NOT expand beyond its parent width.

This requirement makes explicit the constraint that PC-005 implies: horizontal scroll belongs to the tabs row only, never to the page body.

#### Scenario: Mobile viewport with many categories

- GIVEN a viewport width below 640px and more category tabs than fit the available width
- WHEN the menu page renders
- THEN only the tabs row SHALL scroll horizontally within its container
- AND the page body SHALL NOT have horizontal overflow

#### Scenario: Desktop viewport (640px+)

- GIVEN a viewport width of 640px or above
- WHEN the menu page renders
- THEN tabs SHALL be centered below the search bar
- AND no horizontal scroll SHALL appear at any level

#### Scenario: Long category names

- GIVEN category tab labels that collectively exceed the viewport width
- WHEN the menu page renders on any viewport
- THEN the tabs row SHALL scroll horizontally to reveal all tabs
- AND the search bar SHALL remain stationary and full-width
