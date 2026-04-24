# Agriflow System Architecture

## Overview

Agriflow is a **Laravel + Vue.js** web application designed for agricultural inventory and harvest management. The system follows a modern full-stack architecture with a RESTful API backend and a reactive SPA frontend.

```
+------------------+     +------------------+     +------------------+
|   Vue.js Frontend |<--->| Laravel Backend  |<--->|    MySQL DB      |
|   (SPA)          |     |   (API Server)   |     |   (MariaDB)      |
+------------------+     +------------------+     +------------------+
                                       |
                                       v
                                +------------------+
                                |   Queue Worker   |
                                | (Background Jobs) |
                                +------------------+
```

## Architecture Layers

### 1. Presentation Layer (Frontend)
- **Framework**: Vue.js 3.5 with Composition API (`<script setup>`)
- **State Management**: Pinia (Vue stores)
- **Routing**: Vue Router
- **Styling**: Tailwind CSS 4.0
- **UI Library**: Element Plus
- **Build Tool**: Vite 8.0
- **i18n**: vue-i18n (supports en, lg, sw)

### 2. API Layer (Backend)
- **Framework**: Laravel 13.0
- **Authentication**: Laravel Sanctum (Token-based)
- **Authorization**: Laravel Fortify
- **Database**: MySQL/MariaDB (via Laravel Eloquent ORM)
- **API Type**: RESTful JSON API

### 3. Data Layer
- **Primary Database**: MySQL/MariaDB
- **Migrations**: Laravel Schema Builder
- **Models**: Eloquent ORM with Spatie Laravel Translatable
- **Seeding**: Database Seeders with Factories

## Directory Structure

```
agriflow/
├── app/
│   ├── Actions/           # Fortify authentication actions
│   ├── Enums/            # PHP 8.3 Enums (UnitEnum, QualityEnum, etc.)
│   ├── Http/
│   │   └── Controllers/  # API Controllers (Harvest, Product, User, etc.)
│   ├── Models/           # Eloquent Models
│   ├── Notifications/    # User notifications
│   ├── Providers/        # Service providers
│   └── Services/         # Business logic services
│
├── config/               # Laravel configuration
├── database/
│   ├── factories/        # Model factories
│   ├── migrations/       # Database migrations
│   └── seeders/          # Database seeders
│
├── public/               # Static assets entry point
├── resources/
│   ├── js/               # Vue.js components
│   │   ├── components/   # Vue components
│   │   ├── views/        # Page views
│   │   ├── stores/       # Pinia stores
│   │   ├── services/     # Frontend services
│   │   ├── router/       # Vue router
│   │   ├── i18n/         # Translation files
│   │   └── lib/          # Utility libraries
│   └── views/            # Blade templates (welcome.blade.php)
│
├── routes/               # API and Web routes
│   ├── api.php           # REST API routes (authenticated)
│   └── web.php           # SPA catch-all route
│
├── tests/                # PHPUnit tests
├── vendor/               # Composer dependencies
├── node_modules/         # NPM dependencies
└── storage/              # Logs, cache, sessions
```

## Component Diagram

```
┌─────────────────────────────────────────────────────────┐
│                        Frontend (SPA)                       │
├─────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────┐    ┌──────────┐    ┌──────────────────┐    │
│  │ App.vue  │◄──►│ Router   │◄──►│ Views (Pages)     │    │
│  └──────────┘    └──────────┘    └──────────────────┘    │
│          ▲                  ▲                    ▲           │
│          │                  │                    │           │
│  ┌───────┴───────┐  ┌──────┴───────┐    ┌──────┴──────┐    │
│  │  Stores       │  │  Services     │    │ Components │    │
│  │  (Pinia)      │  │  (auth, http) │    │             │    │
│  └───────────────┘  └──────────────┘    └─────────────┘    │
│                                                             │
└─────────────────────────────────────────────────────────┘
                             │
                             │ HTTP/HTTPS (JSON)
                             ▼
┌─────────────────────────────────────────────────────────┐
│                      Backend (Laravel)                       │
├─────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐    ┌─────────────┐    ┌──────────────┐   │
│  │  Controllers │◄──►│  Services   │◄──►│   Models     │   │
│  └─────────────┘    └─────────────┘    └──────────────┘   │
│          ▼                  ▼                    ▲           │
│  ┌─────────────┐    ┌─────────────┐                    │           │
│  │   Routes    │    │  Validation │                    │           │
│  └─────────────┘    └─────────────┘                    │           │
│          ▼                                          │           │
│  ┌──────────────────────────────────────────┐        │           │
│  │               API Middleware               │        │           │
│  │  (auth:sanctum, cors, web, etc.)           │        │           │
│  └──────────────────────────────────────────┘        │           │
│                                                             │
└─────────────────────────────────────────────────────────┘
                             │
                             │ TCP/IP
                             ▼
┌─────────────────────────────────────────────────────────┐
│                      Data Layer                              │
├─────────────────────────────────────────────────────────┤
│  ┌──────────────────────────────────────────────────┐   │
│  │                 MySQL/MariaDB                      │   │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐  │   │
│  │  │ users   │ │products │ │ warehouses│ │  batches │  │   │
│  │  └─────────┘ └─────────┘ └─────────┘ └─────────┘  │   │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐            │   │
│  │  │inventor │ │corporat │ │product_ │            │   │
│  │  │ y       │ │ions     │ │properties│            │   │
│  │  └─────────┘ └─────────┘ └─────────┘            │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

## Data Flow

```
User Action → Vue Component → Vuex/Pinia Store → HTTP Request
       ↓                                      ↓
   Vue Router                               Laravel Route → Middleware
       ↓                                      ↓
   API Call (axios)                         Controller → Service
       ↓                                      ↓
   JSON Response ← Model (Eloquent) ← Database
```

## Key Architectural Features

### 1. Service-Oriented Controllers
- Controllers are thin, delegating business logic to Service classes
- Example: `ProductController` → `ProductService`
- Follows MVC pattern with clear separation of concerns

### 2. Repository Pattern via Eloquent
- Eloquent ORM acts as active record pattern
- Models extend `Illuminate\Database\Eloquent\Model`
- Includes traitable behaviors via `Spatie\Translatable\HasTranslations`

### 3. Authentication & Authorization
- **Laravel Sanctum**: Token-based API authentication
- **Laravel Fortify**: Authentication scaffolding (login, registration, password reset)
- Middleware: `auth:sanctum` protects API routes

### 4. Internationalization
- **Backend**: Laravel translation system
- **Frontend**: vue-i18n with dynamic locale switching
- **Languages**: English (en), Luganda (lg), Swahili (sw)

### 5. Offline Support
- Frontend includes `offlineQueue.js` for queuing actions when offline
- Uses IndexedDB for storage
- Syncs when connectivity is restored

### 6. Background Processing
- Laravel Queue system for async tasks
- Configured in `config/queue.php`
- Uses database driver by default

## API Endpoints Summary

| Method | Endpoint | Controller | Purpose |
|--------|----------|------------|---------|
| GET | `/api/user` | - | Get current user |
| GET | `/api/product` | ProductController | List products |
| POST | `/api/product` | ProductController | Create product |
| DELETE | `/api/product/{uuid}` | ProductController | Delete product |
| GET | `/api/warehouse` | WarehouseController | List warehouses |
| POST | `/api/warehouse` | WarehouseController | Create warehouse |
| DELETE | `/api/warehouse/{uuid}` | WarehouseController | Delete warehouse |
| GET | `/api/harvest` | HarvestController | List harvests |
| POST | `/api/harvest` | HarvestController | Create harvest |
| DELETE | `/api/harvest/{uuid}` | HarvestController | Delete harvest |
| GET | `/api/inventory` | InventoryController | List inventory |
| POST | `/api/inventory/adjust` | InventoryController | Adjust inventory |
| GET | `/api/countries` | CountryController | List countries |
| GET | `/api/corporations` | CorporationController | List corporations |
| POST | `/api/corporation` | CorporationController | Create corporation |
| GET | `/api/corporation` | CorporationController | Get corporation |
| GET | `/api/users` | UserController | List users |
| POST | `/api/users` | UserController | Create user |
| DELETE | `/api/users/{id}` | UserController | Delete user |
| GET | `/api/geocoding/reverse` | GeocodingController | Reverse geocoding |
| GET | `/api/harvest/public/{batchUuid}` | HarvestController | Public harvest view |
| GET | `/api/translations/{locale}` | TranslationController | Get translations |

## Frontend Routing

| Path | Component | Auth Required | Purpose |
|------|-----------|---------------|---------|
| `/` | DashboardView | Yes | Main dashboard |
| `/products` | ProductsView | Yes | Product management |
| `/harvests` | HarvestsView | Yes | Harvest management |
| `/inventory` | InventoryView | Yes | Inventory management |
| `/warehouses` | WarehousesView | Yes | Warehouse management |
| `/corporations` | CorporationsView | Yes | Corporation management |
| `/users` | UsersView | Yes | User management |
| `/harvest/:batchUuid` | HarvestPublicView | No | Public harvest details |
| `/login` | LoginView | No (guest only) | User login |
| `/set-password` | SetPasswordView | No (guest only) | Password setup |

## Security Architecture

### Authentication Flow
1. User submits credentials to `/login` (Sanctum)
2. Token generated and returned
3. Token stored in frontend (localStorage/cookies)
4. Subsequent requests include `Authorization: Bearer <token>` header
5. `auth:sanctum` middleware validates token

### Data Validation
- Request validation in Controllers
- Form requests for complex validation
- Sanitization via Laravel's built-in features

### CSRF Protection
- Sanctum handles CSRF for SPA authentication
- Stateless API tokens for mobile/app clients

## Performance Considerations

- **Frontend**: Vite for fast HMR and optimized builds
- **Backend**: Laravel routing with middleware pipeline
- **Database**: Indexes on frequently queried columns (uuid, foreign keys)
- **Caching**: Laravel cache system (configured in `config/cache.php`)
- **Queue**: Background job processing for long-running tasks
