# Admin Panel Specification

## Purpose

Authenticated administration interface for managing the product catalog: login/logout, CRUD operations on products and categories, and CSV bulk import/export for catalog management.

## Requirements

### AP-001: Admin Authentication

The system MUST provide a login page at `/admin/login` requiring valid credentials. Authenticated sessions MUST be validated on every admin API request.

#### Scenario: Successful login

- GIVEN a registered admin user with username "admin"
- WHEN they submit valid credentials at `/admin/login`
- THEN the system SHALL issue a session token
- AND redirect to `/admin/dashboard`

#### Scenario: Failed login

- GIVEN any visitor at `/admin/login`
- WHEN they submit invalid credentials
- THEN the system SHALL display an error message
- AND SHALL NOT issue a session token

#### Scenario: Unauthenticated access to admin

- GIVEN a visitor without a valid session token
- WHEN they attempt to access `/admin/dashboard` or any admin API endpoint
- THEN the system SHALL redirect to `/admin/login`
- OR return HTTP 401 for API requests

### AP-002: Product CRUD

The system MUST provide an admin interface for creating, reading, updating, and deleting products. Each operation MUST validate required fields and persist to SQLite.

#### Scenario: Create a new product

- GIVEN an authenticated admin on the products list page
- WHEN they fill the product form (name ES/EN, price, category, description ES/EN) and submit
- THEN the product SHALL be created in the database
- AND the products list SHALL refresh to include the new product

#### Scenario: Update an existing product

- GIVEN an authenticated admin viewing the product list
- WHEN they edit a product's price and save
- THEN the updated price SHALL persist in the database
- AND the public menu SHALL reflect the change on next load

#### Scenario: Delete a product

- GIVEN an authenticated admin viewing the product list
- WHEN they confirm deletion of a product
- THEN the product SHALL be soft-deleted (`is_active = 0`) or hard-deleted
- AND the product SHALL no longer appear in the public catalog

#### Scenario: Validation error on create

- GIVEN an authenticated admin submitting a product form
- WHEN required fields (name_es, name_en, price, category_id) are empty
- THEN the system SHALL display inline validation errors
- AND SHALL NOT create the product

### AP-003: Category CRUD

The system MUST allow admins to create, update, and deactivate categories.

#### Scenario: Create a category

- GIVEN an authenticated admin on the categories management page
- WHEN they submit name_es, name_en, and slug
- THEN the category SHALL be created with `is_active = 1`

#### Scenario: Deactivate a category

- GIVEN an active category with products
- WHEN the admin deactivates it
- THEN the category SHALL be hidden from the public menu
- AND its products SHALL NOT appear in the public catalog

### AP-004: CSV Import

The system MUST accept a CSV file upload containing product data and import valid rows into the database.

#### Scenario: Successful CSV import

- GIVEN an authenticated admin on the import page
- WHEN they upload a valid CSV with columns: `name_es,name_en,slug,price,category_slug,description_es,description_en`
- THEN all valid rows SHALL be inserted as products
- AND a success message SHALL show the count of imported rows

#### Scenario: CSV with invalid rows

- GIVEN a CSV file where row 3 has a missing required field
- WHEN the admin uploads it
- THEN valid rows SHALL be imported
- AND the response SHALL list row-level errors (e.g., "Row 3: missing name_es")
- AND invalid rows SHALL NOT be imported

### AP-005: CSV Export

The system MUST generate a CSV download of all current products.

#### Scenario: Export products to CSV

- GIVEN an authenticated admin on the export page
- WHEN they click "Export CSV"
- THEN the browser SHALL download a CSV file containing all products
- AND columns SHALL match the import format for round-trip compatibility

### AP-006: Session Logout

The system MUST allow admins to terminate their session.

#### Scenario: Logout

- GIVEN an authenticated admin
- WHEN they click "Logout"
- THEN the session token SHALL be invalidated
- AND the browser SHALL redirect to `/admin/login`

## Contracts

### CSV Import Format

```csv
name_es,name_en,slug,price,category_slug,description_es,description_en,image_url,last_shop_url
"Pollo + Patatas","Chicken + Chips","pollo-patatas",19.50,"menu-de-pollo","Cuarto de pollo...","Quarter chicken...","https://...","https://..."
```

### Admin API Endpoints

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/api/auth/login` | No | Authenticate |
| POST | `/api/auth/logout` | Yes | End session |
| POST | `/api/admin/products` | Yes | Create product |
| PUT | `/api/admin/products/{id}` | Yes | Update product |
| DELETE | `/api/admin/products/{id}` | Yes | Delete product |
| POST | `/api/admin/categories` | Yes | Create category |
| PUT | `/api/admin/categories/{id}` | Yes | Update category |
| POST | `/api/admin/import` | Yes | CSV import |
| GET | `/api/admin/export` | Yes | CSV export |

## Constraints

- Session tokens stored in SQLite `sessions` table with expiration
- Passwords MUST be hashed with `password_hash()` (bcrypt)
- No role-based permissions in Sprint 1 (all admins have full access)
- CSV import is additive — does not delete existing products
- Max file size for CSV: 5MB

## Acceptance Criteria

- [ ] Login with valid credentials redirects to dashboard
- [ ] Invalid login shows error, no session created
- [ ] Admin pages redirect to login when unauthenticated
- [ ] Product create/update/delete works end-to-end
- [ ] Category create/deactivate works
- [ ] CSV import loads products, reports errors per row
- [ ] CSV export produces valid downloadable file
- [ ] Logout invalidates session, redirects to login
