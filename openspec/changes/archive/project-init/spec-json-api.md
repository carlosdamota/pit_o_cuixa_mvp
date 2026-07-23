# JSON API Specification

## Purpose

RESTful JSON API contract serving as the integration layer between the PHP/SQLite backend and the HTML/JS frontend. Provides public read endpoints for the product catalog and authenticated write endpoints for admin operations.

## Requirements

### JA-001: Public Read Endpoints

The system MUST expose JSON endpoints for reading products, categories, and the grouped menu. These endpoints SHALL require no authentication.

#### Scenario: List all products

- GIVEN the database contains 45 active products
- WHEN a client sends `GET /api/products`
- THEN the response SHALL be HTTP 200 with `{ "products": [...] }` containing all 45 products
- AND each product object SHALL include: id, name_es, name_en, slug, description_es, description_en, price, category_id, image_url, last_shop_url, is_active, sort_order

#### Scenario: Filter products by category

- GIVEN the database contains products in category_id = 3
- WHEN a client sends `GET /api/products?id_category=3`
- THEN the response SHALL contain only products where `category_id = 3`

#### Scenario: Get single product by slug

- GIVEN a product exists with slug "pollo-patatas-alioli"
- WHEN a client sends `GET /api/products/pollo-patatas-alioli`
- THEN the response SHALL be HTTP 200 with `{ "product": {...} }`

#### Scenario: Product not found

- GIVEN no product exists with slug "nonexistent"
- WHEN a client sends `GET /api/products/nonexistent`
- THEN the response SHALL be HTTP 404 with `{ "error": true, "message": "Product not found", "code": 404 }`

#### Scenario: List categories

- GIVEN 11 active categories exist
- WHEN a client sends `GET /api/categories`
- THEN the response SHALL contain all 11 categories ordered by `sort_order`

#### Scenario: Get grouped menu

- WHEN a client sends `GET /api/menu`
- THEN the response SHALL return products grouped by category: `{ "groups": [{ "category": {...}, "products": [...] }, ...] }`
- AND groups SHALL be ordered by category `sort_order`
- AND products within each group SHALL be ordered by product `sort_order`

### JA-002: Admin Write Endpoints (Authenticated)

The system MUST expose JSON endpoints for CRUD operations that require a valid session token.

#### Scenario: Create product with valid auth

- GIVEN a valid session token in the `Authorization` header
- WHEN a client sends `POST /api/admin/products` with valid product JSON
- THEN the response SHALL be HTTP 201 with `{ "product": {...} }`
- AND the product SHALL persist in SQLite

#### Scenario: Create product without auth

- GIVEN no session token
- WHEN a client sends `POST /api/admin/products`
- THEN the response SHALL be HTTP 401 with `{ "error": true, "message": "Unauthorized", "code": 401 }`

#### Scenario: Update product

- GIVEN a valid session and existing product id = 5
- WHEN a client sends `PUT /api/admin/products/5` with updated fields
- THEN the response SHALL be HTTP 200 with the updated product
- AND `updated_at` SHALL reflect the current timestamp

#### Scenario: Delete product

- GIVEN a valid session and existing product id = 5
- WHEN a client sends `DELETE /api/admin/products/5`
- THEN the response SHALL be HTTP 200 with `{ "success": true }`
- AND the product SHALL no longer appear in public endpoints

### JA-003: Authentication Endpoints

The system MUST provide login/logout endpoints that manage session tokens.

#### Scenario: Login with valid credentials

- WHEN a client sends `POST /api/auth/login` with `{ "username": "admin", "password": "correct" }`
- THEN the response SHALL be HTTP 200 with `{ "token": "...", "user": { "id": 1, "username": "admin", "display_name": "..." } }`

#### Scenario: Login with invalid credentials

- WHEN a client sends `POST /api/auth/login` with wrong password
- THEN the response SHALL be HTTP 401 with `{ "error": true, "message": "Invalid credentials", "code": 401 }`

#### Scenario: Logout

- GIVEN a valid session token
- WHEN a client sends `POST /api/auth/logout`
- THEN the token SHALL be invalidated in the sessions table
- AND the response SHALL be HTTP 200 with `{ "success": true }`

### JA-004: CSV Import/Export Endpoints

The system MUST provide endpoints for bulk CSV operations (see admin-panel spec for details).

#### Scenario: CSV import

- GIVEN a valid session and a multipart CSV upload
- WHEN `POST /api/admin/import` is called
- THEN the response SHALL include `{ "imported": N, "errors": [...] }`

#### Scenario: CSV export

- GIVEN a valid session
- WHEN `GET /api/admin/export` is called
- THEN the response SHALL have `Content-Type: text/csv` and `Content-Disposition: attachment`

### JA-005: Consistent Error Format

All error responses MUST follow a uniform JSON structure.

#### Scenario: Error response format

- WHEN any API endpoint encounters an error
- THEN the response body SHALL be `{ "error": true, "message": "...", "code": <HTTP_STATUS> }`
- AND the HTTP status code SHALL match the `code` field

## Contracts

### Response Schemas

```json
// Product
{
  "id": "integer",
  "name_es": "string",
  "name_en": "string",
  "slug": "string",
  "description_es": "string | null",
  "description_en": "string | null",
  "price": "number",
  "category_id": "integer",
  "category_slug": "string",
  "image_url": "string | null",
  "last_shop_url": "string",
  "is_active": "boolean",
  "sort_order": "integer"
}

// Category
{
  "id": "integer",
  "name_es": "string",
  "name_en": "string",
  "slug": "string",
  "description_es": "string | null",
  "description_en": "string | null",
  "sort_order": "integer",
  "is_active": "boolean"
}

// Error
{
  "error": "boolean (true)",
  "message": "string",
  "code": "integer (HTTP status)"
}
```

### Endpoint Summary

| Method | Path | Auth | Response |
|--------|------|------|----------|
| GET | `/api/products` | No | `{ products: Product[] }` |
| GET | `/api/products?id_category={id}` | No | `{ products: Product[] }` |
| GET | `/api/products/{slug}` | No | `{ product: Product }` |
| GET | `/api/categories` | No | `{ categories: Category[] }` |
| GET | `/api/menu` | No | `{ groups: Group[] }` |
| POST | `/api/auth/login` | No | `{ token, user }` |
| POST | `/api/auth/logout` | Yes | `{ success: true }` |
| POST | `/api/admin/products` | Yes | `{ product: Product }` |
| PUT | `/api/admin/products/{id}` | Yes | `{ product: Product }` |
| DELETE | `/api/admin/products/{id}` | Yes | `{ success: true }` |
| POST | `/api/admin/import` | Yes | `{ imported, errors[] }` |
| GET | `/api/admin/export` | Yes | CSV file |

### Authentication

- Token passed via `Authorization: Bearer <token>` header
- Tokens stored in `sessions` table with `expires_at` timestamp
- Expired tokens SHALL be treated as invalid

## Constraints

- All responses MUST have `Content-Type: application/json; charset=utf-8` (except CSV export)
- Prices are REAL in SQLite, serialized as JSON numbers (e.g., `19.50`)
- Boolean fields (`is_active`, `is_featured`) serialized as JSON booleans, not integers
- No CORS configuration needed in Sprint 1 (same-origin)
- Request body limit: 5MB (for CSV import)

## Acceptance Criteria

- [ ] All 6 public GET endpoints return correct JSON
- [ ] Category filter query parameter works
- [ ] 404 responses use consistent error format
- [ ] Auth endpoints issue/invalidate tokens correctly
- [ ] Admin CRUD endpoints reject unauthenticated requests (401)
- [ ] Admin CRUD endpoints persist changes to SQLite
- [ ] CSV import returns per-row error reporting
- [ ] CSV export returns downloadable file with correct headers
- [ ] All responses include correct Content-Type header
