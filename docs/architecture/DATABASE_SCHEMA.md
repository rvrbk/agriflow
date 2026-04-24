# Agriflow Database Schema

## Overview

The database uses MySQL/MariaDB with Laravel migrations for schema management. All tables use UUIDs as primary keys where applicable, with foreign key relationships maintaining referential integrity.

## Entity Relationship Diagram (ERD)

```
┌─────────────────────────────────────────────────────────────────────┐
│                              ENTITIES                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────┐       ┌─────────────┐       ┌─────────────┐    │
│  │   users     │       │ corporations│       │ warehouses  │    │
│  ├─────────────┤       ├─────────────┤       ├─────────────┤    │
│  │ id (PK)     │       │ id (PK)     │       │ id (PK)     │    │
│  │ uuid        │       │ uuid        │       │ uuid        │    │
│  │ name        │       │ name        │       │ name        │    │
│  │ email       │       │ created_at  │       │ address     │    │
│  │ password    │       │ updated_at  │       │ latitude   │    │
│  │ ...         │       └─────────────┘       │ longitude  │    │
│  └──────┬──────┘                              │ uuid      │    │
│         │                                      │ created_at│    │
│         │                                      │ updated_at│    │
│         │                                      └─────┬──────┘    │
│         │                                            │          │
│         │  ┌─────────────┐       ┌─────────────┐    │          │
│         │  │  products   │       │   batches   │    │          │
│         │  ├─────────────┤       ├─────────────┤    │          │
│         │  │ id (PK)     │       │ id (PK)     │    │          │
│         │  │ uuid        │       │ uuid        │    │          │
│         │  │ code        │       │ code        │    │          │
│         │  │ code_type   │       │ qr_code     │    │          │
│         │  │ unit        │       │ warehouse_id│────┘          │
│         │  │ created_at  │       │ corporation_│               │
│         │  │ updated_at  │       │ id (FK)     │               │
│         │  └──────┬──────┘       │ product_id  │               │
│         │         │              │ (FK)        │               │
│         │         │              │ user_id     │               │
│         │         │              │ created_at  │               │
│         │         │              └──────┬──────┘               │
│         │         │                     │                      │
│         │         │              ┌──────▼──────┐               │
│         │         │              │  inventory  │               │
│         │         │              ├─────────────┤               │
│         │         │              │ id (PK)     │               │
│         │         │              │ product_id  │───────────────┘
│         │         │              │ warehouse_id│
│         │         │              │ batch_id    │
│         │         │              │ quantity    │
│         │         │              │ location    │
│         │         │              │ created_at  │
│         │         │              │ updated_at  │
│         │         │              └─────────────┘
│         │         │
│         │         │       ┌─────────────┐
│         │         │       │product_      │
│         │         │       │ properties   │
│         │         └──────►│ id (PK)      │
│         │                 │ product_id   │
│         │                 │ type         │
│         │                 │ sequence     │
│         │                 │ key (trans)  │
│         │                 │ value (trans)│
│         │                 │ created_at   │
│         │                 │ updated_at   │
│         │                 └─────────────┘
│         │
│         │       ┌─────────────┐
│         └──────►│ personal_   │
│                 │ access_     │
│                 │ tokens      │
│                 ├─────────────┤
│                 │ id (PK)     │
│                 │ tokenable_id│
│                 │ name        │
│                 │ token       │
│                 │ abilities   │
│                 │ created_at  │
│                 │ updated_at  │
│                 └─────────────┘
│
└─────────────────────────────────────────────────────────────────────┘
```

## Table Definitions

### users
```sql
id: bigIncrements (PK, AI)
uuid: string(36), unique, nullable
name: string
email: string, unique
password: string
remember_token: string, nullable
two_factor_secret: text, nullable
two_factor_recovery_codes: text, nullable
email_verified_at: timestamp, nullable
created_at: timestamp
updated_at: timestamp
```

**Relationships:**
- HasMany personal_access_tokens
- HasMany batches (user_id)
- BelongsToMany warehouses (via corporation access)

### corporations
```sql
id: bigIncrements (PK, AI)
uuid: string(36), unique
name: string (translatable)
created_at: timestamp
updated_at: timestamp
```

**Relationships:**
- HasMany warehouses
- HasMany batches

### warehouses
```sql
id: bigIncrements (PK, AI)
uuid: string(36), unique
name: string
address: text, nullable
latitude: decimal(10,8), nullable
longitude: decimal(11,8), nullable
corporation_id: foreignId, nullable
created_at: timestamp
updated_at: timestamp
```

**Relationships:**
- BelongsTo corporation
- HasMany inventory
- HasMany batches

### products
```sql
id: bigIncrements (PK, AI)
uuid: string(36), unique
code: string, nullable
code_type: string, nullable
unit: string, nullable
created_at: timestamp
updated_at: timestamp
```

**Translatable Attributes:**
- name (en, lg, sw)

**Relationships:**
- HasMany inventory
- HasMany product_properties
- HasMany batches (via inventory)

### batches
```sql
id: bigIncrements (PK, AI)
uuid: string(36), unique
code: string, nullable
qr_code: text, nullable
warehouse_id: foreignId
corporation_id: foreignId, nullable
user_id: foreignId, nullable
created_at: timestamp
updated_at: timestamp
```

**Relationships:**
- BelongsTo warehouse
- BelongsTo corporation
- BelongsTo user
- HasMany inventory

### inventory
```sql
id: bigIncrements (PK, AI)
product_id: foreignId
warehouse_id: foreignId, nullable
batch_id: foreignId, nullable
quantity: decimal(15,2)
location: string, nullable
created_at: timestamp
updated_at: timestamp
```

**Relationships:**
- BelongsTo product
- BelongsTo warehouse
- BelongsTo batch (nullable)

### product_properties
```sql
id: bigIncrements (PK, AI)
product_id: foreignId
type: string
sequence: integer
created_at: timestamp
updated_at: timestamp
```

**Translatable Attributes:**
- key (en, lg, sw)
- value (en, lg, sw)

**Relationships:**
- BelongsTo product

### product_certifications (exists but not heavily used)
```sql
id: bigIncrements (PK, AI)
name: string (translatable)
created_at: timestamp
updated_at: timestamp
```

### product_product_certifications (pivot)
```sql
product_id: foreignId
product_certification_id: foreignId
```

### personal_access_tokens
```sql
id: bigIncrements (PK, AI)
tokenable_type: string (polymorphic)
tokenable_id: bigInteger (polymorphic)
name: string
token: string, unique
abilities: text, nullable
last_used_at: timestamp, nullable
expires_at: timestamp, nullable
created_at: timestamp
updated_at: timestamp
```

## Indexes

| Table | Column | Type | Purpose |
|-------|--------|------|---------|
| users | uuid | unique | UUID lookup |
| users | email | unique | Email lookup |
| corporations | uuid | unique | UUID lookup |
| warehouses | uuid | unique | UUID lookup |
| warehouses | corporation_id | foreign | Corporation relationship |
| products | uuid | unique | UUID lookup |
| batches | uuid | unique | UUID lookup |
| batches | warehouse_id | foreign | Warehouse relationship |
| batches | corporation_id | foreign | Corporation relationship |
| batches | user_id | foreign | User relationship |
| inventory | product_id | foreign | Product relationship |
| inventory | warehouse_id | foreign | Warehouse relationship |
| inventory | batch_id | foreign | Batch relationship (nullable) |
| product_properties | product_id | foreign | Product relationship |
| personal_access_tokens | token | unique | Token lookup |
| personal_access_tokens | tokenable_id, tokenable_type | composite | Polymorphic relationship |

## Enums

The application uses PHP 8.3 native enums:

### UnitEnum
```php
cases: KILOGRAM, GRAM, TONNE, LITRE, MILLILITRE, UNIT, BOX, BAG, CRATE, OTHER
```

### QualityEnum
```php
cases: GRADE_A, GRADE_B, GRADE_C, ORGANIC, CONVENTIONAL, PREMIUM
```

### ProductPropertyTypeEnum
```php
cases: NUTRITIONAL, PHYSICAL, CHEMICAL, PACKAGING, CERTIFICATION, OTHER
```

### CodeTypeEnum
```php
cases: GTIN, SKU, PLU, CUSTOM
```

## Constraints

### Business Rules
1. Products cannot be deleted if they have linked harvest records (inventory with batch_id)
2. Warehouses belong to corporations
3. Batches belong to warehouses and optionally to corporations/users
4. Inventory records link products to warehouses and optionally to batches
5. Product properties are linked to products with translatable key-value pairs

### Validation Rules
- UUIDs are unique and immutable
- Email addresses are unique and validated
- Passwords are hashed (bcrypt)
- Foreign key constraints are enforced at database level
- Translatable fields support en, lg, sw locales

## Migrations Timeline

| Date | Migration | Purpose |
|------|-----------|---------|
| 2026-02-19 | create_products_table | Initial products table |
| 2026-03-19 | create_personal_access_tokens_table | Sanctum tokens |
| 2026-03-19 | create_inventory_table | Inventory tracking |
| 2026-03-23 | add_two_factor_columns_to_users_table | 2FA support |
| 2026-03-28 | create_product_properties_table | Product attributes |
| 2026-03-28 | create_product_certifications_table | Certification system |
| 2026-03-28 | create_product_product_certifications_table | Pivot table |
| 2026-03-29 | add_sequence_column_to_product_properties_table | Ordering |
| 2026-03-31 | create_warehouses_table | Warehouse management |
| 2026-03-31 | add_warehouse_id_column_to_inventories_table | Link inventory to warehouse |
| 2026-03-31 | add_uuid_column_to_warehouses_table | UUID support |
| 2026-03-31 | add_location_column_to_inventories_table | Location tracking |
| 2026-03-31 | add_address_columns_to_warehouse_table | Address fields |
| 2026-03-31 | create_corporation_table | Corporation entity |
| 2026-03-31 | create_batches_table | Batch/harvest tracking |
| 2026-03-31 | add_batch_id_column_to_inventories_table | Link inventory to batch |
| 2026-03-31 | add_corporation_id_column_to_warehouses_table | Link warehouse to corporation |
| 2026-04-07 | add_qr_columns_to_batches_table | QR code support |
