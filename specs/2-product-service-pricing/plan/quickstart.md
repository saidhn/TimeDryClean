# Quick Start Guide: Product-Specific Service Pricing

## Overview

This guide helps developers quickly understand and implement the product-specific service pricing feature. The feature allows different prices for the same service based on the product being serviced.

## Key Concepts

### Before (Current System)
- Each service has one fixed price
- All products use the same service price
- Example: Dry cleaning always costs 3.000 KWD

### After (New System)
- Each product can have different prices for each service
- Prices are product-specific
- Example: Bisht dry cleaning = 5.000 KWD, Shirt dry cleaning = 2.000 KWD

## Database Changes

### New Table: `product_service_prices`
```sql
CREATE TABLE product_service_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    product_service_id BIGINT NOT NULL,
    price DECIMAL(8,3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_service (product_id, product_service_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_service_id) REFERENCES product_services(id) ON DELETE CASCADE
);
```

### Modified Tables
- `product_services`: Remove `price` column
- `order_product_services`: Add `price_at_order` column

## Model Relationships

### New Model: `ProductServicePrice`
```php
class ProductServicePrice extends Model
{
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

### Enhanced Models
```php
// Product.php
public function productServicePrices()
{
    return $this->hasMany(ProductServicePrice::class);
}

public function availableServices()
{
    return $this->belongsToMany(ProductService::class, 'product_service_prices')
        ->withPivot('price');
}

// ProductService.php  
public function productServicePrices()
{
    return $this->hasMany(ProductServicePrice::class);
}

// OrderProductService.php
protected $fillable = [
    'order_id',
    'product_id', 
    'product_service_id',
    'quantity',
    'price_at_order', // NEW
];

public function getLineTotalAttribute()
{
    return $this->price_at_order * $this->quantity;
}
```

## Key Implementation Steps

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Update Product Creation Form
Add service pricing section to product creation view:
- Display all available services
- Allow admin to enable/disable services per product
- Set price for each enabled service
- Validate at least one service is selected

### 3. Update Order Creation Flow
- Load product services dynamically via AJAX
- Filter services based on product configuration
- Display prices in service dropdown
- Store price snapshot when creating order items

### 4. Update Price Calculations
- Use `price_at_order` for existing orders
- Use `ProductServicePrice` for new orders
- Maintain historical pricing accuracy

## UI/UX Guidelines

### Bootstrap Components to Use
- **Cards**: For service pricing sections
- **Form Switches**: For enable/disable services
- **Input Groups**: For price input with currency
- **Badges**: For service count indicators
- **Tooltips**: For helpful hints
- **Alerts**: For warnings and info messages

### User Experience Principles
- **Smooth Transitions**: Use Bootstrap animations
- **Clear Visual Hierarchy**: Group related elements
- **Helpful Messages**: Provide context and guidance
- **Error Prevention**: Validate before submission
- **Responsive Design**: Work on all screen sizes

### Icon Usage
- `fas fa-dollar-sign` - Pricing sections
- `fas fa-cogs` - Service selection
- `fas fa-tag` - Price display
- `fas fa-exclamation-triangle` - Warnings
- `fas fa-info-circle` - Help information
- `fas fa-plus-circle` - Create actions
- `fas fa-edit` - Edit actions

## Localization

### English Messages
```php
'service_pricing' => 'Service Pricing',
'at_least_one_service' => 'Please select at least one service',
'product_no_services_warning' => 'This product has no services configured.',
```

### Arabic Messages  
```php
'service_pricing' => 'تسعير الخدمات',
'at_least_one_service' => 'يرجى تحديد خدمة واحدة على الأقل',
'product_no_services_warning' => 'هذا المنتج ليس لديه خدمات مهيأة.',
```

## Common Implementation Patterns

### Service Price Validation
```php
// In ProductController@store
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'services.*.enabled' => 'boolean',
    'services.*.price' => 'required_if:services.*.enabled,true|numeric|min:0'
]);

// Check at least one service enabled
$enabledServices = collect($request->services)->filter(fn($s) => $s['enabled']);
if ($enabledServices->isEmpty()) {
    return back()->withErrors(['services' => 'At least one service is required']);
}
```

### Dynamic Service Loading (AJAX)
```javascript
// When product is selected
fetch(`/api/products/${productId}/services`)
    .then(response => response.json())
    .then(data => {
        // Populate service dropdown with prices
        data.services.forEach(service => {
            const option = new Option(
                `${service.name} - ${service.price} KWD`,
                service.id
            );
            serviceSelect.add(option);
        });
    });
```

### Price Snapshot Storage
```php
// In OrderController@storeItem
$priceAtOrder = ProductServicePrice::where('product_id', $productId)
    ->where('product_service_id', $serviceId)
    ->first()->price;

OrderProductService::create([
    'order_id' => $order->id,
    'product_id' => $productId,
    'product_service_id' => $serviceId,
    'quantity' => $quantity,
    'price_at_order' => $priceAtOrder, // Store snapshot
]);
```

## Testing Checklist

### Manual Testing
- [ ] Create product with service prices
- [ ] Edit product service prices  
- [ ] Create order with product-specific pricing
- [ ] Verify historical orders retain original prices
- [ ] Test products without services warning
- [ ] Test Arabic/English language switching

### Data Validation
- [ ] Migration runs without errors
- [ ] No data loss during migration
- [ ] Price calculations are accurate
- [ ] Form validation works correctly

## Troubleshooting

### Common Issues
1. **Service dropdown empty** - Check product has configured services
2. **Price not displaying** - Verify AJAX endpoint returns data
3. **Migration fails** - Check foreign key constraints
4. **Form validation error** - Ensure at least one service enabled

### Debug Tips
- Check browser console for JavaScript errors
- Verify API responses in Network tab
- Check Laravel logs for backend errors
- Test with different user roles and permissions

## Performance Considerations

- Eager load product service prices: `Product::with('productServicePrices')`
- Cache service lists for dropdowns
- Use database indexes for foreign keys
- Validate on client-side before server submission

## Security Notes

- All price modifications require authentication
- Validate all user inputs server-side
- Use database constraints for data integrity
- Prevent negative prices through validation rules
