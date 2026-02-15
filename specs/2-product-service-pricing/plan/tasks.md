# Tasks: Product-Specific Service Pricing

**Feature**: Product-Specific Service Pricing  
**Branch**: 2-product-service-pricing  
**Created**: 2026-02-15  

---

## Phase 1: Setup & Infrastructure

### Goal
Prepare the development environment and create the foundational database structure for product-specific service pricing.

### Independent Test Criteria
- All database migrations run successfully without errors
- New models are created and relationships work correctly
- Existing functionality remains intact

### Implementation Tasks

- [ ] T001 Create ProductServicePrice model in app/Models/ProductServicePrice.php
- [ ] T002 Create migration for product_service_prices table in database/migrations/YYYY_MM_DD_HHMMSS_create_product_service_prices_table.php
- [ ] T003 Create migration to add price_at_order to order_product_services in database/migrations/YYYY_MM_DD_HHMMSS_add_price_at_order_to_order_product_services_table.php
- [ ] T004 Create migration to remove price from product_services in database/migrations/YYYY_MM_DD_HHMMSS_remove_price_from_product_services_table.php
- [ ] T005 Update Product model with new relationships in app/Models/Product.php
- [ ] T006 Update ProductService model to remove price field in app/Models/ProductService.php
- [ ] T007 Update OrderProductService model with price_at_order in app/Models/OrderProductService.php
- [ ] T008 Run database migrations to create new structure

---

## Phase 2: Foundational Backend Logic

### Goal
Implement the core backend logic for managing product-service prices and API endpoints.

### Independent Test Criteria
- Manual QA verification of ProductServicePrice CRUD operations
- Manual testing of API endpoint returns correct service prices for products
- Manual verification of Product model relationships work correctly
- Manual testing of validation rules enforce data integrity

### Implementation Tasks

- [ ] T009 [P] Add productServicePrices relationship to Product model in app/Models/Product.php
- [ ] T010 [P] Add productServicePrices relationship to ProductService model in app/Models/ProductService.php
- [ ] T011 [P] Add hasServicePrices method to Product model in app/Models/Product.php
- [ ] T012 [P] Add availableServices relationship to Product model in app/Models/Product.php
- [ ] T013 [P] Add lineTotal accessor to OrderProductService model in app/Models/OrderProductService.php
- [ ] T014 Add getServicePrices method to ProductController in app/Http/Controllers/Product/ProductController.php
- [ ] T015 Add API route for product services in routes/api.php
- [ ] T016 Create manual QA checklist for ProductServicePrice CRUD operations

---

## Phase 3: User Story 1 - Product Creation with Service Prices

### Goal
Enable admin/employee to create new products with specific service prices using an enhanced Bootstrap UI.

### Independent Test Criteria
- Admin can create product with multiple service prices
- Form validates at least one service is selected with valid price
- Service prices are saved correctly to database
- UI shows smooth transitions and helpful tooltips
- Arabic/English messages display correctly

### Implementation Tasks

- [ ] T017 Update ProductController store method to handle service prices in app/Http/Controllers/Product/ProductController.php
- [ ] T018 Add service price validation rules to ProductController in app/Http/Controllers/Product/ProductController.php
- [ ] T019 Update ProductController index method to load services for form in app/Http/Controllers/Product/ProductController.php
- [ ] T020 [P] Add English service pricing messages to resources/lang/en/messages.php
- [ ] T021 [P] Add Arabic service pricing messages to resources/lang/ar/messages.php
- [ ] T022 [US1] Enhance product creation view with service pricing UI in resources/views/products/create.blade.php
- [ ] T023 [US1] Add JavaScript for service price toggle functionality in resources/views/products/create.blade.php
- [ ] T024 [US1] Add Bootstrap tooltips and transitions to product creation form in resources/views/products/create.blade.php
- [ ] T025 Test product creation with service prices

---

## Phase 4: User Story 2 - Product Editing with Service Prices

### Goal
Enable admin/employee to edit existing products and update their service prices with pre-filled current values.

### Independent Test Criteria
- Edit form shows current service prices for product
- Admin can add, update, or remove service prices
- Changes save successfully and reflect immediately
- Visual indicators clearly show configured services
- Form validation works correctly

### Implementation Tasks

- [ ] T026 Update ProductController update method to handle service prices in app/Http/Controllers/Product/ProductController.php
- [ ] T027 Update ProductController edit method to load service prices in app/Http/Controllers/Product/ProductController.php
- [ ] T028 [US2] Enhance product edit view with service pricing UI in resources/views/products/edit.blade.php
- [ ] T029 [US2] Add JavaScript for editing service prices with current values in resources/views/products/edit.blade.php
- [ ] T030 [US2] Add visual indicators for configured services in resources/views/products/edit.blade.php
- [ ] T031 Test product editing with service prices

---

## Phase 5: User Story 3 - Product List with Service Count

### Goal
Display product list with service count indicators and visual status for products without services.

### Independent Test Criteria
- Product list shows service count badges for each product
- Visual indicators distinguish products with/without services
- Search and filtering work with new structure
- Responsive design works on all screen sizes

### Implementation Tasks

- [ ] T032 Update ProductController index method to load service counts in app/Http/Controllers/Product/ProductController.php
- [ ] T033 [P] Add service count badge styling to product list in resources/views/products/index.blade.php
- [ ] T034 [US3] Add visual indicators for products without services in resources/views/products/index.blade.php
- [ ] T035 [US3] Update product list to show service status with Bootstrap badges in resources/views/products/index.blade.php
- [ ] T036 Test product list service count display

---

## Phase 6: User Story 4 - Order Creation with Product-Specific Services

### Goal
Enable staff to create orders with dynamic service selection based on product configuration, showing only available services with their specific prices.

### Independent Test Criteria
- Service dropdown shows only services configured for selected product
- Each service option displays its price
- System prevents adding products without configured services
- Price snapshots are stored correctly in order items
- Warning messages guide users to configure products

### Implementation Tasks

- [ ] T037 Update OrderController to validate product services in app/Http/Controllers/Order/OrderController.php
- [ ] T038 Update OrderController to store price snapshots in app/Http/Controllers/Order/OrderController.php
- [ ] T039 [P] Add product no services warning message to resources/lang/en/messages.php
- [ ] T040 [P] Add product no services warning message to resources/lang/ar/messages.php
- [ ] T041 [US4] Enhance order creation view with dynamic service selection in resources/views/orders/create.blade.php
- [ ] T042 [US4] Add JavaScript for dynamic service loading via AJAX in resources/views/orders/create.blade.php
- [ ] T043 [US4] Add price display and service filtering in resources/views/orders/create.blade.php
- [ ] T044 [US4] Add warning for products without services in resources/views/orders/create.blade.php
- [ ] T045 Test order creation with product-specific services

---

## Phase 7: User Story 5 - Order Price Calculation

### Goal
Ensure order calculations use stored price snapshots and maintain historical pricing accuracy.

### Independent Test Criteria
- Order line totals use stored price_at_order values
- Order subtotal sums correctly
- Historical orders retain original pricing
- Discount and delivery logic continues to work
- Price calculations are accurate to 3 decimal places

### Implementation Tasks

- [ ] T046 Update Order model to use price snapshots in calculations in app/Models/Order.php
- [ ] T047 Update OrderProductService to use price_at_order for totals in app/Models/OrderProductService.php
- [ ] T048 [US5] Update order display views to show correct pricing in resources/views/orders/show.blade.php
- [ ] T049 [US5] Test order price calculations with historical data
- [ ] T050 Verify discount and delivery logic works with new pricing

---

## Phase 8: Polish & Cross-Cutting Concerns

### Goal
Finalize the implementation with error handling, performance optimization, and user experience enhancements.

### Independent Test Criteria
- All forms have proper error handling and validation
- Performance meets requirements (<100ms additional overhead)
- UI is responsive and works on all devices
- All Arabic/English translations are complete
- System gracefully handles edge cases

### Implementation Tasks

- [ ] T051 Add comprehensive error handling to ProductController in app/Http/Controllers/Product/ProductController.php
- [ ] T052 Add comprehensive error handling to OrderController in app/Http/Controllers/Order/OrderController.php
- [ ] T053 [P] Optimize database queries with eager loading in ProductController in app/Http/Controllers/Product/ProductController.php
- [ ] T054 [P] Add loading indicators for AJAX requests in resources/views/orders/create.blade.php
- [ ] T055 [P] Add confirmation dialogs for destructive actions in resources/views/products/edit.blade.php
- [ ] T056 Test responsive design on mobile devices
- [ ] T057 Test Arabic/English language switching
- [ ] T058 Test edge cases (zero prices, deleted services, etc.)
- [ ] T059 Perform final integration testing

---

## Dependencies

### Story Completion Order
1. **Phase 1** (Setup) → **Phase 2** (Foundational) → All other phases can proceed in parallel
2. **US1** (Product Creation) → **US2** (Product Editing) - shares controller logic
3. **US3** (Product List) - Independent after Phase 2
4. **US4** (Order Creation) → **US5** (Price Calculation) - depends on order creation changes
5. **Phase 8** (Polish) - Depends on all user stories

### Critical Path
Phase 1 → Phase 2 → US1 → US4 → US5 → Phase 8

---

## Parallel Execution Opportunities

### Within User Stories
- **US1**: Tasks T020-T021 (localization) can be done in parallel with T022-T024 (UI)
- **US2**: UI tasks can be done in parallel with controller updates
- **US4**: Localization tasks T039-T040 can be done in parallel with UI tasks T041-T044
- **Phase 8**: Optimization tasks can be done in parallel with error handling

### Across Stories
- **US3** can be developed in parallel with **US1** and **US2** after Phase 2
- **US5** can start once **US4** controller changes are complete

---

## Implementation Strategy

### MVP (Minimum Viable Product)
**Scope**: Phase 1 + Phase 2 + US1 + US4
**Deliverable**: Basic product creation with service prices and order creation with filtered services

### Incremental Delivery
1. **Week 1**: Database setup and core models (Phase 1-2)
2. **Week 2**: Product management (US1-US2)
3. **Week 3**: Order integration (US4-US5)
4. **Week 4**: Polish and optimization (Phase 8)

### Risk Mitigation
- Database migrations are reversible
- Existing order data is preserved
- Gradual rollout possible by feature flag
- Comprehensive testing at each phase

---

## Task Summary

- **Total Tasks**: 59
- **Setup Tasks**: 8
- **Foundational Tasks**: 8  
- **US1 Tasks**: 9
- **US2 Tasks**: 6
- **US3 Tasks**: 5
- **US4 Tasks**: 9
- **US5 Tasks**: 5
- **Polish Tasks**: 9

**Parallelizable Tasks**: 23 (marked with [P])
**Critical Path Tasks**: 36 (must be done sequentially)

---

## Success Criteria

Each user story is complete when:
- All implementation tasks for the story are done
- Independent test criteria are met
- UI/UX requirements are satisfied (Bootstrap, responsive, Arabic/English)
- No regressions in existing functionality
- Performance requirements are met
