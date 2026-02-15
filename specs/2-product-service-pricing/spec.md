# Feature Specification: Product-Specific Service Pricing

**Created**: 2026-02-15
**Status**: Draft
**Branch**: 2-product-service-pricing

---

## Overview

### Purpose
This feature restructures the pricing model for laundry services to allow different prices for the same service based on the product being serviced. Currently, each service has a single fixed price regardless of the product. This change enables more flexible and accurate pricing where, for example, dry cleaning a Bisht may cost differently than dry cleaning a shirt.

### User Value
- **For Business Owners**: More accurate pricing that reflects the actual cost and complexity of servicing different products
- **For Staff**: Clear visibility of correct prices when creating orders, reducing pricing errors
- **For Customers**: Transparent pricing that reflects the specific product-service combination they're ordering

### Scope

**Included**:
- New pricing structure linking products to services with specific prices
- Product management interface to define service prices when creating/editing products
- Order creation flow updated to show only available services for selected products with their specific prices
- Migration of existing service prices to the new structure

**Excluded**:
- Dynamic pricing based on time, volume, or customer tier
- Bulk pricing or package deals
- Historical price tracking or price change audit logs
- Customer-facing price list or catalog pages

---

## User Scenarios & Testing

### Primary User Flow

**Scenario: Admin/Employee Creates a New Product**
1. Admin navigates to product creation page
2. Admin enters product name and uploads product image
3. Admin sees a list of all available services (e.g., Washing, Dry Cleaning, Ironing)
4. For each service the product will support, admin enters the price
5. Admin can leave services blank if they don't apply to this product
6. Admin saves the product with its service prices

**Scenario: Staff Creates an Order**
1. Staff selects a customer
2. Staff adds a product to the order (e.g., "Bisht")
3. System displays only the services available for that product with their specific prices
4. Staff selects a service (e.g., "Dry Cleaning - 5.000 KWD")
5. Staff enters quantity
6. System calculates line total based on product-specific service price
7. Staff can add more product-service combinations
8. System calculates order total

### Edge Cases

- **Product with no services defined**: System should warn admin that product has no services and cannot be added to orders until services are configured
- **Service price set to zero**: System should allow but display warning that price is 0.000 KWD
- **Editing product service prices**: Changes should apply to new orders only, existing orders retain their original prices through price_at_order snapshots stored at order creation time
- **Deleting a service that has product prices**: System should handle gracefully, either preventing deletion or removing associated product-service prices
- **Product deleted with existing orders**: Existing orders should retain product name and pricing information

### Acceptance Scenarios

**Scenario 1**: Create Product with Service Prices
- **Given**: Admin is on the product creation page
- **When**: Admin enters product name "Abaya" and sets prices: Washing=2.500, Dry Cleaning=4.000, Ironing=1.500
- **Then**: Product is saved with three service price entries in the database

**Scenario 2**: Add Product to Order Shows Correct Services
- **Given**: Product "Bisht" has only Dry Cleaning (5.000) and Ironing (2.000) services defined
- **When**: Staff adds "Bisht" to an order
- **Then**: Service dropdown shows only "Dry Cleaning - 5.000 KWD" and "Ironing - 2.000 KWD"

**Scenario 3**: Order Calculates Correct Price
- **Given**: Product "Dishdasha" has Washing service at 3.000 KWD
- **When**: Staff adds 2 Dishdasha with Washing service
- **Then**: Line total shows 6.000 KWD (3.000 × 2)

**Scenario 4**: Different Products, Same Service, Different Prices
- **Given**: Bisht has Dry Cleaning at 5.000 KWD, Shirt has Dry Cleaning at 2.000 KWD
- **When**: Staff creates order with both items using Dry Cleaning
- **Then**: Bisht line shows 5.000 KWD, Shirt line shows 2.000 KWD

---

## Functional Requirements

### Core Requirements

1. **Product-Service Price Management**
   - Description: System must store and manage prices for each product-service combination
   - Acceptance Criteria: 
     - Database stores product_id, service_id, and price
     - Each product-service combination can have only one active price
     - Prices are stored with appropriate decimal precision for currency

2. **Product Creation with Service Prices**
   - Description: When creating a product, admin must be able to define which services apply and their prices
   - Acceptance Criteria:
     - Product form displays all available services
     - Admin can enter price for each applicable service
     - Admin can leave services blank if not applicable
     - Form validates that prices are non-negative numbers
     - Product can be saved without service prices (validation occurs at order creation)

3. **Product Editing with Service Prices**
   - Description: Admin can update service prices for existing products
   - Acceptance Criteria:
     - Edit form shows current service prices for the product
     - Admin can add new service prices
     - Admin can update existing service prices
     - Admin can remove service prices (set to null/delete)
     - Changes save successfully and reflect immediately

4. **Order Creation Service Selection**
   - Description: When adding a product to an order, only services configured for that product are available
   - Acceptance Criteria:
     - Service dropdown is filtered to show only services with prices for the selected product
     - Each service option displays its price
     - Selecting a service uses the product-specific price for calculations
     - If product has no services configured, system prevents adding product to order and shows warning message

5. **Order Price Calculation**
   - Description: Order line items store price snapshot at creation and calculate totals from stored price
   - Acceptance Criteria:
     - At order creation, price snapshot is stored from ProductServicePrice
     - Line total = stored price_at_order × quantity
     - Order subtotal sums all line totals correctly
     - Existing discount and delivery price logic continues to work
     - Historical orders retain original pricing even if ProductServicePrice changes

6. **Remove Price from ProductService Model**
   - Description: The generic price field on ProductService must be removed as it's no longer used
   - Acceptance Criteria:
     - ProductService model no longer has price field
     - Database migration removes price column from product_services table
     - All references to ProductService price are updated to use ProductServicePrice
     - Existing product service records remain intact (only price column removed)

7. **Data Migration**
   - Description: Database structure must be updated to support the new pricing model
   - Acceptance Criteria:
     - Create ProductServicePrice table with proper structure and relationships
     - Remove price column from product_services table
     - No ProductServicePrice entries are created during migration (manual configuration required)
     - Migration is reversible
     - No data loss occurs during migration (existing order data preserved)

### Optional Requirements

- **Bulk price update**: Allow admin to update prices for multiple products at once
- **Price history**: Track when prices were changed and by whom
- **Price templates**: Copy service prices from one product to another
- **Import/Export**: Bulk import product-service prices via CSV

---

## Success Criteria

### Measurable Outcomes

- **Data Integrity**: 100% of existing orders retain correct pricing information after migration
- **Feature Adoption**: All new products created have at least one service price defined
- **Order Accuracy**: 0% pricing errors in orders created after feature deployment
- **User Efficiency**: Staff can create orders with correct prices without manual price lookup
- **System Performance**: Order creation time does not increase by more than 200ms

---

## Key Entities

### Data Entities

- **ProductServicePrice**: New entity linking products to services with specific prices
  - Attributes: id, product_id, product_service_id, price, timestamps
  - Relationships: belongs to Product, belongs to ProductService

- **Product**: Existing entity (modified relationships)
  - New Relationship: has many ProductServicePrice entries

- **ProductService**: Existing entity (modified)
  - Remove: price attribute
  - New Relationship: has many ProductServicePrice entries

- **OrderProductService**: Existing entity (modified)
  - Add: price_at_order (decimal) - stores price snapshot at order creation
  - Continues to store product_id, product_service_id, quantity
  - Price at order creation is stored, current price calculation references ProductServicePrice

---

## Dependencies & Assumptions

### Dependencies

- Existing Product model and management interface
- Existing ProductService model and data
- Existing Order and OrderProductService models
- Order creation interface and workflow

### Assumptions

- All existing products should support all existing services by default (during migration)
- Current service prices are reasonable defaults for all products
- Staff have permission to see all service prices
- Price changes apply only to new orders (no retroactive pricing)
- Currency precision of 3 decimal places is sufficient (KWD standard)
- Products must have at least one service to be orderable

---

## Constraints

### Technical Constraints

- Must maintain backward compatibility with existing orders
- Database migration must be reversible
- Cannot modify existing order records (price integrity)

### Business Constraints

- Pricing changes should not affect orders already placed
- All products must have at least one service price to be orderable
- Service prices must be non-negative

### Timeline Constraints

- None specified

---

## Clarifications

### Session 2026-02-15

- Q: Migration Data Scope - Should migration create product-service price entries only for existing order combinations, all possible combinations, or none? → A: Create no entries during migration, require full manual configuration
- Q: Order Price Storage - How should order line item prices be stored for historical accuracy? → A: Store price snapshot in OrderProductService at order creation (historical accuracy)
- Q: Validation Timing - When should system validate that a product has at least one service price? → A: At order creation time (allow saving products without services, warn when adding to orders)

---

## Decisions Made

1. **Migration Strategy**: ✅ **Option B - Manual Configuration**
   - Admin must manually configure each product's services
   - Provides more accurate data reflecting actual business offerings
   - Ensures only relevant product-service combinations exist in the system

2. **Product Without Services**: ✅ **Option A - Allow with Warning**
   - System allows saving products without service prices
   - Display warning that product cannot be ordered until services are configured
   - Provides flexibility during setup and data entry

3. **Service Price Display**: ✅ **Option A - Service Count**
   - Product list page shows "X services configured" for each product
   - Simple, clear indication of configuration status
   - No pricing details on list page (available in detail/edit views)

---

## Revision History

| Date | Author | Changes |
|------|--------|---------|
| 2026-02-15 | Cascade AI | Initial specification |
