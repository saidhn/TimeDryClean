# Order Discount System - Database Schema Documentation

## Overview
The discount system allows staff to apply fixed or percentage-based discounts to orders during creation or editing. All discount data is stored directly in the `orders` table.

---

## Database Table: `orders`

### Discount-Related Columns

The following columns were added to the `orders` table to support the discount feature:

| Column Name | Data Type | Nullable | Description |
|------------|-----------|----------|-------------|
| `discount_type` | `enum('fixed', 'percentage')` | YES | Type of discount applied (fixed amount or percentage) |
| `discount_value` | `decimal(10,2)` | YES | The discount value (dollar amount for fixed, percentage for percentage) |
| `discount_amount` | `decimal(10,2)` | YES | The calculated discount amount in dollars |
| `discount_applied_by` | `unsignedBigInteger` | YES | Foreign key to `users.id` - who applied the discount |
| `discount_applied_at` | `timestamp` | YES | When the discount was applied |

### Indexes

- **Index on `discount_applied_by`**: For efficient queries when filtering orders by who applied discounts
- **Index on `discount_applied_at`**: For efficient date-based queries on discount application

### Foreign Key Constraints

- **`discount_applied_by`** → `users.id`
  - Constraint: `orders_discount_applied_by_foreign`
  - On Delete: `SET NULL` (if user is deleted, the discount record remains but the user reference is nullified)

---

## Database Relations

### Order Model Relations

The `Order` model has the following relationship for discounts:

```php
// In app/Models/Order.php

public function discountAppliedBy()
{
    return $this->belongsTo(User::class, 'discount_applied_by');
}
```

This allows you to access who applied the discount:
```php
$order = Order::find(1);
$appliedBy = $order->discountAppliedBy; // Returns User model or null
```

---

## How Discounts Are Stored

### Example 1: Fixed Discount ($50 off)

When a $50 fixed discount is applied to an order with subtotal of $200:

```
discount_type: 'fixed'
discount_value: 50.00
discount_amount: 50.00
discount_applied_by: 5 (user ID)
discount_applied_at: '2026-02-03 15:30:00'
sum_price: 150.00 (original 200 - 50 discount)
```

### Example 2: Percentage Discount (20% off)

When a 20% discount is applied to an order with subtotal of $200:

```
discount_type: 'percentage'
discount_value: 20.00
discount_amount: 40.00 (20% of 200)
discount_applied_by: 5 (user ID)
discount_applied_at: '2026-02-03 15:30:00'
sum_price: 160.00 (original 200 - 40 discount)
```

### Example 3: No Discount

When no discount is applied:

```
discount_type: NULL
discount_value: NULL
discount_amount: NULL
discount_applied_by: NULL
discount_applied_at: NULL
sum_price: 200.00 (original price)
```

---

## Data Flow

### 1. Order Creation with Discount

**Route**: `POST /orders`

**Controller**: `OrdersController@store`

**Process**:
1. Calculate order subtotal from products
2. If discount fields are provided:
   - Validate discount type and value
   - Calculate discount amount
   - Subtract discount from subtotal
   - Store all discount fields in order
3. Save order with discounted `sum_price`

**Database Insert**:
```sql
INSERT INTO orders (
    user_id, 
    sum_price, 
    status,
    discount_type,
    discount_value,
    discount_amount,
    discount_applied_by,
    discount_applied_at,
    created_at,
    updated_at
) VALUES (
    1,           -- user_id
    150.00,      -- sum_price (after discount)
    'pending',   -- status
    'fixed',     -- discount_type
    50.00,       -- discount_value
    50.00,       -- discount_amount
    5,           -- discount_applied_by
    NOW(),       -- discount_applied_at
    NOW(),       -- created_at
    NOW()        -- updated_at
);
```

### 2. Order Update with Discount

**Route**: `PUT /orders/{id}`

**Controller**: `OrdersController@update`

**Process**:
1. Recalculate order subtotal from updated products
2. If discount fields are provided:
   - Validate discount against new subtotal
   - Calculate new discount amount
   - Subtract discount from new subtotal
   - Update all discount fields
3. If no discount fields provided:
   - Clear all discount fields (set to NULL)
4. Update order with new `sum_price`

**Database Update**:
```sql
UPDATE orders 
SET 
    sum_price = 160.00,
    discount_type = 'percentage',
    discount_value = 20.00,
    discount_amount = 40.00,
    discount_applied_by = 5,
    discount_applied_at = NOW(),
    updated_at = NOW()
WHERE id = 308;
```

### 3. Removing Discount

When clearing discount from order edit form (leave fields empty):

**Database Update**:
```sql
UPDATE orders 
SET 
    sum_price = 200.00,  -- restored to full price
    discount_type = NULL,
    discount_value = NULL,
    discount_amount = NULL,
    discount_applied_by = NULL,
    discount_applied_at = NULL,
    updated_at = NOW()
WHERE id = 308;
```

---

## Model Helper Methods

The `Order` model provides several helper methods for working with discounts:

### `hasDiscount()`
Returns `true` if the order has a discount applied.

```php
if ($order->hasDiscount()) {
    echo "Discount applied!";
}
```

### `canApplyDiscount()`
Returns `true` if the order status allows discount application (draft or pending).

```php
if ($order->canApplyDiscount()) {
    // Show discount form
}
```

### `discountAppliedBy()`
Returns the User model who applied the discount.

```php
$user = $order->discountAppliedBy;
echo $user->name; // "John Doe"
```

### `getDiscountDisplayAttribute()`
Returns a formatted string showing the discount.

```php
echo $order->discount_display;
// Output: "-$50.00" or "-20% ($40.00)"
```

---

## Validation Rules

### Order Creation/Update

```php
'discount_type' => 'nullable|in:fixed,percentage',
'discount_value' => 'nullable|numeric|min:0.01',
```

### Business Rules

1. **Fixed Discount**: Cannot exceed order subtotal
2. **Percentage Discount**: Cannot exceed 100%
3. **Discount Value**: Must be positive (> 0.01)
4. **Order Status**: Discounts can only be applied to draft or pending orders

---

## Migration File

**Location**: `database/migrations/2026_02_01_000000_add_discount_fields_to_orders_table.php`

The migration includes:
- Column additions with proper data types
- Indexes for performance
- Foreign key constraints
- Safe checks to prevent duplicate columns on re-run

---

## Summary

✅ **Single Table Storage**: All discount data is stored in the `orders` table  
✅ **No Separate Discount Table**: Discounts are not stored in a separate table  
✅ **Direct Relationship**: One-to-one relationship between order and discount  
✅ **Audit Trail**: Tracks who applied the discount and when  
✅ **Flexible**: Supports both fixed and percentage discounts  
✅ **Validated**: Business rules enforced at controller and service level  
✅ **Translated**: All UI text supports English and Arabic  

The discount is an integral part of the order record, not a separate entity. This design keeps the data model simple and ensures discount information is always available with the order data.
