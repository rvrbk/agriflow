# Agriflow Code Style Guide

## Overview

This document outlines the coding standards and style conventions for the Agriflow project. The project follows PSR-12 standards for PHP and uses Laravel Pint for automatic code formatting.

## PHP Code Style

### General Rules
- **File Encoding**: UTF-8 without BOM
- **Line Endings**: LF (\n)
- **Indentation**: 4 spaces (no tabs)
- **Brace Style**: PSR-12 (opening braces on new line for classes, same line for control structures)
- **Class Names**: PascalCase
- **Method Names**: camelCase
- **Variable Names**: snake_case (PHP) / camelCase (JavaScript)
- **Constants**: UPPER_SNAKE_CASE

### EditorConfig
```
root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 4
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{yml,yaml}]
indent_size = 2

[compose.yaml]
indent_size = 4
```

### PSR-12 Compliance
The project uses **Laravel Pint** for PHP code style enforcement:
```bash
composer require laravel/pint --dev
./vendor/bin/pint
```

**Key PSR-12 Rules Applied:**
- No closing `?>` tag for PHP files containing only PHP
- One class per file
- Type declarations (PHP 8.3+)
- Return type hints for all methods
- Property type declarations
- Native type hints (string, int, array, etc.)

### PHP Specific Conventions

#### Namespaces
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    // ...
}
```

#### Imports
- Group by namespace origin
- Alphabetical within each group
- One import per line

#### Class Structure
```php
<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public const STATUS_ACTIVE = 'active';
    
    protected array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    public function getProducts(): array
    {
        // Method body
    }
    
    protected function validate(array $data): bool
    {
        // Method body
    }
    
    private function log(string $message): void
    {
        // Method body
    }
}
```

#### Method Signatures
```php
// Simple return type
public function getProduct(string $uuid): ?Product
{
    // ...
}

// Union types (PHP 8.0+)
public function find(int|string $id): Product|null
{
    // ...
}

// Array with key and value types
public function getAll(): array<string, Product>
{
    // ...
}
```

#### Control Structures
```php
// If statements
if ($condition) {
    // Single statement can be on same line if short
    return true;
}

// If-else
if ($condition) {
    // ...
} elseif ($otherCondition) {
    // ...
} else {
    // ...
}

// Switch
switch ($value) {
    case 'a':
        // ...
        break;
    
    case 'b':
        // ...
        break;
    
    default:
        // ...
        break;
}

// Foreach
foreach ($items as $item) {
    // ...
}

// While
while ($condition) {
    // ...
}

// Do-while
do {
    // ...
} while ($condition);
```

#### Laravel-Specific

**Facade Usage:**
```php
// At top of file
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

// In method
Log::info('User logged in', ['user_id' => $user->id]);
```

**Dependency Injection:**
```php
public function __construct(
    protected ProductService $productService,
    protected Request $request
) {}
```

**Route Definitions:**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
});
```

**Eloquent Queries:**
```php
// Chained methods
$products = Product::query()
    ->where('status', Status::ACTIVE)
    ->orderBy('name', 'asc')
    ->get();

// With relationships
$products = Product::with('inventory', 'properties')
    ->whereHas('inventory')
    ->get();
```

## JavaScript/Vue.js Code Style

### General Rules
- **Indentation**: 2 spaces (as per Vue.js community standard)
- **Quotes**: Single quotes for strings, double quotes for HTML attributes
- **Semicolons**: Required
- **Comma Dangle**: Yes (trailing commas)
- **ESLint Config**: Follows Vue.js official ESLint config

### Vue.js Template Style

```vue
<template>
  <div class="container">
    <h1>Products</h1>
    
    <ul>
      <li
        v-for="product in products"
        :key="product.uuid"
        class="product-item"
      >
        {{ product.name }}
      </li>
    </ul>
  </div>
</template>
```

**Attributes:**
- HTML attributes: `kebab-case`
- Vue directives: `camelCase` (v-on, v-bind, etc.)
- Custom events: `kebab-case`
- Props: `camelCase`

**Attribute Order:**
1. `v-` directives (v-if, v-for, etc.)
2. `:` or `v-bind:` bindings
3. `@` or `v-on:` event listeners
4. Regular HTML attributes
5. Class and style (class after id, style last)

### Composition API Style

```javascript
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['refresh']);

const router = useRouter();
const { t } = useI18n();

const count = ref(0);
const products = ref([]);

const computedValue = computed(() => {
    return products.value.length;
});

function handleClick() {
    count.value++;
}

onMounted(() => {
    loadProducts();
});

defineExpose({
    reload: loadProducts,
});
</script>
```

### Pinia Store Style

```javascript
// stores/auth.js
export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(null);
    const isInitialized = ref(false);

    const login = async (credentials) => {
        const response = await http.post('/api/login', credentials);
        user.value = response.data.user;
        token.value = response.data.token;
    };

    const logout = async () => {
        await http.post('/api/logout');
        user.value = null;
        token.value = null;
    };

    return {
        user,
        token,
        isInitialized,
        login,
        logout,
    };
});
```

### Axios HTTP Calls

```javascript
// lib/http.js
import axios from 'axios';

const http = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    timeout: 30000,
});

// Add request interceptor
export const setupInterceptors = (store) => {
    http.interceptors.request.use((config) => {
        const token = store.state.auth.token;
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    });
};

export default http;
```

**In Components:**
```javascript
const fetchProducts = async () => {
    try {
        const response = await http.get('/products');
        products.value = response.data;
    } catch (error) {
        console.error('Failed to load products:', error);
        // Handle error
    }
};
```

### Imports

**Grouped and Ordered:**
```javascript
// 1. Vue core
import { ref, computed } from 'vue';

// 2. Vue Router
import { useRouter, useRoute } from 'vue-router';

// 3. External libraries
import axios from 'axios';

// 4. Local utilities
import http from './lib/http';

// 5. Stores
import { useAuthStore } from './stores/auth';

// 6. Components
import ProductCard from './components/ProductCard.vue';
```

## Database & Migrations

### Migration Style
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->json('name');
            $table->string('code')->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

**Best Practices:**
- Always include `down()` method for rollback
- Use `timestamps()` for created_at and updated_at
- Use `nullable()` for optional fields
- Use proper column types (string, integer, decimal, etc.)
- Add foreign key constraints with `->constrained()`
- Add indexes for frequently queried columns

### Model Style
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'uuid',
        'code',
        'code_type',
        'unit',
    ];

    protected $casts = [
        // Cast fields as needed
    ];

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function properties()
    {
        return $this->hasMany(ProductProperty::class);
    }
}
```

## Testing Style

### PHPUnit
```php
<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_it_creates_a_product(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Test Product',
            'code' => 'TEST001',
            'unit' => 'kg',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'uuid',
                'name',
                'code',
                'unit',
            ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/products', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
```

## Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Class | PascalCase | ProductService |
| Interface | PascalCase | ProductInterface |
| Trait | PascalCase | HasUuid |
| Enum | PascalCase | UnitEnum |
| Method | camelCase | getProducts, storeProduct |
| Variable | snake_case | $product_name (PHP) |
| Variable | camelCase | const productName (JS) |
| Constant | UPPER_SNAKE_CASE | MAX_PRODUCTS |
| Table Name | snake_case, plural | products, inventories |
| Column Name | snake_case | product_name, created_at |
| Route Name | dot.notation | products.index, products.store |
| View File | PascalCase | ProductsView.vue |
| Component File | PascalCase | ProductCard.vue |

## File Organization

### Backend (app/)
```
app/
├── Actions/           # Action classes (Fortify)
├── Enums/            # PHP 8.3 Enums
├── Http/
│   └── Controllers/  # API controllers (alphabetical)
├── Models/           # Eloquent models (alphabetical)
├── Notifications/    # Notification classes
├── Providers/        # Service providers
└── Services/         # Business logic services
```

### Frontend (resources/js/)
```
resources/js/
├── app.js            # Main app entry point
├── App.vue           # Root component
├── bootstrap.js      # Laravel bootstrap
├── components/       # Shared components
│   └── ui/           # UI component library
├── views/            # Page-level components
├── stores/           # Pinia stores
├── services/         # API service classes
├── router/           # Vue Router config
├── i18n/             # Translation files
│   └── index.js      # i18n setup
└── lib/              # Utility libraries
```

## Comments & Documentation

### PHP

**DocBlocks:**
```php
/**
 * Get the list of products.
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function list(): JsonResponse
{
    // ...
}

/**
 * @param string $uuid The product UUID
 * @return string Deletion result status
 */
public function deleteByUuid(string $uuid): string
{
    // ...
}
```

**Type Hints in DocBlocks:**
```php
/**
 * @var array<string, mixed>
 */
protected array $data;

/**
 * @return array<int, Product>
 */
public function getActiveProducts(): array
{
    // ...
}
```

### JavaScript/Vue

**JSDoc:**
```javascript
/**
 * Fetches products from the API.
 * @returns {Promise(Array)} List of products
 */
const fetchProducts = async () => {
    // ...
};

/**
 * Product model interface
 * @typedef {Object} Product
 * @property {string} uuid - Product UUID
 * @property {string} name - Product name
 * @property {string} code - Product code
 * @property {string} unit - Unit of measurement
 */
```

**Component Documentation:**
```vue
<script setup>
/**
 * ProductCard - Displays a product card with details
 * 
 * @emits refresh - Emitted when product data needs refresh
 * @props product - The product to display
 * @props compact - Whether to show compact view
 */
const props = defineProps({
    product: { type: Object, required: true },
    compact: { type: Boolean, default: false },
});
</script>
```

## Formatting Tools

### PHP
- **Laravel Pint**: `./vendor/bin/pint` (auto-formats and fixes)
- **PHP-CS-Fixer**: Alternative (not currently configured)

### JavaScript
- **ESLint**: Configured for Vue.js
- **Prettier**: Opinionated formatter

###Editor Setup

**VS Code (recommended):**
- PHP Intelephense extension
- Vue Language Features (Volar) extension
- ESLint extension
- Prettier extension
- EditorConfig for VS Code

**Settings.json:**
```json
{
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "editor.formatOnSave": true,
    "editor.tabSize": 2,
    "editor.insertSpaces": true,
    "files.autoSave": "onFocusChange",
    "[php]": {
        "editor.tabSize": 4
    }
}
```

## Code Quality Tools

### PHP
```bash
# Run Pint (fixes formatting)
./vendor/bin/pint

# Run Pint (check only)
./vendor/bin/pint --test

# Run PHPUnit tests
composer test

# Static analysis (pint includes basic checks)
```

### JavaScript
```bash
# Run ESLint
npm run lint

# Run tests (if configured)
# npm test
```

## Common Patterns

### Service Pattern
```php
// Good: Business logic in service class
class ProductService
{
    public function store(array $data): Product
    {
        // Validation
        // Business logic
        // Database operations
        return $product;
    }
}

// Controller calls service
class ProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $product = app()->make(ProductService::class)->store($request->all());
        return response()->json($product, 201);
    }
}
```

### Repository Pattern (simplified)
```php
// Using Eloquent directly in controllers for simple queries
class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->orderBy('name')
            ->get();
        
        return response()->json($products);
    }
}

// For complex queries, use local scopes
class Product extends Model
{
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->whereHas('inventory', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        });
    }
}
```

### Frontend Composition
```vue
<script setup>
// Import dependencies
import { ref } from 'vue';
import http from './lib/http';

// State
const products = ref([]);
const loading = ref(false);
const error = ref(null);

// Actions
const loadProducts = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await http.get('/products');
        products.value = response.data;
    } catch (err) {
        error.value = err.message;
    } finally {
        loading.value = false;
    }
};

// Lifecycle
onMounted(() => {
    loadProducts();
});
</script>
```

## Anti-Patterns to Avoid

### ❌ Don't
- Mix business logic in controllers (move to services)
- Use `DB::raw()` for user input (SQL injection risk)
- Use global helper functions excessively (use proper classes)
- Create God classes (keep classes focused and small)
- Use `function()` syntax in Vue (use arrow functions)
- Mutate props directly (use emits or local state)
- Use `var` in JavaScript (use `const` or `let`)
- Commit commented-out code
- Use `any` type in TypeScript (not applicable here, but good practice)

### ✅ Do
- Follow SOLID principles
- Use dependency injection
- Validate all inputs
- Handle errors gracefully
- Write readable, maintainable code
- Add meaningful comments for complex logic
- Use proper types and return types
- Follow the existing codebase patterns

## Version Control

### Commits
- Use conventional commit messages
- Prefix with type: `feat:`, `fix:`, `docs:`, `refactor:`, `chore:`
- Keep messages concise (50 chars or less for subject)
- Use present tense: "Add feature" not "Added feature"

### Examples
```bash
# Good
git commit -m "feat: add product creation endpoint"
git commit -m "fix: prevent duplicate inventory entries"
git commit -m "docs: update API documentation"

# Bad
git commit -m "fixed stuff"  # Too vague
git commit -m "Added new feature for products and also fixed some bugs"  # Too long
git commit -m "WIP"  # Not descriptive
```

### Branch Naming
- Feature branches: `feature/product-search`
- Bug fixes: `fix/inventory-calculation`
- Refactoring: `refactor/product-service`
- Main branches: `main`, `develop`

## Final Notes

This style guide is meant to be practical, not dogmatic. The most important rule is **consistency** - follow the patterns already established in the codebase. When in doubt, match the surrounding code.
