# Implementation Plan: Product-Specific Service Pricing

**Branch**: 2-product-service-pricing  
**Created**: 2026-02-15  
**Focus**: UI/UX Excellence with Bootstrap, Smooth User Experience, Arabic/English Support

---

## Technical Context

### Current System Analysis
- **Framework**: Laravel with Blade templates
- **UI Framework**: Bootstrap 5 with Font Awesome icons
- **Database**: MySQL with Eloquent ORM
- **Localization**: Arabic/English with Laravel's localization system
- **Current Models**: Product, ProductService, OrderProductService
- **Current Views**: Bootstrap-based with responsive design

### Key Technical Decisions
- **Database Migration**: Create new `product_service_prices` table, remove `price` from `product_services`
- **Price Storage**: Store price snapshots in `order_product_services` for historical accuracy
- **Validation**: At order creation time (allow products without services, warn when adding to orders)
- **UI Pattern**: Bootstrap 5 components with Font Awesome icons, smooth transitions, helpful tooltips

---

## Phase 0: Database & Model Changes

### 1. Database Migration
**File**: `database/migrations/YYYY_MM_DD_HHMMSS_create_product_service_prices_table.php`

```php
Schema::create('product_service_prices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_service_id')->constrained()->onDelete('cascade');
    $table->decimal('price', 8, 3); // KWD precision
    $table->timestamps();
    
    $table->unique(['product_id', 'product_service_id']);
});
```

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_remove_price_from_product_services_table.php`

```php
Schema::table('product_services', function (Blueprint $table) {
    $table->dropColumn('price');
});
```

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_price_at_order_to_order_product_services_table.php`

```php
Schema::table('order_product_services', function (Blueprint $table) {
    $table->decimal('price_at_order', 8, 3)->nullable()->after('quantity');
});
```

### 2. Model Updates

**File**: `app/Models/ProductServicePrice.php` (NEW)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductServicePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_service_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:3',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productService()
    {
        return $this->belongsTo(ProductService::class);
    }
}
```

**File**: `app/Models/Product.php` (UPDATED)
```php
// Add new relationship
public function productServicePrices()
{
    return $this->hasMany(ProductServicePrice::class);
}

public function availableServices()
{
    return $this->belongsToMany(ProductService::class, 'product_service_prices')
        ->withPivot('price')
        ->withTimestamps();
}

public function hasServicePrices()
{
    return $this->productServicePrices()->exists();
}
```

**File**: `app/Models/ProductService.php` (UPDATED)
```php
// Remove price from fillable
protected $fillable = [
    'name',
];

// Add new relationship
public function productServicePrices()
{
    return $this->hasMany(ProductServicePrice::class);
}
```

**File**: `app/Models/OrderProductService.php` (UPDATED)
```php
// Add price_at_order to fillable
protected $fillable = [
    'order_id',
    'product_id',
    'product_service_id',
    'quantity',
    'price_at_order',
];

protected $casts = [
    'price_at_order' => 'decimal:3',
];

// Add accessor for price calculation
public function getLineTotalAttribute()
{
    return $this->price_at_order * $this->quantity;
}
```

---

## Phase 1: Product Management UI Enhancement

### 1. Product Creation View Enhancement
**File**: `resources/views/products/create.blade.php`

**Key Features**:
- Dynamic service pricing section with Bootstrap cards
- Real-time price validation with Bootstrap feedback
- Helpful tooltips and icons
- Arabic/English support
- Smooth animations and transitions

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        {{ __('messages.create_product') }}
                    </h5>
                </div>

                <div class="card-body">
                    @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" id="productForm">
                        @csrf

                        <!-- Basic Information Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label fw-bold">
                                        <i class="fas fa-tag me-1"></i>
                                        {{ __('messages.name') }}
                                    </label>
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                           name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                    @error('name')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image" class="form-label fw-bold">
                                        <i class="fas fa-image me-1"></i>
                                        {{ __('messages.image') }}
                                    </label>
                                    <input id="image" type="file" class="form-control @error('image') is-invalid @enderror" 
                                           name="image" accept="image/*">
                                    @error('image')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Service Pricing Section -->
                        <div class="border-top pt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">
                                    <i class="fas fa-dollar-sign me-2"></i>
                                    {{ __('messages.service_pricing') }}
                                </h6>
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                        data-bs-toggle="tooltip" 
                                        title="{{ __('messages.service_pricing_help') }}">
                                    <i class="fas fa-question-circle"></i>
                                </button>
                            </div>

                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('messages.service_pricing_info') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>

                            <div class="row" id="servicePrices">
                                @foreach($services as $service)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 border-light">
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="service_{{ $service->id }}" 
                                                       name="services[{{ $service->id }}][enabled]"
                                                       onchange="toggleServicePrice({{ $service->id }})">
                                                <label class="form-check-label fw-bold" for="service_{{ $service->id }}">
                                                    {{ $service->name }}
                                                </label>
                                            </div>
                                            
                                            <div class="input-group input-group-sm" id="price_group_{{ $service->id }}" style="display: none;">
                                                <span class="input-group-text">KWD</span>
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="price_{{ $service->id }}"
                                                       name="services[{{ $service->id }}][price]"
                                                       step="0.001" 
                                                       min="0" 
                                                       max="9999.999"
                                                       placeholder="0.000">
                                            </div>
                                            
                                            <div class="invalid-feedback" id="error_{{ $service->id }}"></div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="border-top pt-4 mt-4">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    {{ __('messages.cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>
                                    {{ __('messages.create') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Service pricing JavaScript
function toggleServicePrice(serviceId) {
    const checkbox = document.getElementById(`service_${serviceId}`);
    const priceGroup = document.getElementById(`price_group_${serviceId}`);
    const priceInput = document.getElementById(`price_${serviceId}`);
    
    if (checkbox.checked) {
        priceGroup.style.display = 'flex';
        priceInput.setAttribute('required', 'required');
    } else {
        priceGroup.style.display = 'none';
        priceInput.removeAttribute('required');
        priceInput.value = '';
    }
}

// Form validation
document.getElementById('productForm').addEventListener('submit', function(e) {
    const enabledServices = document.querySelectorAll('input[type="checkbox"]:checked');
    
    if (enabledServices.length === 0) {
        e.preventDefault();
        alert('{{ __("messages.at_least_one_service") }}');
        return false;
    }
    
    // Validate prices
    let isValid = true;
    enabledServices.forEach(checkbox => {
        const serviceId = checkbox.id.replace('service_', '');
        const priceInput = document.getElementById(`price_${serviceId}`);
        const errorDiv = document.getElementById(`error_${serviceId}`);
        
        if (!priceInput.value || priceInput.value <= 0) {
            priceInput.classList.add('is-invalid');
            errorDiv.textContent = '{{ __("messages.price_required") }}';
            isValid = false;
        } else {
            priceInput.classList.remove('is-invalid');
            errorDiv.textContent = '';
        }
    });
    
    if (!isValid) {
        e.preventDefault();
    }
});

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});
</script>
@endsection
```

### 2. Product Edit View Enhancement
**File**: `resources/views/products/edit.blade.php`

**Key Features**:
- Pre-filled service prices with current values
- Add/remove service capabilities
- Visual indicators for configured services
- Smooth transitions and animations

### 3. Product Index View Enhancement
**File**: `resources/views/products/index.blade.php`

**Key Features**:
- Service count display with badges
- Visual indicators for products without services
- Quick action buttons with icons
- Enhanced search and filtering

---

## Phase 2: Order Creation UI Enhancement

### 1. Order Creation Service Selection
**File**: `resources/views/orders/create.blade.php` (or relevant order creation view)

**Key Features**:
- Dynamic service dropdown based on product selection
- Real-time price display
- Product without services warning
- Smooth animations and helpful messages

```blade
<!-- Enhanced Product Selection with Service Integration -->
<div class="form-group mb-3">
    <label class="form-label fw-bold">
        <i class="fas fa-box me-1"></i>
        {{ __('messages.select_product') }}
    </label>
    <select class="form-select" id="productSelect" name="product_id" required>
        <option value="">{{ __('messages.choose_product') }}</option>
        @foreach($products as $product)
        <option value="{{ $product->id }}" 
                data-services="{{ $product->productServicePrices()->count() }}">
            {{ $product->name }}
            @if($product->productServicePrices()->count() > 0)
            <span class="badge bg-success ms-2">{{ $product->productServicePrices()->count() }} {{ __('messages.services') }}</span>
            @else
            <span class="badge bg-warning ms-2">{{ __('messages.no_services') }}</span>
            @endif
        </option>
        @endforeach
    </select>
</div>

<!-- Dynamic Service Selection -->
<div class="form-group mb-3" id="serviceGroup" style="display: none;">
    <label class="form-label fw-bold">
        <i class="fas fa-cogs me-1"></i>
        {{ __('messages.select_service') }}
    </label>
    <select class="form-select" id="serviceSelect" name="product_service_id" required>
        <option value="">{{ __('messages.choose_service') }}</option>
    </select>
    <div class="mt-2">
        <small class="text-muted" id="priceDisplay"></small>
    </div>
</div>

<!-- Warning for products without services -->
<div class="alert alert-warning" id="noServicesWarning" style="display: none;">
    <i class="fas fa-exclamation-triangle me-2"></i>
    {{ __('messages.product_no_services_warning') }}
    <button type="button" class="btn btn-sm btn-primary ms-2" 
            onclick="window.open('{{ route("products.edit", ":id") }}', '_blank')">
        <i class="fas fa-edit me-1"></i>
        {{ __('messages.configure_services') }}
    </button>
</div>

<script>
// Dynamic service loading
document.getElementById('productSelect').addEventListener('change', function() {
    const productId = this.value;
    const serviceGroup = document.getElementById('serviceGroup');
    const serviceSelect = document.getElementById('serviceSelect');
    const noServicesWarning = document.getElementById('noServicesWarning');
    
    if (!productId) {
        serviceGroup.style.display = 'none';
        noServicesWarning.style.display = 'none';
        return;
    }
    
    // Fetch product services via AJAX
    fetch(`/api/products/${productId}/services`)
        .then(response => response.json())
        .then(data => {
            serviceSelect.innerHTML = '<option value="">{{ __("messages.choose_service") }}</option>';
            
            if (data.services.length === 0) {
                serviceGroup.style.display = 'none';
                noServicesWarning.style.display = 'block';
                // Update configure button with actual product ID
                const configureBtn = noServicesWarning.querySelector('button');
                configureBtn.onclick = function() {
                    window.open(configureBtn.getAttribute('onclick').replace(':id', productId), '_blank');
                };
            } else {
                noServicesWarning.style.display = 'none';
                serviceGroup.style.display = 'block';
                
                data.services.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service.id;
                    option.textContent = `${service.name} - ${service.price} KWD`;
                    option.dataset.price = service.price;
                    serviceSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
});

// Price display update
document.getElementById('serviceSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const priceDisplay = document.getElementById('priceDisplay');
    
    if (selectedOption.dataset.price) {
        priceDisplay.innerHTML = `<i class="fas fa-tag me-1"></i> {{ __("messages.price") }}: ${selectedOption.dataset.price} KWD`;
    } else {
        priceDisplay.innerHTML = '';
    }
});
</script>
```

---

## Phase 3: Backend Logic Implementation

### 1. ProductController Updates
**File**: `app/Http/Controllers/Product/ProductController.php`

**Key Methods**:
- `store()` - Handle service prices in product creation
- `update()` - Handle service prices in product editing
- `getServicePrices()` - API endpoint for AJAX calls

### 2. API Endpoint for Product Services
**File**: `routes/api.php`

```php
Route::get('/products/{product}/services', [ProductController::class, 'getServicePrices'])
    ->middleware('auth:admin,employee,driver');
```

### 3. OrderController Updates
**File**: `app/Http/Controllers/Order/OrderController.php`

**Key Updates**:
- Store price snapshot when creating order line items
- Validate product has services before adding to order
- Calculate totals using stored price snapshots

---

## Phase 4: Localization & Messages

### 1. English Messages
**File**: `resources/lang/en/messages.php`

```php
// Service Pricing
'service_pricing' => 'Service Pricing',
'service_pricing_help' => 'Configure prices for each service this product will offer',
'service_pricing_info' => 'Select the services this product will offer and set their prices. You can leave services unselected if they don\'t apply to this product.',
'at_least_one_service' => 'Please select at least one service for this product',
'price_required' => 'Price is required and must be greater than 0',
'services' => 'services',
'no_services' => 'No services',
'configure_services' => 'Configure Services',
'product_no_services_warning' => 'This product has no services configured. Please configure services before adding to orders.',
'price' => 'Price',
'create_product' => 'Create Product',
'manage_products' => 'Manage Products',
```

### 2. Arabic Messages
**File**: `resources/lang/ar/messages.php`

```php
// Service Pricing
'service_pricing' => 'تسعير الخدمات',
'service_pricing_help' => 'تكوين الأسعار لكل خدمة سيقدمها هذا المنتج',
'service_pricing_info' => 'اختر الخدمات التي سيقدمها هذا المنتج وقم بتعيين أسعارها. يمكنك ترك الخدمات غير محددة إذا لم تنطبق على هذا المنتج.',
'at_least_one_service' => 'يرجى تحديد خدمة واحدة على الأقل لهذا المنتج',
'price_required' => 'السعر مطلوب ويجب أن يكون أكبر من 0',
'services' => 'خدمات',
'no_services' => 'لا توجد خدمات',
'configure_services' => 'تكوين الخدمات',
'product_no_services_warning' => 'هذا المنتج ليس لديه خدمات مهيأة. يرجى تكوين الخدمات قبل الإضافة إلى الطلبات.',
'price' => 'السعر',
'create_product' => 'إنشاء منتج',
'manage_products' => 'إدارة المنتجات',
```

---

## Phase 5: Testing & Validation

### 1. Manual Testing Checklist
- [ ] Product creation with service prices
- [ ] Product editing service prices
- [ ] Product list shows service count
- [ ] Order creation shows correct services
- [ ] Order calculation uses correct prices
- [ ] Products without services show warning
- [ ] Arabic/English language switching
- [ ] Bootstrap responsive design
- [ ] Form validation and error messages

### 2. Data Integrity Validation
- [ ] Migration runs successfully
- [ ] Existing orders retain pricing
- [ ] Price snapshots stored correctly
- [ ] No data loss during migration

---

## Implementation Priority

1. **High Priority**: Database migrations and model updates
2. **High Priority**: Product creation/editing UI with service pricing
3. **High Priority**: Order creation service selection
4. **Medium Priority**: Product list enhancements
5. **Medium Priority**: Localization messages
6. **Low Priority**: Optional features (bulk pricing, templates)

---

## Success Metrics

- **UI/UX**: Smooth transitions, helpful tooltips, Bootstrap consistency
- **Functionality**: 100% accurate price calculations
- **User Experience**: Staff can create orders with correct prices without manual price lookup
- **System Performance**: Order creation time does not increase by more than 200ms additional overhead
- **Data Integrity**: Zero data loss during migration
