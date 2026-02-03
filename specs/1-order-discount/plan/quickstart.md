# Quickstart Guide: Order Discount System

**Created**: 2026-02-01
**Feature**: 1-order-discount

---

## Overview

This guide provides step-by-step instructions for implementing the Order Discount System in the TimeDryClean application. The feature allows staff to apply manual discounts (fixed amount or percentage) to orders during the draft/pending stages.

---

## Prerequisites

### System Requirements
- Laravel 9.x or higher
- MySQL 8.0 or MariaDB 10.3+
- PHP 8.1+
- Node.js 16+ (for frontend build)
- Existing staff authentication system
- Existing order management system

### Permissions
- Database migration permissions
- File system write access
- Staff role with `order.edit` permission for testing

---

## Installation Steps

### Step 1: Database Migration

Run the migration to add discount fields to the orders table:

```bash
php artisan migrate
```

**Expected Output**:
```
Migrating: 2026_02_01_add_discount_fields_to_orders_table
Migrated:  2026_02_01_add_discount_fields_to_orders_table (0.02s)
```

### Step 2: Create Service Class

Create the discount service for business logic:

```bash
php artisan make:service DiscountService
```

File: `app/Services/DiscountService.php`
```php
<?php

namespace App\Services;

use App\Models\Order;
use InvalidArgumentException;

class DiscountService
{
    public function validateDiscount(Order $order, string $type, float $value): array
    {
        $errors = [];
        
        if (!$order->canApplyDiscount()) {
            $errors[] = 'Discounts can only be applied to draft or pending orders';
        }
        
        if ($value <= 0) {
            $errors[] = 'Discount value must be positive';
        }
        
        if ($type === 'fixed' && $value > $order->subtotal) {
            $errors[] = 'Fixed discount cannot exceed order subtotal';
        }
        
        if ($type === 'percentage' && $value > 100) {
            $errors[] = 'Percentage discount cannot exceed 100%';
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
        $taxRate = $order->tax / $order->subtotal;
        $newTax = round($discountedSubtotal * $taxRate, 2);
        
        return round($discountedSubtotal + $newTax, 2);
    }
    
    public function applyDiscount(Order $order, string $type, float $value, int $staffId): Order
    {
        $errors = $this->validateDiscount($order, $type, $value);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }
        
        $discountAmount = $this->calculateDiscountAmount($order, $type, $value);
        $newTotal = $this->calculateNewTotal($order, $discountAmount);
        
        $order->update([
            'discount_type' => $type,
            'discount_value' => $value,
            'discount_amount' => $discountAmount,
            'discount_applied_by' => $staffId,
            'discount_applied_at' => now(),
            'total' => $newTotal,
        ]);
        
        return $order;
    }
    
    public function removeDiscount(Order $order): Order
    {
        if (!$order->canApplyDiscount()) {
            throw new InvalidArgumentException('Cannot remove discount from this order status');
        }
        
        // Recalculate original total
        $originalTotal = $order->subtotal + $order->tax;
        
        $order->update([
            'discount_type' => null,
            'discount_value' => null,
            'discount_amount' => null,
            'discount_applied_by' => null,
            'discount_applied_at' => null,
            'total' => $originalTotal,
        ]);
        
        return $order;
    }
}
```

### Step 3: Update Order Model

Add discount-related methods to the Order model:

File: `app/Models/Order.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_applied_at' => 'datetime',
    ];
    
    public function discountAppliedBy()
    {
        return $this->belongsTo(Staff::class, 'discount_applied_by');
    }
    
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
    
    public function getDiscountDisplayAttribute(): string
    {
        if (!$this->hasDiscount()) {
            return '';
        }
        
        if ($this->discount_type === 'fixed') {
            return "\${$this->discount_value} off";
        }
        
        return "{$this->discount_value}% off (\${$this->discount_amount})";
    }
}
```

### Step 4: Create Controller

Generate the discount controller:

```bash
php artisan make:controller DiscountController
```

File: `app/Http/Controllers/DiscountController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DiscountService;
use App\Http\Requests\ApplyDiscountRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiscountController extends Controller
{
    public function __construct(private DiscountService $discountService)
    {
        $this->middleware('auth:staff');
        $this->middleware('permission:order.edit');
    }
    
    public function apply(ApplyDiscountRequest $request, Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->discountService->applyDiscount(
                $order,
                $request->discount_type,
                $request->discount_value,
                auth('staff')->id()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Discount applied successfully',
                'data' => [
                    'order' => $updatedOrder->load('discountAppliedBy')
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
    
    public function remove(Order $order): JsonResponse
    {
        try {
            if (!$order->hasDiscount()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No discount found on this order'
                ], 404);
            }
            
            $updatedOrder = $this->discountService->removeDiscount($order);
            
            return response()->json([
                'success' => true,
                'message' => 'Discount removed successfully',
                'data' => [
                    'order' => $updatedOrder
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
    
    public function validate(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0.01',
        ]);
        
        $errors = $this->discountService->validateDiscount(
            $order,
            $request->discount_type,
            $request->discount_value
        );
        
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Discount validation failed',
                'data' => [
                    'valid' => false,
                    'errors' => $errors
                ]
            ]);
        }
        
        $discountAmount = $this->discountService->calculateDiscountAmount(
            $order,
            $request->discount_type,
            $request->discount_value
        );
        
        $newTotal = $this->discountService->calculateNewTotal($order, $discountAmount);
        
        return response()->json([
            'success' => true,
            'message' => 'Discount is valid',
            'data' => [
                'valid' => true,
                'discount_amount' => number_format($discountAmount, 2),
                'discounted_subtotal' => number_format($order->subtotal - $discountAmount, 2),
                'new_tax' => number_format($newTotal - ($order->subtotal - $discountAmount), 2),
                'new_total' => number_format($newTotal, 2),
                'savings' => number_format($discountAmount, 2)
            ]
        ]);
    }
}
```

### Step 5: Create Request Validation

```bash
php artisan make:request ApplyDiscountRequest
```

File: `app/Http/Requests/ApplyDiscountRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('staff')->check() && auth('staff')->user()->can('order.edit');
    }
    
    public function rules(): array
    {
        return [
            'discount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'discount_value' => 'required|numeric|min:0.01|max:999999.99',
        ];
    }
    
    public function messages(): array
    {
        return [
            'discount_type.required' => 'Please select a discount type',
            'discount_type.in' => 'Invalid discount type selected',
            'discount_value.required' => 'Please enter a discount value',
            'discount_value.min' => 'Discount value must be greater than 0',
            'discount_value.max' => 'Discount value is too large',
        ];
    }
}
```

### Step 6: Add Routes

File: `routes/api.php`
```php
// Discount routes
Route::middleware(['auth:staff', 'permission:order.edit'])->group(function () {
    Route::post('/orders/{order}/discount', [DiscountController::class, 'apply']);
    Route::delete('/orders/{order}/discount', [DiscountController::class, 'remove']);
    Route::post('/orders/{order}/discount/validate', [DiscountController::class, 'validate']);
});
```

### Step 7: Create Frontend Components

#### Discount Form Component

File: `resources/views/components/discount-form.blade.php`
```php
@props(['order', 'discount' => null])

<div class="discount-form" data-order-id="{{ $order->id }}">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Discount</h6>
            @if($discount)
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDiscount()">
                    <i class="fas fa-trash"></i> Remove
                </button>
            @endif
        </div>
        <div class="card-body">
            @if(!$discount)
                <form id="discountForm">
                    <div class="mb-3">
                        <label class="form-label">Discount Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="discount_type" id="discount_fixed" value="fixed" checked>
                            <label class="btn btn-outline-primary" for="discount_fixed">
                                <i class="fas fa-dollar-sign"></i> Fixed Amount
                            </label>
                            
                            <input type="radio" class="btn-check" name="discount_type" id="discount_percentage" value="percentage">
                            <label class="btn btn-outline-primary" for="discount_percentage">
                                <i class="fas fa-percent"></i> Percentage
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="discount_value" class="form-label">Discount Value</label>
                        <div class="input-group">
                            <span class="input-group-text" id="discount_prefix">$</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="discount_value" 
                                   name="discount_value" 
                                   step="0.01" 
                                   min="0.01" 
                                   max="{{ $order->subtotal }}" 
                                   required>
                            <span class="input-group-text" id="discount_suffix">%</span>
                        </div>
                        <div class="form-text">
                            Maximum: ${{ number_format($order->subtotal, 2) }}
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Apply Discount
                            </button>
                        </div>
                    </div>
                </form>
            @else
                <div class="discount-display">
                    <div class="alert alert-success">
                        <h6 class="alert-heading">
                            <i class="fas fa-tag"></i> Discount Applied
                        </h6>
                        <p class="mb-1">
                            <strong>{{ $discount->discount_display }}</strong>
                        </p>
                        <p class="mb-1">
                            Applied by: {{ $discount->discountAppliedBy->name }}
                        </p>
                        <p class="mb-0">
                            Applied at: {{ $discount->discount_applied_at->format('M j, Y g:i A') }}
                        </p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="editDiscount()">
                            <i class="fas fa-edit"></i> Edit Discount
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const discountForm = document.getElementById('discountForm');
    if (discountForm) {
        discountForm.addEventListener('submit', handleDiscountSubmit);
        
        // Toggle input prefix/suffix based on discount type
        document.querySelectorAll('input[name="discount_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const prefix = document.getElementById('discount_prefix');
                const suffix = document.getElementById('discount_suffix');
                const input = document.getElementById('discount_value');
                
                if (this.value === 'fixed') {
                    prefix.style.display = 'block';
                    suffix.style.display = 'none';
                    input.max = {{ $order->subtotal }};
                } else {
                    prefix.style.display = 'none';
                    suffix.style.display = 'block';
                    input.max = 100;
                }
            });
        });
        
        // Real-time validation
        document.getElementById('discount_value').addEventListener('input', validateDiscount);
    }
});

function handleDiscountSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        discount_type: formData.get('discount_type'),
        discount_value: parseFloat(formData.get('discount_value'))
    };
    
    // Validate first
    fetch(`/api/orders/{{ $order->id }}/discount/validate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Show preview and confirm
            if (confirm(`Apply discount: ${result.data.savings} savings? New total: $${result.data.new_total}`)) {
                applyDiscount(data);
            }
        } else {
            alert('Invalid discount: ' + result.data.errors.join(', '));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error validating discount');
    });
}

function validateDiscount() {
    const type = document.querySelector('input[name="discount_type"]:checked').value;
    const value = parseFloat(document.getElementById('discount_value').value);
    
    if (!value || value <= 0) return;
    
    const data = { discount_type: type, discount_value: value };
    
    fetch(`/api/orders/{{ $order->id }}/discount/validate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Could show live preview here
            console.log('Valid discount:', result.data);
        }
    })
    .catch(error => console.error('Error:', error));
}

function applyDiscount(data) {
    fetch(`/api/orders/{{ $order->id }}/discount`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload(); // Reload to show updated order
        } else {
            alert('Error applying discount: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error applying discount');
    });
}

function removeDiscount() {
    if (confirm('Remove discount from this order?')) {
        fetch(`/api/orders/{{ $order->id }}/discount`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert('Error removing discount: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing discount');
        });
    }
}

function editDiscount() {
    // Switch to edit mode
    location.reload();
}
</script>
```

### Step 8: Update Order Views

Add the discount component to order edit pages:

File: `resources/views/orders/edit.blade.php`
```php
<!-- Existing order form -->
<div class="row">
    <div class="col-md-8">
        <!-- Order items and details -->
    </div>
    <div class="col-md-4">
        <!-- Order summary -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Order Summary</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                
                @if($order->hasDiscount())
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Discount:</span>
                        <span>-${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax:</span>
                    <span>${{ number_format($order->tax, 2) }}</span>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total:</span>
                    <span>${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>
        
        <!-- Discount form component -->
        @if($order->canApplyDiscount())
            <x-discount-form :order="$order" :discount="$order->hasDiscount() ? $order : null" />
        @endif
    </div>
</div>
```

### Step 9: Run Tests

```bash
# Run unit tests
php artisan test tests/Unit/DiscountServiceTest.php

# Run feature tests
php artisan test tests/Feature/DiscountApiTest.php

# Run all tests
php artisan test
```

---

## Testing

### Manual Testing Steps

1. **Create Test Order**
   - Navigate to order creation page
   - Add items to create an order with subtotal > $0
   - Save order in "draft" status

2. **Apply Fixed Discount**
   - Open order edit page
   - Select "Fixed Amount" discount type
   - Enter valid amount (e.g., $10)
   - Click "Apply Discount"
   - Verify order total is reduced correctly

3. **Apply Percentage Discount**
   - Remove existing discount if any
   - Select "Percentage" discount type
   - Enter valid percentage (e.g., 15)
   - Click "Apply Discount"
   - Verify calculation is correct

4. **Test Validation**
   - Try discount exceeding order subtotal
   - Try percentage over 100%
   - Try negative values
   - Verify error messages appear

5. **Test Order Status Constraints**
   - Change order status to "processing"
   - Try to apply discount - should fail
   - Change back to "draft" - should work again

### Automated Tests

Run the test suite to verify functionality:

```bash
php artisan test --filter=Discount
```

---

## Deployment

### Production Checklist

- [ ] Database migration completed successfully
- [ ] All tests passing
- [ ] Staff permissions configured
- [ ] Frontend assets compiled
- [ ] API endpoints accessible
- [ ] Error monitoring configured

### Rollback Plan

If issues arise, rollback with:

```bash
php artisan migrate:rollback --step=1
```

---

## Troubleshooting

### Common Issues

1. **Migration Fails**
   - Check database permissions
   - Verify orders table exists
   - Check for existing discount columns

2. **Permission Errors**
   - Verify staff has `order.edit` permission
   - Check authentication middleware

3. **Calculation Errors**
   - Verify decimal precision in database
   - Check tax calculation logic
   - Validate input sanitization

4. **Frontend Issues**
   - Check JavaScript console for errors
   - Verify CSRF token is present
   - Check API endpoint URLs

### Debug Mode

Enable debug mode for detailed error information:

```bash
APP_DEBUG=true php artisan serve
```

---

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review database constraints
3. Verify API responses in browser dev tools
4. Test with different user roles

---

## Next Steps

After successful implementation:

1. Monitor discount usage in production
2. Collect staff feedback
3. Consider adding discount reporting
4. Plan for future enhancements (discount reasons, approval workflows)

---

**Estimated Implementation Time**: 2-3 days
**Testing Time**: 1 day
**Total Time**: 3-4 days
