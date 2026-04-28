# AgriFlow

AgriFlow is a multi-tenant agricultural operations platform for managing products, warehouses, harvests, inventory, and sales with multi-currency revenue reporting.

## Current Technical Baseline

- Backend: Laravel 13, PHP 8.3
- Frontend: Vue 3 SPA, Vue Router, Vue I18n, Vite
- Auth: Laravel Fortify + Sanctum (session and CSRF based SPA auth)
- Tenancy: Spatie laravel-multitenancy, domain-based tenant resolution
- Translation: Server-backed UI dictionaries for en, lg, sw
- Database: Relational schema with JSON fields (for translatable names)

## Functional Scope

### Included

- Authentication: login, logout, forgot password, set password
- Tenant-aware operations for:
	- Corporations (tenant model and domain owner)
	- Users
	- Warehouses
	- Products
	- Harvest batches
	- Inventory
	- Sales and receipts
- Dashboard with tenant-scoped revenue widgets
- Sales history with currency conversion and totals
- Translation switching (English, Luganda, Swahili)

### Removed / Not in Use

- Fiscal year module
- Manual inventory adjust endpoint and UI controls

Inventory now changes through business events only:

- Harvest creation/update increases or replaces stock
- Sale recording decreases stock

## Multi-Tenancy Model

Tenancy is resolved by request host:

- `config/multitenancy.php` uses `DomainTenantFinder`
- Tenant model is `App\\Models\\Corporation`
- Protected SPA/API routes require tenant context (`needsTenant` middleware)

Tenant ownership is explicit through `corporation_id` on core domain tables.

## Authentication Flow (SPA)

1. Client requests CSRF cookie (`/sanctum/csrf-cookie`)
2. Client posts to Fortify endpoints (`/login`, `/forgot-password`, `/reset-password`, `/logout`)
3. Client fetches current user from `/api/user`
4. API routes are protected by `auth:sanctum` and `needsTenant`

## API Snapshot

Public routes:

- `GET /api/translations/{locale}`
- `GET /api/harvest/public/{batchUuid}`

Authenticated tenant routes (sample):

- `GET /api/user`
- `GET/POST/DELETE /api/product`
- `GET/POST/DELETE /api/warehouse`
- `GET/POST/DELETE /api/harvest`
- `GET /api/inventory`
- `POST /api/inventory/sell`
- `GET /api/sales`
- `GET /api/sales/{uuid}`
- `GET/PUT /api/currencies...`
- `GET/POST/PUT/DELETE /api/users...`

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed
npm install
npm run build
```

For active development:

```bash
composer run dev
```

## Tenant Setup Checklist

1. Ensure each corporation has a unique `domain`
2. Point each tenant domain/subdomain to the application
3. Access the app through a tenant domain so `needsTenant` can resolve context

## Documentation

Detailed docs are under `docs/`:

- Technical docs index: `docs/README.md`
- Functional behavior reference: `docs/FUNCTIONAL_OVERVIEW.md`

