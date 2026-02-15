# Data Model: Product-Specific Service Pricing

## Entity Relationships

```mermaid
erDiagram
    Product ||--o{ ProductServicePrice : has
    ProductService ||--o{ ProductServicePrice : has
    OrderProductService ||--|| ProductServicePrice : "price_at_order"
    OrderProductService }o--|| Product : belongs_to
    OrderProductService }o--|| ProductService : belongs_to
    Order ||--o{ OrderProductService : has

    Product {
        int id PK
        string name
        string image_path nullable
        timestamps created_at, updated_at
        soft_deletes deleted_at nullable
    }

    ProductService {
        int id PK
        string name
        timestamps created_at, updated_at
        soft_deletes deleted_at nullable
    }

    ProductServicePrice {
        int id PK
        int product_id FK
        int product_service_id FK
        decimal price(8,3)
        timestamps created_at, updated_at
        unique(product_id, product_service_id)
    }

    OrderProductService {
        int id PK
        int order_id FK
        int product_id FK
        int product_service_id FK
        int quantity
        decimal price_at_order(8,3) nullable
        timestamps created_at, updated_at
    }

    Order {
        int id PK
        int user_id FK
        decimal sum_price
        string status
        timestamps created_at, updated_at
    }
```

## Model Definitions

### ProductServicePrice

**Purpose**: Links products to services with specific prices

**Attributes**:
- `id` (bigint, primary) - Auto-increment identifier
- `product_id` (bigint, foreign) - Reference to products.id
- `product_service_id` (bigint, foreign) - Reference to product_services.id  
- `price` (decimal 8,3) - Price in KWD with 3 decimal precision
- `created_at` (timestamp) - Creation timestamp
- `updated_at` (timestamp) - Last update timestamp

**Constraints**:
- Unique constraint on (product_id, product_service_id)
- Foreign key constraints with cascade delete
- Price must be >= 0

**Relationships**:
- belongsTo Product
- belongsTo ProductService

### Product (Enhanced)

**New Relationships**:
- `productServicePrices()` - hasMany ProductServicePrice
- `availableServices()` - belongsToMany ProductService via pivot

**New Methods**:
- `hasServicePrices()` - boolean check if product has configured services

### ProductService (Modified)

**Changes**:
- Remove `price` attribute from fillable
- Add `productServicePrices()` hasMany relationship

### OrderProductService (Enhanced)

**New Attributes**:
- `price_at_order` (decimal 8,3, nullable) - Price snapshot at order creation

**New Methods**:
- `getLineTotalAttribute()` - Calculate line total (price_at_order * quantity)

## Validation Rules

### ProductServicePrice
```php
'product_id' => 'required|exists:products,id',
'product_service_id' => 'required|exists:product_services,id',
'price' => 'required|numeric|min:0|max:9999.999',
'product_id,product_service_id' => 'unique:product_service_prices,product_id,product_service_id'
```

### Product (Service Prices)
```php
'services.*.enabled' => 'boolean',
'services.*.price' => 'required_if:services.*.enabled,true|numeric|min:0|max:9999.999'
```

## Migration Strategy

### Phase 1: Create New Structure
1. Create `product_service_prices` table
2. Add `price_at_order` to `order_product_services` table

### Phase 2: Remove Old Structure  
3. Remove `price` column from `product_services` table

### Phase 3: Update Models
4. Update model relationships and attributes
5. Update controllers and views

## Data Flow

### Product Creation
1. User selects services and enters prices
2. Validation ensures at least one service with valid price
3. ProductServicePrice records created for each selected service

### Order Creation
1. User selects product
2. System loads available services for that product
3. User selects service (price displayed)
4. OrderProductService created with price_at_order snapshot

### Price Calculation
- New orders: Use price_at_order from OrderProductService
- Historical orders: Maintain original price_at_order values
- Current pricing: Reference ProductServicePrice for new orders

## Performance Considerations

### Indexes
- Primary key indexes on all tables
- Foreign key indexes for relationship performance
- Unique index on (product_id, product_service_id) in product_service_prices

### Query Optimization
- Eager load product service prices when displaying products
- Cache service lists for dropdown population
- Use database constraints for data integrity

## Security Considerations

### Access Control
- Only admin/employee roles can manage product service prices
- Driver role can only view prices for order creation
- All price modifications require authentication

### Data Validation
- Server-side validation for all price inputs
- Decimal precision enforcement at database level
- Prevent negative prices through constraints
