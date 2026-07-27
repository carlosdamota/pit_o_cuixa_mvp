# Delta for Admin Panel

## ADDED Requirements

### Requirement: Modern CSS Serving

The system MUST serve the modern BEM-based CSS file on all admin pages. The legacy CSS at `public/css/pages/admin.css` MUST be replaced with the modern version from `src/frontend/css/pages/admin.css`.

#### Scenario: Admin page loads modern CSS

- GIVEN an authenticated admin navigates to any admin page
- WHEN the page renders
- THEN the browser SHALL load the modern BEM CSS with design system tokens (Primary #f7e721, Secondary #d32f2f, Surface #f7f9ff, Quicksand font, 8px radius)

#### Scenario: CSS file consistency

- GIVEN the modern CSS source at `src/frontend/css/pages/admin.css`
- WHEN the build or deploy process runs
- THEN the served CSS at `public/css/pages/admin.css` SHALL be identical to the source

### Requirement: Shared JavaScript Module

The system SHOULD provide a shared `admin.js` module containing common helpers (API calls, alert display, form handling). Admin pages MUST load this module instead of duplicating inline scripts.

#### Scenario: Shared module loaded on admin pages

- GIVEN an authenticated admin on products, categories, or import/export page
- WHEN the page loads
- THEN `public/js/admin.js` SHALL be loaded via a `<script>` tag
- AND inline duplicate `api()` and `showAlert()` functions SHALL NOT be present in templates

#### Scenario: Module provides common helpers

- GIVEN the shared `admin.js` module
- WHEN any admin page needs to make an API call or show feedback
- THEN the page SHALL use the module's `api()` helper
- AND the page SHALL use the module's toast/notification helper instead of `alert()`

### Requirement: Progressive Enhancement

All admin pages MUST remain functional without JavaScript. Server-side form submissions and page navigations MUST work as fallbacks when JS is disabled.

#### Scenario: CRUD without JavaScript

- GIVEN JavaScript is disabled in the browser
- WHEN an authenticated admin submits a product or category form
- THEN the server SHALL process the form submission via standard POST
- AND the page SHALL reload with the updated data

#### Scenario: Delete without JavaScript

- GIVEN JavaScript is disabled in the browser
- WHEN an authenticated admin clicks delete on a product
- THEN the server SHALL process the deletion via standard form POST
- AND the page SHALL reload without the deleted product

## MODIFIED Requirements

### Requirement: Product CRUD

The system MUST provide an admin interface for creating, reading, updating, and deleting products. Each operation MUST validate required fields and persist to SQLite. CRUD operations SHOULD use AJAX with loading states, toast notifications for feedback, and a custom modal for delete confirmation. Product forms SHOULD display an image preview for the `image_url` field.

(Previously: CRUD operations used full page reloads with native `confirm()` for delete and no visual feedback beyond page reload)

#### Scenario: Create a new product

- GIVEN an authenticated admin on the products list page
- WHEN they fill the product form (name ES/EN, price, category, description ES/EN) and submit
- THEN the product SHALL be created in the database
- AND a success toast notification SHALL appear
- AND the products list SHALL refresh to include the new product

#### Scenario: Update an existing product

- GIVEN an authenticated admin viewing the product list
- WHEN they edit a product's price and save
- THEN the updated price SHALL persist in the database
- AND a success toast notification SHALL appear
- AND the public menu SHALL reflect the change on next load

#### Scenario: Delete a product

- GIVEN an authenticated admin viewing the product list
- WHEN they click delete and confirm via the custom modal dialog
- THEN the product SHALL be soft-deleted (`is_active = 0`) or hard-deleted
- AND a success toast notification SHALL appear
- AND the product SHALL no longer appear in the public catalog

#### Scenario: Validation error on create

- GIVEN an authenticated admin submitting a product form
- WHEN required fields (name_es, name_en, price, category_id) are empty
- THEN the system SHALL display styled inline validation errors per field
- AND SHALL NOT create the product

#### Scenario: Image preview on product form

- GIVEN an authenticated admin on the product create/edit form
- WHEN they enter or modify the `image_url` field
- THEN a thumbnail preview SHALL render next to the input
- AND the preview SHALL update as the URL changes

#### Scenario: Loading state during product save

- GIVEN an authenticated admin submitting a product form via AJAX
- WHEN the request is in progress
- THEN the submit button SHALL display a loading spinner
- AND the button SHALL be disabled until the request completes

### Requirement: Category CRUD

The system MUST allow admins to create, update, and deactivate categories. Operations SHOULD use AJAX with loading states, toast notifications, and a custom modal for deactivation confirmation.

(Previously: Category operations used full page reloads with native `confirm()` and no visual feedback)

#### Scenario: Create a category

- GIVEN an authenticated admin on the categories management page
- WHEN they submit name_es, name_en, and slug
- THEN the category SHALL be created with `is_active = 1`
- AND a success toast notification SHALL appear

#### Scenario: Deactivate a category

- GIVEN an active category with products
- WHEN the admin confirms deactivation via the custom modal dialog
- THEN the category SHALL be hidden from the public menu
- AND its products SHALL NOT appear in the public catalog
- AND a success toast notification SHALL appear
