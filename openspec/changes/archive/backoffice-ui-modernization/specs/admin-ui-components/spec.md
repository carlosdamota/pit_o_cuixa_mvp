# Admin UI Components Specification

## Purpose

Shared JavaScript UI component library for the admin backoffice. Provides modal dialogs, toast notifications, loading states, image preview, and validation styling. All vanilla JS, no dependencies. Components MUST follow the design system (Primary #f7e721, Secondary #d32f2f, Surface #f7f9ff, Quicksand font, 8px radius).

## Requirements

### Requirement: Modal Dialog

The system MUST provide a custom accessible modal dialog component that replaces native `confirm()`. The modal MUST support keyboard navigation (Tab cycling, Enter to confirm, Escape to dismiss) and focus trapping.

#### Scenario: Confirm delete via modal

- GIVEN an admin clicks "Delete" on a product or category
- WHEN the modal opens
- THEN the modal SHALL display the confirmation message with Cancel and Confirm buttons
- AND focus SHALL move to the Confirm button
- AND pressing Escape SHALL close the modal without confirming

#### Scenario: Modal focus trapping

- GIVEN a modal dialog is open
- WHEN the admin presses Tab repeatedly
- THEN focus SHALL cycle through interactive elements within the modal only
- AND focus SHALL NOT move to elements behind the modal overlay

#### Scenario: Modal backdrop click

- GIVEN a modal dialog is open
- WHEN the admin clicks the backdrop overlay
- THEN the modal SHALL close without triggering the action

### Requirement: Toast Notifications

The system MUST provide a toast notification component with auto-dismiss. Toasts MUST support success (green), error (red), and info (blue) variants. Multiple toasts MAY stack vertically in the top-right corner.

#### Scenario: Success toast after CRUD

- GIVEN a product is successfully created via AJAX
- WHEN the API returns success
- THEN a green success toast SHALL appear in the top-right corner
- AND it SHALL auto-dismiss after 3 seconds

#### Scenario: Error toast on failure

- GIVEN an API call fails
- WHEN the error response is received
- THEN a red error toast SHALL appear with the error message
- AND it SHALL remain visible until manually dismissed (close button)

#### Scenario: Stacked toasts

- GIVEN a success toast is currently visible
- WHEN an info toast is triggered before the first dismisses
- THEN both toasts SHALL be visible simultaneously
- AND the newer toast SHALL appear below the existing one

### Requirement: Loading States

The system MUST display loading indicators during AJAX operations. Submit buttons MUST show a spinner and be disabled while requests are in progress.

#### Scenario: Button loading state during save

- GIVEN an admin clicks "Save" on a product form
- WHEN the AJAX request is in progress
- THEN the button SHALL display a CSS spinner icon
- AND the button SHALL be disabled
- AND the original button text SHALL be hidden

#### Scenario: Loading state resolves on error

- GIVEN a button is in loading state
- WHEN the AJAX request fails
- THEN the spinner SHALL disappear
- AND the button SHALL be re-enabled with original text
- AND an error toast SHALL appear

### Requirement: Image Preview

The system MUST provide an image preview component for `image_url` input fields on product forms. The preview SHALL update reactively as the URL changes.

#### Scenario: Preview valid image URL

- GIVEN an admin on the product create/edit form
- WHEN they enter a valid image URL and the field loses focus
- THEN a thumbnail (max 120x120px, 8px border-radius) SHALL render next to the input
- AND the image SHALL load with `alt` text from the product name

#### Scenario: Preview broken image URL

- GIVEN an admin enters an invalid or unreachable image URL
- WHEN the image fails to load
- THEN a placeholder icon SHALL display in the preview area
- AND a small error message SHALL appear below the preview

### Requirement: Validation Styling

The system MUST style form fields based on validation state. Invalid fields MUST show a red border (Secondary #d32f2f) with inline error messages. Valid fields MUST show a neutral or green border.

#### Scenario: Invalid field styling on blur

- GIVEN an admin is filling a product form
- WHEN a required field is empty and the field loses focus
- THEN the field border SHALL turn red (#d32f2f)
- AND an inline error message SHALL appear below the field (e.g., "This field is required")

#### Scenario: Valid field styling on blur

- GIVEN a required field has been filled with valid data
- WHEN the field loses focus
- THEN the field border SHALL return to neutral
- AND any previous error message SHALL be removed

#### Scenario: Real-time validation on submit

- GIVEN an admin submits a form with multiple empty required fields
- WHEN the form is submitted
- THEN ALL empty required fields SHALL be highlighted with red borders
- AND inline error messages SHALL appear below each invalid field
- AND the form SHALL NOT be submitted
