# Data Model: Order Discount System

**Created**: 2026-02-01
**Feature**: 1-order-discount

---

## Entity Overview

### Order Entity Extensions

The discount functionality extends the existing `orders` table with new fields to support discount tracking and calculation.

#### Orders Table Schema

```sql
-- Existing orders table structure (simplified)
CREATE TABLE orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    customer_id BIGINT,
    staff_id BIGINT,
    order_number VARCHAR(50) UNIQUE,
    status ENUM('draft', 'pending', 'processing', 'completed', 'cancelled'),
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- ... existing fields
);

-- New discount fields to add
ALTER TABLE orders ADD COLUMN discount_type ENUM('fixed', 'percentage') NULL;
ALTER TABLE orders ADD COLUMN discount_value DECIMAL(10,2) NULL;
ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) NULL;
ALTER TABLE orders ADD COLUMN discount_applied_by BIGINT NULL;
ALTER TABLE orders ADD COLUMN discount_applied_at TIMESTAMP NULL;

-- Indexes for performance
ALTER TABLE orders ADD INDEX idx_discount_applied_by (discount_applied_by);
ALTER TABLE orders ADD INDEX idx_discount_type (discount_type);
```

#### Field Definitions

| Field | Type | Null | Description |
|-------|------|------|-------------|
| `discount_type` | ENUM('fixed', 'percentage') | YES | Type of discount applied |
| `discount_value` | DECIMAL(10,2) | YES | Discount value entered by staff |
| `discount_amount` | DECIMAL(10,2) | YES | Calculated discount amount in currency |
| `discount_applied_by` | BIGINT | YES | Foreign key to staff who applied discount |
| `discount_applied_at` | TIMESTAMP | YES | When discount was applied |

#### Field Constraints

```sql
-- Discount value must be positive
ALTER TABLE orders ADD CONSTRAINT chk_discount_value_positive 
    CHECK (discount_value IS NULL OR discount_value > 0);

-- Discount amount must be positive
ALTER TABLE orders ADD CONSTRAINT chk_discount_amount_positive 
    CHECK (discount_amount IS NULL OR discount_amount >= 0);

-- Discount applied by must reference staff if set
ALTER TABLE orders ADD CONSTRAINT fk_discount_applied_by 
    FOREIGN KEY (discount_applied_by) REFERENCES staff(id)
    ON DELETE SET NULL;
```

---

## Relationships

### Order Relationships

```php
// app/Models/Order.php
class Order extends Model
{
    // Existing relationships...
    
    public function discountAppliedBy()
    {
        return $this->belongsTo(Staff::class, 'discount_applied_by');
    }
    
    // Discount-related methods
    public function hasDiscount(): bool
    {
        return !is_null($this->discount_type);
    }
    
    public function getDiscountedSubtotal(): float
    {
        if (!$this->hasDiscount()) {
            return $this->subtotal;
        }
        
        return $this->subtotal - $this->discount_amount;
    }
    
    public function canApplyDiscount(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }
}
```

### Staff Relationships

```php
// app/Models/Staff.php
class Staff extends Model
{
    // Existing relationships...
    
    public function appliedDiscounts()
    {
        return $this->hasMany(Order::class, 'discount_applied_by');
    }
}
```

---

## Validation Rules

### Business Logic Validation

```php
// app/Services/DiscountService.php
class DiscountService
{
    public function validateDiscount(Order $order, string $type, float $value): array
    {
        $errors = [];
        
        // Check order status
        if (!$order->canApplyDiscount()) {
            $errors[] = 'Discounts can only be applied to draft or pending orders';
        }
        
        // Check discount value
        if ($value <= 0) {
            $errors[] = 'Discount value must be positive';
        }
        
        // Type-specific validation
        if ($type === 'fixed') {
            if ($value > $order->subtotal) {
                $errors[] = 'Fixed discount cannot exceed order subtotal';
            }
        } elseif ($type === 'percentage') {
            if ($value > 100) {
                $errors[] = 'Percentage discount cannot exceed 100%';
            }
        }
        
        return $errors;
    }
    
    public function calculateDiscountAmount(Order $order, string $type, float $value): float
    {
        if ($type === 'fixed') {
            return $value;
        }
        
        if ($type === 'percentage') {
            return round($order->subtotal * ($value / 100), 2);
        }
        
        return 0;
    }
    
    public function calculateNewTotal(Order $order, float $discountAmount): float
    {
        $discountedSubtotal = $order->subtotal - $discountAmount;
        $taxRate = $order->tax / $order->subtotal; // Current tax rate
        $newTax = round($discountedSubtotal * $taxRate, 2);
        
        return round($discountedSubtotal + $newTax, 2);
    }
}
```

### Request Validation

```php
// app/Http/Requests/ApplyDiscountRequest.php
class ApplyDiscountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0.01',
        ];
    }
    
    public function messages(): array
    {
        return [
            'discount_type.required' => 'Please select a discount type',
            'discount_type.in' => 'Invalid discount type selected',
            'discount_value.required' => 'Please enter a discount value',
            'discount_value.min' => 'Discount value must be greater than 0',
        ];
    }
}
```

---

## State Transitions

### Order Status Flow

```
[draft] → [pending] → [processing] → [completed]
   ↓           ↓           ↓
[cancelled] [cancelled] [cancelled]
```

### Discount Application Rules

| Order Status | Can Apply Discount | Can Edit Discount | Can Remove Discount |
|--------------|-------------------|-------------------|---------------------|
| draft        | ✅ Yes            | ✅ Yes            | ✅ Yes              |
| pending      | ✅ Yes            | ✅ Yes            | ✅ Yes              |
| processing   | ❌ No             | ❌ No             | ❌ No               |
| completed    | ❌ No             | ❌ No             | ❌ No               |
| cancelled    | ❌ No             | ❌ No             | ❌ No               |

### Discount State Diagram

```
No Discount
    ↓ (apply discount)
Active Discount
    ↓ (edit discount)
Active Discount (updated)
    ↓ (remove discount)
No Discount
```

---

## Data Integrity

### Database Constraints

```sql
-- Ensure discount fields are consistent
ALTER TABLE orders ADD CONSTRAINT chk_discount_consistency 
    CHECK (
        (discount_type IS NULL AND discount_value IS NULL AND discount_amount IS NULL) OR
        (discount_type IS NOT NULL AND discount_value IS NOT NULL AND discount_amount IS NOT NULL)
    );

-- Ensure discount_applied_by is set when discount is applied
ALTER TABLE orders ADD CONSTRAINT chk_discount_applied_by 
    CHECK (
        (discount_type IS NULL AND discount_applied_by IS NULL) OR
        (discount_type IS NOT NULL AND discount_applied_by IS NOT NULL)
    );
```

### Application-Level Validation

```php
// Model events for data integrity
class Order extends Model
{
    protected static function booted()
    {
        static::saving(function ($order) {
            // Recalculate discount amount if type or value changes
            if ($order->isDirty(['discount_type', 'discount_value']) && $order->discount_type) {
                $service = app(DiscountService::class);
                $order->discount_amount = $service->calculateDiscountAmount(
                    $order, 
                    $order->discount_type, 
                    $order->discount_value
                );
            }
            
            // Clear discount fields if type is null
            if ($order->discount_type === null) {
                $order->discount_value = null;
                $order->discount_amount = null;
                $order->discount_applied_by = null;
                $order->discount_applied_at = null;
            }
        });
        
        static::updating(function ($order) {
            // Prevent discount modification on ineligible orders
            if ($order->isDirty(['discount_type', 'discount_value']) && !$order->canApplyDiscount()) {
                throw new InvalidArgumentException('Cannot modify discount on this order status');
            }
        });
    }
}
```

---

## Migration Strategy

### Initial Migration

```php
// database/migrations/2026_02_01_add_discount_fields_to_orders_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountFieldsToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable()->after('total');
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_value');
            $table->bigInteger('discount_applied_by')->unsigned()->nullable()->after('discount_amount');
            $table->timestamp('discount_applied_at')->nullable()->after('discount_applied_by');
            
            // Indexes
            $table->index('discount_applied_by');
            $table->index('discount_type');
            
            // Foreign key
            $table->foreign('discount_applied_by')
                  ->references('id')
                  ->on('staff')
                  ->onDelete('set null');
        });
        
        // Add constraints
        DB::statement('ALTER TABLE orders ADD CONSTRAINT chk_discount_value_positive CHECK (discount_value IS NULL OR discount_value > 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT chk_discount_amount_positive CHECK (discount_amount IS NULL OR discount_amount >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT chk_discount_consistency CHECK ((discount_type IS NULL AND discount_value IS NULL AND discount_amount IS NULL) OR (discount_type IS NOT NULL AND discount_value IS NOT NULL AND discount_amount IS NOT NULL))');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT chk_discount_applied_by CHECK ((discount_type IS NULL AND discount_applied_by IS NULL) OR (discount_type IS NOT NULL AND discount_applied_by IS NOT NULL))');
    }
    
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['discount_applied_by']);
            $table->dropIndex(['discount_applied_by']);
            $table->dropIndex(['discount_type']);
            $table->dropColumn([
                'discount_type',
                'discount_value', 
                'discount_amount',
                'discount_applied_by',
                'discount_applied_at'
            ]);
        });
    }
}
```

### Backward Compatibility

- Existing orders without discounts will have NULL values in all discount fields
- All existing functionality continues to work unchanged
- Discount features are additive and don't modify existing order processing

---

## Query Patterns

### Common Discount Queries

```php
// Orders with discounts
Order::whereNotNull('discount_type')->get();

// Orders by staff member who applied discounts
Order::whereNotNull('discount_applied_by')
      ->with('discountAppliedBy')
      ->get();

// Total discount amount for a period
Order::whereNotNull('discount_amount')
      ->whereBetween('created_at', [$start, $end])
      ->sum('discount_amount');

// Discount usage by staff
DB::table('orders')
  ->select('staff.name', DB::raw('COUNT(*) as discount_count'), DB::raw('SUM(discount_amount) as total_discount'))
  ->join('staff', 'orders.discount_applied_by', '=', 'staff.id')
  ->whereNotNull('orders.discount_type')
  ->groupBy('staff.id', 'staff.name')
  ->get();
```

### Performance Considerations

- Index on `discount_applied_by` for staff-based queries
- Index on `discount_type` for filtering discounted orders
- Consider composite index for common reporting queries
- Use proper decimal precision for currency calculations

---

## Testing Strategy

### Unit Tests

```php
// tests/Unit/DiscountServiceTest.php
class DiscountServiceTest extends TestCase
{
    public function test_calculate_fixed_discount()
    {
        $order = Order::factory()->make(['subtotal' => 100]);
        $service = new DiscountService();
        
        $amount = $service->calculateDiscountAmount($order, 'fixed', 10);
        
        $this->assertEquals(10.0, $amount);
    }
    
    public function test_calculate_percentage_discount()
    {
        $order = Order::factory()->make(['subtotal' => 100]);
        $service = new DiscountService();
        
        $amount = $service->calculateDiscountAmount($order, 'percentage', 15);
        
        $this->assertEquals(15.0, $amount);
    }
    
    public function test_validate_fixed_discount_exceeds_subtotal()
    {
        $order = Order::factory()->make(['subtotal' => 50]);
        $service = new DiscountService();
        
        $errors = $service->validateDiscount($order, 'fixed', 60);
        
        $this->assertContains('Fixed discount cannot exceed order subtotal', $errors);
    }
}
```

### Feature Tests

```php
// tests/Feature/DiscountApiTest.php
class DiscountApiTest extends TestCase
{
    public function test_apply_fixed_discount()
    {
        $order = Order::factory()->create(['status' => 'draft', 'subtotal' => 100]);
        
        $response = $this->post("/api/orders/{$order->id}/discount", [
            'discount_type' => 'fixed',
            'discount_value' => 10
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'discount_amount' => 10
        ]);
    }
}
```

---

## Summary

The data model for the order discount system:

1. **Extends existing orders table** with minimal new fields
2. **Maintains data integrity** through database constraints and validation
3. **Supports audit trail** with who/when tracking
4. **Handles state transitions** properly based on order status
5. **Provides clear relationships** for easy querying and reporting
6. **Ensures backward compatibility** with existing orders
7. **Includes comprehensive testing** strategy

The design follows Laravel best practices and integrates seamlessly with the existing order management system.
