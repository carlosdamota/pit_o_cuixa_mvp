# Admin Import/Export Specification

## Purpose

User interface for bulk CSV import and export of product catalog data. The API endpoints already exist (`POST /api/admin/import`, `GET /api/admin/export`); this spec covers the UI page that wraps them.

## Requirements

### Requirement: Import Page Accessibility

The system MUST provide an Import page accessible from the admin navigation menu. Only authenticated admins MAY access this page.

#### Scenario: Navigation to import page

- GIVEN an authenticated admin on any admin page
- WHEN they click "Import/Export" in the admin navigation
- THEN the browser SHALL navigate to the import page
- AND the page SHALL render with a file upload form and an export section

#### Scenario: Unauthenticated access to import

- GIVEN a visitor without a valid session
- WHEN they attempt to access the import page
- THEN the system SHALL redirect to `/admin/login`

### Requirement: CSV File Upload

The import page MUST provide a file input accepting `.csv` files. The system MUST validate file type and size (max 5MB) before submission.

#### Scenario: Upload valid CSV

- GIVEN an authenticated admin on the import page
- WHEN they select a valid CSV file (<=5MB, .csv extension)
- THEN the file input SHALL display the selected filename
- AND the import button SHALL become enabled

#### Scenario: Reject oversized file

- GIVEN an admin selecting a file larger than 5MB
- WHEN the file is selected
- THEN the system SHALL display an error toast: "File exceeds 5MB limit"
- AND SHALL NOT enable the import button

#### Scenario: Reject non-CSV file

- GIVEN an admin selecting a non-CSV file (e.g., .xlsx, .txt)
- WHEN the file is selected
- THEN the system SHALL display an error toast: "Only CSV files are accepted"

### Requirement: Import Preview Before Confirm

Before importing, the system SHOULD display a preview of the CSV data so the admin can verify column mapping and data correctness.

#### Scenario: Preview CSV data before import

- GIVEN a valid CSV file selected for import
- WHEN the admin clicks "Preview"
- THEN the system SHALL display a table showing the first 5 rows
- AND column headers SHALL be mapped to database fields (name_es, name_en, slug, price, category_slug, description_es, description_en, image_url, last_shop_url)
- AND the admin SHALL be able to confirm or cancel the import

### Requirement: Import Progress and Results

The system MUST display progress feedback during CSV import and show results after completion.

#### Scenario: Import with progress indicator

- GIVEN an admin has confirmed the import
- WHEN the CSV is being processed via `POST /api/admin/import`
- THEN a loading spinner SHALL be visible on the import button
- AND upon completion, a success toast SHALL show the count of imported rows

#### Scenario: Partial import with row errors

- GIVEN a CSV where some rows have missing required fields
- WHEN the import completes
- THEN valid rows SHALL be imported
- AND the result area SHALL list row-level errors (e.g., "Row 3: missing name_es")
- AND an error toast SHALL display the error summary

#### Scenario: Complete import failure

- GIVEN a CSV with an invalid format (wrong headers or empty file)
- WHEN the admin submits it
- THEN no rows SHALL be imported
- AND an error toast SHALL explain the format issue

### Requirement: CSV Export

The system MUST provide an export section on the import page with a button to download all products as CSV.

#### Scenario: Export products to CSV

- GIVEN an authenticated admin on the import/export page
- WHEN they click "Export CSV"
- THEN the browser SHALL download a CSV file via `GET /api/admin/export`
- AND columns SHALL match the import format for round-trip compatibility

#### Scenario: Export with no products

- GIVEN an authenticated admin on the import/export page
- WHEN there are no products in the database
- AND they click "Export CSV"
- THEN the system SHALL download a CSV with headers only
- AND an info toast SHALL indicate "No products to export"
