# Manual QA Checklist: Phase 2 - Foundational Backend Logic

**Feature**: Product-Specific Service Pricing  
**Phase**: 2 - Foundational Backend Logic  
**Created**: 2026-02-15

## ProductServicePrice Model CRUD Operations

- [ ] Create a new ProductServicePrice record via tinker or database
- [ ] Verify product_id and product_service_id foreign keys work correctly
- [ ] Verify unique constraint prevents duplicate product-service combinations
- [ ] Read ProductServicePrice records and verify relationships load correctly
- [ ] Update a ProductServicePrice price value
- [ ] Delete a ProductServicePrice record
- [ ] Verify cascade delete works when parent Product is deleted
- [ ] Verify cascade delete works when parent ProductService is deleted

## Product Model Relationships

- [ ] Verify `productServicePrices()` relationship returns correct records
- [ ] Verify `availableServices()` relationship returns services with pivot price
- [ ] Verify `hasServicePrices()` method returns true when prices exist
- [ ] Verify `hasServicePrices()` method returns false when no prices exist

## ProductService Model Relationships

- [ ] Verify `productServicePrices()` relationship returns correct records
- [ ] Verify price field is no longer in fillable array

## OrderProductService Model

- [ ] Verify `price_at_order` field is in fillable array
- [ ] Verify `lineTotal` accessor calculates correctly (price_at_order × quantity)
- [ ] Verify decimal precision is maintained (3 decimal places)

## API Endpoint

- [ ] GET `/api/products/{product}/services` returns correct JSON structure
- [ ] API returns empty services array for products without configured prices
- [ ] API returns correct service names and prices
- [ ] API requires authentication

## Notes

- Run `php artisan migrate` before testing if not already done
- Use `php artisan tinker` for model testing
- Use browser dev tools or Postman for API testing
