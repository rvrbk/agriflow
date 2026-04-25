# Sales Feature - User Guide & Technical Documentation

## Overview

The Sales feature allows users to record inventory sales by subtracting stock from existing inventory records. This feature provides a dedicated interface for selling products, separate from the general inventory adjustment functionality.

## User Instructions

### Accessing the Sales Feature

There are **two ways** to access the Sales feature:

1. **From the Dashboard**
   - A "Sell Inventory" button is prominently displayed on the Dashboard
   - Clicking this button takes you directly to the Sales page

2. **From the Menu**
   - Click the menu toggle button (three dots) in the header
   - Navigate to: **Main → Sales**

### Using the Sales Page

1. **View Inventory**
   - The page displays all inventory items available for sale
   - Each item shows: Product name, Batch UUID, Harvest date, Warehouse, Quality, and Available quantity

2. **Search Inventory**
   - Use the search bar to filter inventory by product name, batch, warehouse, or quality
   - Results update in real-time as you type

3. **Selling Inventory**
   - For each inventory item, enter the quantity to sell in the input field
   - The input respects decimal values for weight-based products (kg, g, l, ml)
   - For piece-based products (pcs), only whole numbers are accepted
   - Click the "Sell" button to process the sale

4. **Validation & Feedback**
   - If you enter an invalid amount (zero or negative), an error message appears
   - If you try to sell more than available stock, an error message appears
   - On successful sale, a confirmation message appears and inventory is updated
   - The page automatically refreshes to show updated quantities

### Sales Workflow

```
User → Dashboard/Sales Menu → Sales Page → Select Item → Enter Quantity → Click Sell → Confirmation
```

## Technical Implementation

### Frontend Components

#### New Component
- **`SellInventoryView.vue`** (`resources/js/views/SellInventoryView.vue`)
  - Displays inventory list with sell functionality
  - Handles search and filtering
  - Validates input before submission
  - Shows success/error messages to user

#### Modified Components
- **`App.vue`** - Added "Sales" link in the Main menu section
- **`DashboardView.vue`** - Added "Sell Inventory" quick-access button
- **`router/index.js`** - Added route for `/sales` path

### Backend Implementation

#### New API Endpoint
```
POST /api/inventory/sell
```

**Controller:** `InventoryController@sell`

**Purpose:** Dedicated endpoint for selling inventory (subtracting stock)

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `batch_uuid` | string | Yes | UUID of the batch to sell from |
| `amount` | number | Yes | Quantity to sell (must be > 0) |

**Response Codes:**
- `200 OK` - Sale completed successfully
- `404 Not Found` - Batch or inventory not found
- `422 Unprocessable Entity` - Insufficient quantity or invalid amount

#### Modified Code
- **`InventoryController.php`** - Added `sell()` method
- **`api.php`** - Added route for `/inventory/sell` endpoint
- **`InventoryController.php`** - Fixed `orderBy` for JSON column (`products.name->>'en'`)

### Database Considerations

The sales feature works with existing database tables:
- **`inventories`** - Stores stock quantities
- **`batches`** - Stores batch information
- **`products`** - Stores product information (note: `name` is JSON)
- **`warehouses`** - Stores warehouse information

**Important Note:** The `products.name` column is a JSON type storing translations. The API query uses PostgreSQL's JSON operator (`->>'en'`) to properly order by the English name.

### Translations

The Sales feature is fully translated for all supported languages:

#### English (en)
- Title: "Sell Inventory"
- Subtitle: "Record sales by removing inventory stock."
- Sell button: "Sell" / "Selling..."
- Success: "Inventory sold successfully."
- Errors: Various validation and error messages

#### Luganda (lg)
- Title: "Saito ebya Okugyako"
- Subtitle: "Terekera ebigyako bwe bugyawo mu sitoka."
- Sell button: "Gyawo" / "Kugyako..."
- Success: "Saito bigyako nnyo."
- Errors: Translated validation messages

#### Swahili (sw)
- Title: "Uuza Hirizi"
- Subtitle: "Rekodi mauzo kwa kuondoa hisa ya hirizi."
- Sell button: "Uuza" / "Kinauzwa..."
- Success: "Hirizi imeuza kwa mafanikio."
- Errors: Translated validation messages

All translations are stored in:
- `lang/en/ui.php`
- `lang/lg/ui.php`
- `lang/sw/ui.php`

## Error Handling

### Validation Errors
The sell endpoint validates all input:

1. **batch_uuid required** - Must be a non-empty string
2. **amount required** - Must be a positive number
3. **amount > 0** - Must be greater than zero
4. **Sufficient stock** - Cannot sell more than available quantity

### Error Responses

| Error | HTTP Code | Message |
|-------|-----------|---------|
| Batch not found | 404 | "Batch not found." |
| Inventory not found | 404 | "Inventory not found." |
| Invalid amount | 422 | "Insufficient inventory quantity." |
| Validation error | 422 | Appropriate validation message |

## Styling & UX

### Color Scheme
- **Primary button (Sell)**: Orange (`bg-[#d97706]`)
- **Success message**: Green background (`bg-green-50`)
- **Error message**: Red background (`bg-red-50`)

### Iconography
- Dashboard button uses a shopping cart icon (SVG)
- Consistent with the application's design system

### Responsive Design
- Inventory list uses grid layout on desktop
- Single column on mobile devices
- Input fields and buttons adjust to screen size

## Testing

### Manual Testing

1. **Access Sales Page**
   - Verify both navigation paths work (Dashboard button & Menu)

2. **View Inventory**
   - Verify all inventory items display correctly
   - Verify search functionality works

3. **Sell Inventory**
   - Try selling valid quantities (success case)
   - Try selling more than available (error case)
   - Try selling invalid amount (zero/negative, error case)
   - Try selling non-existent batch (error case)

4. **Verify Updates**
   - After sale, verify quantity decreases
   - Verify the change persists across page refreshes

### Automated Testing

The sales feature follows the same patterns as the existing Inventory adjustment feature. Consider adding tests for:
- New `sell()` method in `InventoryController`
- Route authorization (requires `auth:sanctum`)
- Validation rules
- Database updates

## API Integration

The frontend component calls the sell endpoint with:

```javascript
await http.post('/api/inventory/sell', {
    batch_uuid: row.batch_uuid,
    amount,
});
```

On success:
- Shows success message
- Refreshes inventory list
- Resets amount input

On error:
- Shows appropriate error message
- Does not refresh inventory list

## Future Enhancements

Potential improvements for future versions:

1. **Sales History** - Track all sales with timestamps and user info
2. **Sales Reports** - Generate reports by product, date range, warehouse
3. **Customer Information** - Associate sales with customer data
4. **Receipt Generation** - Create printable receipts for sales
5. **Multi-item Sales** - Sell multiple items in a single transaction
6. **Price Tracking** - Record sale prices and calculate revenue

## Dependencies

- Laravel 10.x
- PostgreSQL (for JSON column support)
- Vue 3
- Tailwind CSS
- Pinia (for state management)

## See Also

- [API Documentation](./API_DOCUMENTATION.md) - Complete API endpoint reference
- [System Architecture](./SYSTEM_ARCHITECTURE.md) - Overall system design
- [Database Schema](./DATABASE_SCHEMA.md) - Database table definitions

---

*Document Version: 1.0*
*Last Updated: April 25, 2026*
*Maintained by: Agriflow Development Team*
