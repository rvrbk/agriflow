# Agriflow API Documentation

## Base Information

- **API Version**: 1.0
- **Base URL**: `/api`
- **Content-Type**: `application/json`
- **Authentication**: Bearer Token (Laravel Sanctum)
- **CORS**: Enabled (configured in `config/cors.php`)

## Authentication

### Overview
The API uses **Laravel Sanctum** for token-based authentication with the following flow:

1. User authenticates via `/login` (handled by Laravel Fortify)
2. Receives authentication token
3. Token must be included in `Authorization` header for protected endpoints
4. Token is validated by `auth:sanctum` middleware

### Authentication Header
```http
Authorization: Bearer {token}
```

### Getting Current User
```http
GET /api/user
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2026-04-13T10:00:00.000000Z",
    "updated_at": "2026-04-13T10:00:00.000000Z"
}
```

---

## Product Endpoints

### List Products
```http
GET /api/product
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
    {
        "uuid": "123e4567-e89b-12d3-a456-426614174000",
        "name": "Maize",
        "code": "MAIZE-001",
        "code_type": "SKU",
        "unit": "kg",
        "is_linked_to_harvest": false
    },
    {
        "uuid": "123e4567-e89b-12d3-a456-426614174001",
        "name": "Coffee",
        "code": "COFFEE-001",
        "code_type": "SKU",
        "unit": "kg",
        "is_linked_to_harvest": true
    }
]
```

**Query Parameters:**
- None currently supported

### Create/Update Products (Bulk)
```http
POST /api/product
Authorization: Bearer {token}
Content-Type: application/json

[
    {
        "uuid": "123e4567-e89b-12d3-a456-426614174000",
        "name": "Maize Updated",
        "code": "MAIZE-001",
        "code_type": "SKU",
        "unit": "tonne",
        "translations": {
            "en": {
                "name": "Maize Updated"
            },
            "lg": {
                "name": "Amayunsi Updated"
            }
        },
        "properties": [
            {
                "type": "NUTRITIONAL",
                "sequence": 1,
                "key": {
                    "en": "Calories",
                    "lg": "Calories"
                },
                "value": {
                    "en": "365 kcal",
                    "lg": "365 kcal"
                }
            }
        ]
    },
    {
        "name": "New Product",
        "code": "NEW-001",
        "unit": "kg"
    }
]
```

**Request Body:**
- Array of product objects
- If `uuid` is provided, updates existing product
- If `uuid` is null/missing, creates new product
- `name` (optional): Single name for all locales
- `translations` (optional): Localized names as object with locale keys
- `code` (optional): Product code
- `code_type` (optional): One of GTIN, SKU, PLU, CUSTOM
- `unit` (optional): One of KILOGRAM, GRAM, TONNE, LITRE, MILLILITRE, UNIT, BOX, BAG, CRATE, OTHER
- `properties` (optional): Array of product properties

**Response (201 Created):**
```json
[
    {
        "uuid": "123e4567-e89b-12d3-a456-426614174000",
        "name": "Maize Updated",
        "code": "MAIZE-001",
        "code_type": "SKU",
        "unit": "tonne"
    },
    {
        "uuid": "123e4567-e89b-12d3-a456-426614174002",
        "name": "New Product",
        "code": "NEW-001",
        "unit": "kg"
    }
]
```

**Errors:**
- `422 Unprocessable Entity`: Validation errors

### Delete Product
```http
DELETE /api/product/{uuid}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "message": "Product deleted."
}
```

**Error Responses:**
- `404 Not Found`: Product not found
  ```json
  {
      "message": "Product not found."
  }
  ```
- `409 Conflict`: Product is linked to harvest records
  ```json
  {
      "message": "Product is linked to harvest records and cannot be deleted."
  }
  ```

---

## Warehouse Endpoints

### List Warehouses
```http
GET /api/warehouse
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
    {
        "uuid": "87654321-e89b-12d3-a456-426614174000",
        "name": "Main Warehouse",
        "address": "123 Farm Road",
        "latitude": -0.316716,
        "longitude": 32.583885,
        "corporation_id": "123e4567-e89b-12d3-a456-426614174000",
        "corporation_name": "AgriFlow Corp"
    }
]
```

### Create Warehouse
```http
POST /api/warehouse
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "New Warehouse",
    "address": "456 Storage Lane",
    "latitude": -0.316716,
    "longitude": 32.583885,
    "corporation_id": "123e4567-e89b-12d3-a456-426614174000"
}
```

**Response (201 Created):**
```json
{
    "uuid": "87654321-e89b-12d3-a456-426614174001",
    "name": "New Warehouse",
    "address": "456 Storage Lane",
    "latitude": "-0.316716",
    "longitude": "32.583885",
    "corporation_id": "123e4567-e89b-12d3-a456-426614174000",
    "created_at": "2026-04-13T10:00:00.000000Z",
    "updated_at": "2026-04-13T10:00:00.000000Z"
}
```

### Delete Warehouse
```http
DELETE /api/warehouse/{uuid}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "message": "Warehouse deleted."
}
```

---

## Harvest Endpoints

### List Harvests (Batches)
```http
GET /api/harvest
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
    {
        "uuid": "11111111-e89b-12d3-a456-426614174000",
        "code": "HARV-001",
        "qr_code": "data:image/png;base64,iVBORw0KG...",
        "warehouse_id": "87654321-e89b-12d3-a456-426614174000",
        "warehouse_name": "Main Warehouse",
        "corporation_id": "123e4567-e89b-12d3-a456-426614174000",
        "corporation_name": "AgriFlow Corp",
        "user_id": 1,
        "user_name": "John Doe",
        "total_quantity": 500.00,
        "product_count": 2,
        "created_at": "2026-04-13T08:00:00.000000Z"
    }
]
```

### Create Harvest (Batch)
```http
POST /api/harvest
Authorization: Bearer {token}
Content-Type: application/json

{
    "code": "HARV-002",
    "warehouse_id": "87654321-e89b-12d3-a456-426614174000",
    "corporation_id": "123e4567-e89b-12d3-a456-426614174000",
    "products": [
        {
            "product_id": "123e4567-e89b-12d3-a456-426614174000",
            "quantity": 250.00
        },
        {
            "product_id": "123e4567-e89b-12d3-a456-426614174001",
            "quantity": 250.00
        }
    ]
}
```

**Response (201 Created):**
```json
{
    "uuid": "11111111-e89b-12d3-a456-426614174001",
    "code": "HARV-002",
    "qr_code": "data:image/png;base64,iVBORw0KG...",
    "warehouse_id": "87654321-e89b-12d3-a456-426614174000",
    "created_at": "2026-04-13T10:00:00.000000Z",
    "updated_at": "2026-04-13T10:00:00.000000Z"
}
```

### Delete Harvest (Batch)
```http
DELETE /api/harvest/{uuid}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "message": "Harvest deleted."
}
```

### Get Public Harvest Details
```http
GET /api/harvest/public/{batchUuid}
```

This endpoint is **public** (no authentication required).

**Response (200 OK):**
```json
{
    "uuid": "11111111-e89b-12d3-a456-426614174000",
    "code": "HARV-001",
    "qr_code": "data:image/png;base64,iVBORw0KG...",
    "warehouse": {
        "name": "Main Warehouse",
        "address": "123 Farm Road",
        "latitude": -0.316716,
        "longitude": 32.583885
    },
    "corporation": {
        "name": "AgriFlow Corp"
    },
    "items": [
        {
            "product_name": "Maize",
            "product_code": "MAIZE-001",
            "quantity": 250.00,
            "unit": "kg"
        },
        {
            "product_name": "Coffee",
            "product_code": "COFFEE-001",
            "quantity": 250.00,
            "unit": "kg"
        }
    ],
    "total_quantity": 500.00,
    "created_at": "2026-04-13T08:00:00.000000Z"
}
```

---

## Inventory Endpoints

### List Inventory
```http
GET /api/inventory
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
    {
        "uuid": "22222222-e89b-12d3-a456-426614174000",
        "product_id": "123e4567-e89b-12d3-a456-426614174000",
        "product_name": "Maize",
        "product_code": "MAIZE-001",
        "warehouse_id": "87654321-e89b-12d3-a456-426614174000",
        "warehouse_name": "Main Warehouse",
        "batch_id": "11111111-e89b-12d3-a456-426614174000",
        "batch_code": "HARV-001",
        "quantity": 250.00,
        "unit": "kg",
        "location": "Section A, Shelf 1",
        "created_at": "2026-04-13T10:00:00.000000Z",
        "updated_at": "2026-04-13T10:00:00.000000Z"
    }
]
```

### Adjust Inventory
```http
POST /api/inventory/adjust
Authorization: Bearer {token}
Content-Type: application/json

{
    "product_uuid": "123e4567-e89b-12d3-a456-426614174000",
    "warehouse_uuid": "87654321-e89b-12d3-a456-426614174000",
    "batch_uuid": "11111111-e89b-12d3-a456-426614174000",
    "quantity": 50.00,
    "location": "Section A, Shelf 2",
    "operation": "add"  // or "subtract" or "set"
}
```

**Response (200 OK):**
```json
{
    "message": "Inventory adjusted successfully.",
    "inventory": {
        "uuid": "22222222-e89b-12d3-a456-426614174000",
        "product_id": "123e4567-e89b-12d3-a456-426614174000",
        "warehouse_id": "87654321-e89b-12d3-a456-426614174000",
        "batch_id": "11111111-e89b-12d3-a456-426614174000",
        "quantity": 300.00,
        "location": "Section A, Shelf 2",
        "updated_at": "2026-04-13T12:00:00.000000Z"
    }
}
```

---

## Corporation Endpoints

### List Corporations
```http
GET /api/corporations
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
    {
        "uuid": "123e4567-e89b-12d3-a456-426614174000",
        "name": "AgriFlow Corp",
        "warehouse_count": 3,
        "user_count": 5,
        "created_at": "2026-04-13T10:00:00.000000Z"
    }
]
```

### Get Current Corporation
```http
GET /api/corporation
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "uuid": "123e4567-e89b-12d3-a456-426614174000",
    "name": "AgriFlow Corp",
    "warehouses": [
        {
            "uuid": "87654321-e89b-12d3-a456-426614174000",
            "name": "Main Warehouse"
        }
    ],
    "users": [
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "name": "John Doe",
            "email": "john@example.com"
        }
    ],
    "created_at": "2026-04-13T10:00:00.000000Z"
}
```

### Create Corporation
```http
POST /api/corporation
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "New Corporation"
}
```

**Response (201 Created):**
```json
{
    "uuid": "123e4567-e89b-12d3-a456-426614174002",
    "name": "New Corporation",
    "created_at": "2026-04-13T10:00:00.000000Z",
    "updated_at": "2026-04-13T10:00:00.000000Z"
}
```

---

## User Endpoints

### List Users
```http
GET /api/users
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
    {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "John Doe",
        "email": "john@example.com",
        "corporation_name": "AgriFlow Corp",
        "created_at": "2026-04-13T10:00:00.000000Z"
    }
]
```

### Create User
```http
POST /api/users
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "securepassword123",
    "corporation_id": "123e4567-e89b-12d3-a456-426614174000"
}
```

**Response (201 Created):**
```json
{
    "id": 2,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "name": "New User",
    "email": "newuser@example.com",
    "created_at": "2026-04-13T10:00:00.000000Z",
    "updated_at": "2026-04-13T10:00:00.000000Z"
}
```

### Delete User
```http
DELETE /api/users/{id}
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "message": "User deleted."
}
```

---

## Country Endpoints

### List Countries
```http
GET /api/countries
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
[
    {
        "id": "UG",
        "name": "Uganda"
    },
    {
        "id": "KE",
        "name": "Kenya"
    },
    {
        "id": "TZ",
        "name": "Tanzania"
    }
]
```

---

## Geocoding Endpoints

### Reverse Geocoding
```http
GET /api/geocoding/reverse?lat={latitude}&lng={longitude}
Authorization: Bearer {token}
```

**Query Parameters:**
- `lat` (required): Latitude coordinate
- `lng` (required): Longitude coordinate

**Response (200 OK):**
```json
{
    "formatted_address": "123 Farm Road, Kampala, Uganda",
    "country": "Uganda",
    "country_code": "UG",
    "city": "Kampala",
    "street": "Farm Road",
    "postcode": null,
    "latitude": -0.316716,
    "longitude": 32.583885
}
```

---

## Translation Endpoints

### Get Translations
```http
GET /api/translations/{locale}
```

This endpoint is **public** (no authentication required).

**Path Parameters:**
- `locale` (required): One of `en`, `lg`, `sw`

**Response (200 OK):**
```json
{
    "header": {
        "subtitle": "Agricultural Flow Management"
    },
    "menu": {
        "dashboard": "Dashboard",
        "products": "Products",
        "harvests": "Harvests",
        "inventory": "Inventory",
        "warehouses": "Warehouses",
        "corporations": "Corporations",
        "users": "Users",
        "main": "Main",
        "management": "Management",
        "primary_aria": "Primary navigation",
        "toggle_menu": "Toggle menu"
    },
    "language": "Language",
    "languages": {
        "en": "English",
        "lg": "Luganda",
        "sw": "Swahili"
    },
    "auth": {
        "login": "Login",
        "logout": "Logout",
        "email": "Email",
        "password": "Password",
        "remember_me": "Remember me",
        "forgot_password": "Forgot password?",
        "register": "Register"
    },
    "common": {
        "save": "Save",
        "cancel": "Cancel",
        "delete": "Delete",
        "edit": "Edit",
        "add": "Add",
        "search": "Search",
        "loading": "Loading...",
        "no_results": "No results found",
        "confirm_delete": "Are you sure you want to delete this item?",
        "actions": "Actions"
    }
}
```

---

## Error Responses

### Standard Error Format
All error responses follow this format:
```json
{
    "message": "Error message explaining what went wrong",
    "errors": {  // Only present for validation errors
        "field_name": ["Error message for this field"]
    }
}
```

### Common HTTP Status Codes

| Code | Description | Example Response |
|------|-------------|------------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Missing or invalid authentication token |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Business rule violation (e.g., cannot delete linked product) |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Unexpected server error |

### Validation Error (422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email field must be a valid email address."]
    }
}
```

### Authentication Error (401)
```json
{
    "message": "Unauthenticated."
}
```

### Forbidden Error (403)
```json
{
    "message": "Unauthorized. User does not have permission."
}
```

### Not Found Error (404)
```json
{
    "message": "Resource not found."
}
```

---

## Request Headers

### Required Headers
- `Content-Type: application/json` (for POST, PUT, PATCH requests)
- `Accept: application/json`
- `Authorization: Bearer {token}` (for authenticated endpoints)

### Optional Headers
- `X-Requested-With: XMLHttpRequest`
- `X-CSRF-TOKEN: {token}` (for web routes, not API)

---

## Response Headers

### Standard Headers
- `Content-Type: application/json`
- `Cache-Control: no-cache, private`

### Rate Limiting (if implemented)
- `X-RateLimit-Limit: 60`
- `X-RateLimit-Remaining: 55`
- `Retry-After: 60` (when rate limited)

---

## Rate Limiting

Currently, the application does not have explicit rate limiting configured. This can be added in `app/Providers/RouteServiceProvider.php`:

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60);
});
```

---

## Versioning

The API currently has no explicit versioning. All endpoints are under `/api/`. For future versioning:

```
/api/v1/ - Version 1 endpoints
/api/v2/ - Version 2 endpoints (when needed)
```

---

## CORS Configuration

CORS is configured in `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],  // Development only
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

For production, restrict origins to specific domains.

---

## Testing the API

### Using Postman/Insomnia
Import the provided Postman collection or create requests manually:

1. Set base URL: `http://localhost:8000/api` (development)
2. Add authentication token to requests
3. Use the endpoint examples above

### Using cURL
```bash
# Get products
curl -X GET http://localhost:8000/api/product \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Create product
curl -X POST http://localhost:8000/api/product \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Product"}'
```

### Using PHPUnit
The project includes PHPUnit tests in the `tests/` directory:

```bash
composer test
```

---

## API Security Best Practices

1. **Always use HTTPS** in production
2. **Validate all inputs** on both client and server
3. **Never trust client-side data** - always validate server-side
4. **Use prepared statements** (Laravel Eloquent does this automatically)
5. **Hash sensitive data** (passwords are hashed by Laravel)
6. **Limit token permissions** in Sanctum if using abilities
7. **Rotate tokens** if compromised
8. **Rate limit** sensitive endpoints (not currently configured)
9. **Log security events** (failed logins, deletions, etc.)
10. **Regularly audit** user permissions

---

## Webhooks (Future)

The API currently does not support webhooks, but they could be added for:
- Inventory changes
- Harvest completions
- User actions

Example structure:
```
POST {webhook_url}
Content-Type: application/json
X-Agriflow-Signature: {signature}

{
    "event": "inventory.adjusted",
    "data": { ... },
    "timestamp": "2026-04-13T10:00:00.000000Z"
}
```
