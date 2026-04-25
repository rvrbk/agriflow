# AgriFlow - Functional Overview

## Table of Contents

1. [Application Overview](#application-overview)
2. [Authentication & Access Control](#authentication--access-control)
3. [Core Entities](#core-entities)
4. [Feature Modules](#feature-modules)
5. [Data Flow](#data-flow)
6. [Currency & Exchange Rate System](#currency--exchange-rate-system)
7. [User Interface Structure](#user-interface-structure)
8. [Translation System](#translation-system)

---

## 1. Application Overview

### Purpose
AgriFlow is a warehouse and inventory management application designed for agricultural businesses. It tracks harvests, inventory across multiple warehouses, and sales transactions with multi-currency support.

### Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 10.x, PHP 8.x |
| Database | PostgreSQL (with JSON columns for translations) |
| Authentication | Laravel Sanctum (Bearer Token) |
| Frontend | Vue 3, Tailwind CSS, Vite |
| State Management | Pinia (auth state) |
| Routing | Vue Router |

### System Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Vue 3 Client   │────▶│   Laravel API   │────▶│   PostgreSQL    │
│   (SPA)          │     │   (RESTful)     │     │   Database      │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │                          │
         │              ┌───────────┐
         │              │  Sanctum  │
         │              │  Auth     │
         │              └───────────┘
         │
    ┌─────────────────┐
    │   Translations  │ (en, lg, sw)
    └─────────────────┘
```

---

## 2. Authentication & Access Control

### User Types
- **Standard User**: Can view, create, edit, delete own data
- **Administrator**: Full access including user management

### Authentication Flow

```
1. Login (POST /api/login) → Returns Sanctum Token
2. Token stored in client (localStorage or memory)
3. API requests include: Authorization: Bearer <token>
4. Laravel Sanctum validates token via auth:sanctum middleware
5. User data available via GET /api/user
```

### Protected Routes
All routes under `routes/api.php` middleware('auth:sanctum') group require valid Bearer token.

### Session Management
- Token-based authentication (no session cookies)
- Logout: Destroys token client-side
- Token expiration: Handled by Sanctum defaults

---

## 3. Core Entities

### Entity Relationship Diagram

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│   User       │       │ Corporation  │       │  Warehouse   │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ id            │       │ id            │       │ id            │
│ name          │       │ name          │       │ name          │
│ email         │       │ address       │       │ corporation_id│
│ password      │◄──────┤ ...            │◄──────┤ address       │
└──────────────┘       └──────────────┘       │ latitude      │
                                              │ longitude     │
                                              └──────────────┘
                                                     │
                                                     ▼
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│  Product     │       │   Batch      │       │  Inventory   │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ id            │       │ id            │       │ id            │
│ uuid          │       │ uuid          │       │ batch_id      │
│ name (JSON)   │◄──────┤ product_id    │◄──────┤ warehouse_id  │
│ code          │       │ warehouse_id  │       │ quantity      │
│ code_type     │       │ quantity      │       │ location      │
│ unit          │       │ harvested_on  │       │ created_at    │
└──────────────┘       │ expires_on    │       │ updated_at    │
                       │ quality       │       └──────────────┘
                       │ qr_code       │
                       └──────────────┘
                              │
                              ▼
                       ┌──────────────┐
                       │    Sale      │
                       ├──────────────┤
                       │ id            │
                       │ uuid          │
                       │ batch_id      │
                       │ product_id    │
                       │ warehouse_id  │
                       │ quantity      │
                       │ unit_price    │
                       │ total_value   │
                       │ currency      │
                       │ notes (JSON)  │
                       └──────────────┘
```

### Data Models

#### User
- Standard Laravel user model with sanctum token support
- Fields: id, name, email, password, remember_token

#### Corporation
- Represents a business entity
- Fields: id, uuid, name, address, city, state, country, latitude, longitude

#### Warehouse
- Storage location for inventory
- Belongs to a Corporation
- Fields: id, uuid, name, corporation_id, capacity, address, city, state, country, latitude, longitude

#### Product
- Agricultural products (e.g., Apples, Tomatoes)
- Fields: id, uuid, name (JSON: {en: "Apples", lg: "...", sw: "..."}), code, code_type, unit

#### Batch
- Harvest batch with unique tracking
- Generates QR codes for inventory management
- Fields: id, uuid, product_id, warehouse_id, quantity, harvested_on, expires_on, quality, qr_code, location

#### Inventory
- Current stock levels grouped by batch and warehouse
- Fields: id, uuid, batch_id, warehouse_id, product_id, quantity, location, created_at, updated_at

#### Sale
- Sales transaction records
- Tracks revenue in original currency with conversion support
- Fields: id, uuid, batch_id, product_id, warehouse_id, quantity, unit_price, total_value, currency, notes (JSON)

#### Currency
- Exchange rate management
- Fields: id, code (3-letter ISO), name, symbol, exchange_rate (relative to USD), base_currency

---

## 4. Feature Modules

### 4.1 Dashboard

**Purpose**: Overview of warehouse inventory and sales performance

**Components**:
- Header with page title "Dashboard"
- Quick action buttons:
  - Sell Inventory (→ /sales)
  - View Sales History (→ /sales-history)
- Revenue Summary widget:
  - Total revenue across all sales
  - Currency toggle (USD/UGX)
  - Breakdown by original currency
  - Quick link to Sales History
- Search functionality
- Warehouse cards grid showing:
  - Warehouse name
  - Product count
  - Total quantity
  - Product breakdown table

**API Endpoints Used**:
- `GET /api/warehouse` - List all warehouses
- `GET /api/inventory` - List all inventory
- `GET /api/sales` - List all sales for revenue calculation
- `GET /api/currencies` - List currencies for conversion

### 4.2 Inventory Management

**Purpose**: View and manage stock levels across warehouses

**Components**:
- Inventory list with filtering by warehouse
- Product grouping with batch information
- Quantity adjustment (add/subtract)

**API Endpoints**:
- `GET /api/inventory` - List inventory with warehouse, product, batch details
- `POST /api/inventory/adjust` - Adjust inventory quantity

### 4.3 Harvest Management

**Purpose**: Track harvest batches with QR code identification

**Components**:
- Harvest list with search
- Batch details (product, warehouse, quantity, dates)
- QR code generation and display
- Public harvest view (shareable URL)

**API Endpoints**:
- `GET /api/harvest` - List all harvests
- `POST /api/harvest` - Create new harvest
- `GET /api/harvest/public/{batchUuid}` - Public view of harvest details
- `DELETE /api/harvest/{uuid}` - Delete harvest

### 4.4 Product Management

**Purpose**: Manage product catalog with multi-language support

**Components**:
- Product list with search
- Add/edit product form
- Delete product (prevented if linked to harvests)

**API Endpoints**:
- `GET /api/product` - List products
- `POST /api/product` - Create product
- `DELETE /api/product/{uuid}` - Delete product

**Code Types**: none, barcode, qr
**Units**: kg, g, l, ml, pcs

### 4.5 Warehouse Management

**Purpose**: Manage storage locations

**Components**:
- Warehouse list with search
- Add/edit warehouse form
- Map-based location selection
- Geocoding integration

**API Endpoints**:
- `GET /api/warehouse` - List warehouses
- `POST /api/warehouse` - Create warehouse
- `DELETE /api/warehouse/{uuid}` - Delete warehouse (prevented if linked to inventory)

### 4.6 Sales Module

**Purpose**: Record sales transactions and track revenue

**Components**:

#### Sell Inventory View (`/sales`)
- Inventory list with search
- For each inventory item:
  - Amount input (quantity to sell)
  - Price per unit input
  - Currency dropdown (default: UGX)
  - Sell button
- Validation:
  - Amount > 0
  - Amount ≤ available quantity
  - Price ≥ 0
- On success: Creates sale record, reduces inventory

#### Sales History View (`/sales-history`)
- Searchable list of all sales
- For each sale:
  - Product, batch, warehouse, date
  - Quantity, unit price, total value
  - Original currency display
  - Converted amount display (based on selected view currency)
- Revenue Summary panel:
  - Total revenue (in selected currency)
  - Currency toggle (USD/UGX)
  - Exchange rate display
  - Breakdown by original currency

**API Endpoints**:
- `GET /api/inventory` - List sellable inventory
- `POST /api/inventory/sell` - Record sale
  - Body: { batch_uuid, amount, price, currency }
- `GET /api/sales` - List all sales
- `GET /api/currencies` - Get exchange rates

### 4.7 User Management

**Purpose**: Administrator function to manage platform users

**Components**:
- User list with search
- Add user form (sends invitation email)
- Edit user
- Delete user (prevented for current user)

**API Endpoints**:
- `GET /api/users` - List users
- `POST /api/users` - Create user
- `DELETE /api/users/{id}` - Delete user

### 4.8 Corporation Management

**Purpose**: Manage business entities

**Components**:
- Corporation list
- Add/edit corporation form
- Map-based location selection

**API Endpoints**:
- `GET /api/corporations` - List corporations
- `GET /api/corporation` - Get current corporation
- `POST /api/corporation` - Create/update corporation

---

## 5. Data Flow

### RESTful API Design

All API endpoints follow REST conventions:

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | /api/resource | List all resources |
| POST | /api/resource | Create new resource |
| GET | /api/resource/{uuid} | Get specific resource |
| PUT/PATCH | /api/resource/{uuid} | Update resource |
| DELETE | /api/resource/{uuid} | Delete resource |

### Response Structure

**Success Response (200)**:
```json
{
  "data": [...]
}
```

**Success with single item**:
```json
{
  "id": 1,
  "uuid": "...",
  "name": "..."
}
```

**Error Response (4xx/5xx)**:
```json
{
  "message": "Error description",
  "errors": { "field": ["Error details"] }
}
```

**Validation Error (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": { "field": ["The field is required."] }
}
```

### Authentication Flow

```
Client                                      Server
  │                                            │
  │── POST /api/login {email, password} ──────▶│
  │                                            │
  │◀── 200 {user, token} ─────────────────────│
  │                                            │
  │── GET /api/user Authorization: Bearer token ▶│
  │                                            │
  │◀── 200 {user data} ───────────────────────│
```

### Sales Flow

```
1. User navigates to /sales
2. GET /api/inventory → Returns sellable inventory items
3. User selects item, enters amount, price, currency
4. POST /api/inventory/sell → Creates sale record, reduces inventory
   - Validates amount ≤ inventory.quantity
   - Creates Sale record with price, currency
   - Updates inventory quantity (decreases by amount)
5. GET /api/sales → Returns all sales for history view
6. User views /sales-history with total revenue
```

---

## 6. Currency & Exchange Rate System

### Architecture

The system uses a **base currency (USD)** approach:
- All currencies store an `exchange_rate` relative to USD
- USD always has exchange_rate = 1.0
- To convert: amount_in_A * (rate_B / rate_A) = amount_in_B

### Database

**currencies table**:
```
┌─────┬──────┬──────────┬────────┬──────────────┬──────────────┐
│ id  │ code │  name    │ symbol │ exchange_rate │ base_currency │
├─────┼──────┼──────────┼────────┼──────────────┼──────────────┤
│ 1   │ USD  │ US Dollar │ $      │ 1.000000     │ USD           │
│ 2   │ UGX  │ UGX      │ USh    │ 3700.000000  │ USD           │
└─────┴──────┴──────────┴────────┴──────────────┴──────────────┘
```

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/currencies | GET | List all currencies with rates |
| /api/currencies/rate/{from}/{to} | GET | Get conversion rate between currencies |
| /api/currencies/convert | POST | Convert amount from one currency to another |
| /api/currencies/{code} | PUT | Update exchange rate for currency |

### Conversion Logic

```javascript
// Given: USD rate = 1, UGX rate = 3700
// Convert 100 USD to UGX: 100 * (3700/1) = 370,000 UGX
// Convert 370000 UGX to USD: 370000 * (1/3700) = 100 USD

function convert(amount, fromCurrency, toCurrency) {
    const fromRate = currencies.find(c => c.code === fromCurrency).exchange_rate;
    const toRate = currencies.find(c => c.code === toCurrency).exchange_rate;
    const rate = toRate / fromRate;
    return amount * rate;
}
```

### Default Currency

- **Sell Inventory**: Defaults to UGX
- **Dashboard Revenue**: Defaults to UGX
- **Sales History**: Defaults to UGX
- Users can toggle between available currencies

### Exchange Rate Management

- **Initial rates**: Seeded via `CurrencySeeder.php`
- **Current rate**: 1 USD = 3700 UGX (as of April 2026)
- **Update**: Admin can update via PUT /api/currencies/{code}
- **Persistence**: Rates stored in database, loaded on app startup

---

## 7. User Interface Structure

### Page Layout

```
┌─────────────────────────────────────────────────────────────┐
│  HEADER: AgriFlow Logo    +    Menu Toggle    +    Logout   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  SIDE MENU (when open):                                    │
│  ┌─────────────────┐                                      │
│  │ Main            │                                      │
│  │   Dashboard    │                                      │
│  │   Inventory    │                                      │
│  │   Harvests     │                                      │
│  │   Products     │                                      │
│  │   ...          │                                      │
│  │                 │                                      │
│  │ Management     │                                      │
│  │   Corporations │                                      │
│  │   Warehouses   │                                      │
│  │   Users        │                                      │
│  │   Sales        │                                      │
│  │   Sales History│                                      │
│  └─────────────────┘                                      │
│                                                             │
│  MAIN CONTENT AREA                                        │
│  ┌─────────────────────────────────────────────────────┐ │
│  │  Page-specific content (dashboard, inventory, etc.)  │ │
│  └─────────────────────────────────────────────────────┘ │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Menu Visibility
- Hidden on: Login page, Set Password page
- Hidden when: User is not authenticated
- Toggled by: Hamburger menu button in header

### Design System

**Colors**:
- Primary: `#2f6e4a` (green)
- Secondary: `#5d7c69` (muted green)
- Background: `#f7f9f7` (light gray-green)
- Border: `#ccd8c7` / `#dde5d7`
- Text Primary: `#1f2a1d` (dark green)
- Text Secondary: `#4e5f4f` (medium green)
- Text Muted: `#6b826b`
- White: `#ffffff` / `#fff`
- Error: Red (#ef4444, etc.)

**Typography**:
- Font: System default (Inter-like)
- Sizes: text-sm (0.875rem), text-base, text-lg, text-xl, text-2xl
- Weights: font-normal, font-medium, font-semibold, font-bold

**Spacing**:
- Consistent use of Tailwind spacing scale (p-4, p-6, mb-4, gap-3, etc.)
- Card padding: p-4 (1rem)
- Section padding: p-6 (1.5rem)

---

## 8. Translation System

### Supported Languages

| Code | Language | Native Name |
|------|----------|-------------|
| en | English | English |
| lg | Luganda | Oluganda |
| sw | Swahili | Kiswahili |

### Translation Files

All translations stored in `lang/{locale}/ui.php` as nested arrays:

```php
return [
    'dashboard' => [
        'title' => 'Dashboard',
        'subtitle' => 'See what is stored...',
        'fields' => [...]
    ],
    'sales' => [...],
    // ...
];
```

### Translation Keys

All UI text uses the `t()` function:

```vue
<h1>{{ t('dashboard.title') }}</h1>
<p>{{ t('sales.messages.sell_success') }}</p>
```

### Available Translation Sections

| Section | Description |
|---------|-------------|
| header | App header, logout |
| menu | Navigation menu |
| dashboard | Dashboard page |
| inventory | Inventory management |
| harvests | Harvest management |
| products | Product catalog |
| warehouses | Warehouse management |
| users | User management |
| corporations | Corporation management |
| sales | Sales (sell inventory) |
| sales_history | Sales history view |
| login | Login page |
| set_password | Password setup |

### Adding Translations

1. Add key to `lang/en/ui.php`
2. Add corresponding translations to `lang/lg/ui.php` and `lang/sw/ui.php`
3. Use `t('section.key.subkey')` in Vue components

---

## Appendix: Quick Reference

### Common API Endpoints

| Feature | Endpoint | Method |
|---------|----------|--------|
| Login | /api/login | POST |
| Current User | /api/user | GET |
| Logout | /api/logout | POST |
| Products | /api/product | GET/POST |
| Warehouses | /api/warehouse | GET/POST |
| Inventory | /api/inventory | GET |
| Adjust Inventory | /api/inventory/adjust | POST |
| Sell Inventory | /api/inventory/sell | POST |
| Sales History | /api/sales | GET |
| Harvests | /api/harvest | GET/POST |
| Public Harvest | /api/harvest/public/{uuid} | GET |
| Users | /api/users | GET/POST |
| Currencies | /api/currencies | GET |
| Convert Currency | /api/currencies/convert | POST |
| Countries | /api/countries | GET |
| Corporations | /api/corporations | GET/POST |
| Translations | /api/translations/{locale} | GET |

### Vue Routes

| Path | Component | Description |
|------|-----------|-------------|
| / | DashboardView | Main dashboard |
| /inventory | InventoryView | Inventory list |
| /harvests | HarvestsView | Harvest batches |
| /products | ProductsView | Product catalog |
| /warehouses | WarehousesView | Warehouse list |
| /users | UsersView | User management |
| /corporations | CorporationsView | Corporation management |
| /sales | SellInventoryView | Sell inventory form |
| /sales-history | SalesHistoryView | Sales history |
| /login | LoginView | Login page |
| /set-password | SetPasswordView | Password setup |

### Environment Setup

```bash
# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Configure database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=agriflow
DB_USERNAME=postgres
DB_PASSWORD=...

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed data (optional)
php artisan db:seed

# Build frontend
npm run build

# Start development server
php artisan serve
```

---

*Documentation generated for AgriFlow application*
