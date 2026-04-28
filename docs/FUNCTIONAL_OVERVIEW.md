# AgriFlow - Functional Overview

## 1. Purpose

AgriFlow is a multi-tenant operations app for agricultural businesses.
Each tenant manages its own users, warehouses, products, harvests, inventory, and sales.

The application emphasizes inventory integrity:

- Harvest actions create or increase inventory.
- Sale actions decrease inventory.
- Manual inventory adjustment is not part of the active flow.

## 2. Current Technical Architecture

| Layer | Current Implementation |
|-------|------------------------|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Vue 3 SPA, Vue Router, Vite |
| Auth | Fortify + Sanctum (session + CSRF) |
| Tenancy | Spatie laravel-multitenancy (domain finder) |
| Localization | Server-provided dictionaries (en, lg, sw) |
| Data | Relational schema with JSON translation fields |

## 3. Multi-Tenant Model

Tenancy is host/domain based.
The request host is mapped to a tenant corporation and bound as current tenant.

### Tenant Resolution

- Finder: `Spatie\\Multitenancy\\TenantFinder\\DomainTenantFinder`
- Tenant model: `App\\Models\\Corporation`
- Tenant route guard: `needsTenant` middleware

### Tenant-owned Domain Entities

The following are tenant-owned through `corporation_id`:

- users
- warehouses
- products
- batches (harvests)
- inventories
- sales

## 4. Authentication and Access

This is a cookie/session SPA auth model.
It is not bearer token API auth for frontend requests.

### Login Session Flow

1. Client gets CSRF cookie from `/sanctum/csrf-cookie`.
2. Client posts credentials to `/login`.
3. Client bootstraps user from `/api/user`.
4. All tenant-protected API routes require both:
   - `auth:sanctum`
   - `needsTenant`

### Password Flows

- Forgot password: `/forgot-password` page posts to Fortify endpoint.
- Reset/set password: `/set-password?token=...&email=...` and posts to `/reset-password`.
- Reset email links are customized to SPA set-password route.

## 5. Functional Modules

### 5.1 Dashboard

Provides tenant-scoped operational overview.

- Warehouse inventory cards
- Sales/revenue widget
- Multi-currency total display
- Revenue is derived from `/api/sales` (tenant-scoped)

### 5.2 Products

- Tenant-scoped product list/create/edit/delete
- Multilingual product names via JSON translations
- Delete blocked if product is linked to harvest/inventory usage

### 5.3 Warehouses

- Tenant-scoped warehouse management
- Geocoding helper endpoint for location details
- Delete blocked when linked to inventory rows

### 5.4 Harvests

- Tenant-scoped harvest batch management
- QR code and public trace route for harvest batch details
- Harvest writes upsert inventory rows for same tenant

### 5.5 Inventory

- Tenant-scoped inventory list
- Read-only quantity display in inventory module
- Stock decreases only through sell action

### 5.6 Sales

- Sell from available inventory by batch
- Creates sale record and decrements inventory
- Sales history and receipt views are tenant-scoped
- Revenue widgets are tenant-scoped

### 5.7 Users

- Tenant-scoped user management
- Invite/onboarding with set-password flow
- Self-delete prevention

### 5.8 Corporation

- Current tenant corporation retrieval and update
- Corporation domain is used for tenant resolution

## 6. Active API Surface (Functional)

### Public

- `GET /api/translations/{locale}`
- `GET /api/harvest/public/{batchUuid}`

### Authenticated + Tenant Required

- `GET /api/user`
- Products: `GET/POST/DELETE /api/product`
- Warehouses: `GET/POST/DELETE /api/warehouse`
- Harvests: `GET/POST/DELETE /api/harvest`
- Inventory: `GET /api/inventory`, `POST /api/inventory/sell`
- Sales: `GET /api/sales`, `GET /api/sales/{uuid}`
- Currencies: `GET /api/currencies`, `GET /api/currencies/rate/{from}/{to}`, `POST /api/currencies/convert`, `PUT /api/currencies/{code}`
- Users: `GET/POST/PUT/DELETE /api/users...`, `GET /api/users/corporations`

## 7. Revenue Logic

Revenue displays in Dashboard and Sales History are computed from tenant-filtered sales.

Key points:

- Sales rows include currency and total value.
- Frontend converts totals based on exchange rates from `/api/currencies`.
- Display currency is a UI choice; source sales data remains per-sale currency.

## 8. Translation Model

UI translations are loaded from backend dictionaries.

Supported locales:

- `en`
- `lg`
- `sw`

Product names and some entity labels also support translated JSON fields.

## 9. Removed / Deprecated Functional Areas

The following are intentionally removed from active behavior:

- Fiscal year module (API, model, UI flow)
- Manual inventory adjust endpoint and UI controls

## 10. Operational Notes

### Local Build / Runtime

- Build frontend: `npm run build`
- Clear Laravel caches after core config changes: `php artisan optimize:clear`

### Tenant Onboarding Minimum

1. Create corporation.
2. Assign unique domain to corporation.
3. Route domain to app host.
4. Access app through that domain.

---

Last updated: April 28, 2026
