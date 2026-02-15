# Manual QA Checklist: Product-Specific Service Pricing

**Feature**: Product-Specific Service Pricing  
**Created**: 2026-02-15  
**Status**: Ready for QA

---

## Pre-QA Setup

- [ ] Run `php artisan migrate` to apply database changes
- [ ] Clear application cache: `php artisan cache:clear`
- [ ] Verify storage link exists: `php artisan storage:link`

---

## Phase 1: Database & Models

- [ ] Verify `product_service_prices` table exists with correct columns
- [ ] Verify `price_at_order` column added to `order_product_services`
- [ ] Verify `price` column removed from `product_services`
- [ ] Test ProductServicePrice model relationships work correctly

---

## Phase 2: Product Creation (US1)

### Form Display
- [ ] Product creation form shows service pricing section
- [ ] All available services are listed with toggle switches
- [ ] Price input appears when service is enabled
- [ ] Tooltips display helpful information
- [ ] Form is responsive on mobile devices

### Form Validation
- [ ] Form submits successfully with valid data
- [ ] Price validation prevents negative values
- [ ] Price validation enforces 3 decimal precision
- [ ] Error messages display correctly in English
- [ ] Error messages display correctly in Arabic

### Data Persistence
- [ ] Product is created in database
- [ ] Service prices are saved to `product_service_prices` table
- [ ] Multiple services can be configured for one product

---

## Phase 3: Product Editing (US2)

### Form Display
- [ ] Edit form shows existing service prices pre-filled
- [ ] Configured services show green border/check icon
- [ ] Service count badge shows correct number

### Form Functionality
- [ ] Can add new services to existing product
- [ ] Can update existing service prices
- [ ] Can remove services from product
- [ ] Changes persist after save

---

## Phase 4: Product List (US3)

- [ ] Products list shows "Services" column
- [ ] Products with services show green badge with count
- [ ] Products without services show warning badge
- [ ] Badge text is localized (Arabic/English)

---

## Phase 5: Order Creation (US4)

### Dynamic Service Loading
- [ ] Selecting a product loads its available services via AJAX
- [ ] Service dropdown shows service name and price
- [ ] Products without services show warning message
- [ ] Price display updates when service is selected

### Order Calculation
- [ ] Line total calculates correctly (price × quantity)
- [ ] Order total updates when items change
- [ ] Delivery price adds correctly to total

### Data Persistence
- [ ] Order is created successfully
- [ ] `price_at_order` is stored for each order item
- [ ] Price snapshot matches the price at time of order

---

## Phase 6: Order Display (US5)

- [ ] Order show page displays correct line prices
- [ ] Prices use stored `price_at_order` values
- [ ] Historical orders show original prices (not current)
- [ ] Price format shows 3 decimal places with KWD

---

## Phase 7: Localization

### English
- [ ] All new labels display correctly
- [ ] Error messages are clear and helpful
- [ ] Tooltips provide useful guidance

### Arabic
- [ ] All new labels display correctly in Arabic
- [ ] RTL layout works properly
- [ ] Error messages are properly translated

---

## Phase 8: Edge Cases

- [ ] Product with zero-price service shows warning
- [ ] Editing product service prices doesn't affect existing orders
- [ ] Deleting a product cascades to delete service prices
- [ ] Deleting a service cascades to delete product prices
- [ ] Order creation fails gracefully if product has no services

---

## Performance

- [ ] Product list loads within acceptable time (<2s)
- [ ] Service AJAX loading is responsive (<200ms)
- [ ] Order creation completes within acceptable time

---

## Browser Compatibility

- [ ] Chrome: All features work correctly
- [ ] Firefox: All features work correctly
- [ ] Safari: All features work correctly
- [ ] Mobile browsers: Responsive design works

---

## Sign-off

| Tester | Date | Status | Notes |
|--------|------|--------|-------|
|        |      |        |       |

---

## Known Issues

(Document any issues found during QA)

1. 
2. 
3. 
