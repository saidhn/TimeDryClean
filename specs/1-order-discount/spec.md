# Feature Specification: Order Discount System

**Created**: 2026-02-01
**Status**: Draft
**Branch**: 1-order-discount

---

## Overview

### Purpose
Enable staff to apply discounts to orders manually, supporting both fixed amount and percentage-based discount types to provide pricing flexibility for customers.

### User Value
- Staff can offer promotional discounts or special pricing to customers
- Customers receive transparent pricing with clearly displayed discount amounts
- Business can track discount usage and impact on revenue
- Improves customer satisfaction through flexible pricing options

### Scope

**Included:**
- Manual discount entry by staff during order creation/editing
- Two discount types: fixed amount and percentage
- Discount display on order details and invoices
- Discount amount validation (cannot exceed order total)
- Discount tracking in order history
- Immediate discount persistence upon confirmation
- Discount application limited to draft/pending order statuses

**Excluded:**
- Automated discount rules or coupon codes
- Customer-facing discount application
- Bulk discount operations
- Discount approval workflows
- Promotional campaign management

---

## Clarifications

### Session 2026-02-01
- Q: At what point in the order lifecycle is the discount considered finalized and saved? → A: Save immediately upon discount confirmation
- Q: How should discounts interact with tax calculations? → A: Apply discount first, then calculate tax on discounted amount
- Q: Which staff roles should be able to apply discounts? → A: All staff roles with order editing permissions
- Q: Can discounts be applied to orders in any status, or only specific statuses? → A: Only orders in draft/pending status
- Q: What discount history details should be preserved? → A: Track who, when, and what discount was applied

---

## User Scenarios & Testing

### Primary User Flow

1. Staff member opens an order (new or existing)
2. Staff clicks "Add Discount" button
3. Staff selects discount type (Fixed Amount or Percentage)
4. Staff enters discount value
5. System calculates and displays the discounted total
6. Staff confirms the discount
7. Discount is applied to the order and reflected in the total

### Edge Cases

- **Discount exceeds order total**: System prevents discount from exceeding the order subtotal and shows validation error
- **Zero or negative discount**: System validates that discount value must be positive
- **Percentage over 100%**: System limits percentage discounts to maximum 100%
- **Editing existing discount**: Staff can modify or remove previously applied discount
- **Multiple discounts**: Only one discount can be applied per order (new discount replaces existing)
- **Order with existing items removed**: If items are removed and discount becomes invalid, system recalculates or prompts for adjustment

### Acceptance Scenarios

**Scenario 1**: Apply Fixed Amount Discount
- **Given**: An order with subtotal of $100
- **When**: Staff applies a fixed discount of $10
- **Then**: Order total is reduced to $90, discount is displayed as "$10 off"

**Scenario 2**: Apply Percentage Discount
- **Given**: An order with subtotal of $100
- **When**: Staff applies a 15% discount
- **Then**: Order total is reduced to $85, discount is displayed as "15% off ($15)"

**Scenario 3**: Prevent Invalid Discount
- **Given**: An order with subtotal of $50
- **When**: Staff attempts to apply a fixed discount of $60
- **Then**: System shows error "Discount cannot exceed order total" and prevents application

**Scenario 4**: Remove Discount
- **Given**: An order with an applied discount of $10
- **When**: Staff clicks "Remove Discount"
- **Then**: Order total returns to original amount, discount is removed from order

**Scenario 5**: Edit Existing Discount
- **Given**: An order with a 10% discount applied
- **When**: Staff changes discount to 20%
- **Then**: Order total is recalculated with new discount, previous discount is replaced

---

## Functional Requirements

### Core Requirements

1. **Discount Type Selection**
   - Description: Staff must be able to choose between fixed amount or percentage discount
   - Acceptance Criteria: 
     - UI provides clear selection between "Fixed Amount" and "Percentage"
     - Only one type can be selected at a time
     - Selection is saved with the order

2. **Discount Value Input**
   - Description: Staff can enter the discount value based on selected type
   - Acceptance Criteria:
     - For fixed amount: accepts numeric input with currency format
     - For percentage: accepts numeric input between 0 and 100
     - Input validates in real-time
     - Shows clear error messages for invalid values

3. **Discount Calculation**
   - Description: System automatically calculates discount amount and new total
   - Acceptance Criteria:
     - Fixed discount: subtracts exact amount from subtotal
     - Percentage discount: calculates (subtotal × percentage / 100)
     - Rounds to 2 decimal places
     - Updates total immediately upon value entry

4. **Discount Validation**
   - Description: System prevents invalid discount amounts
   - Acceptance Criteria:
     - Fixed discount cannot exceed order subtotal
     - Percentage cannot exceed 100%
     - Discount value must be positive (> 0)
     - Shows specific error message for each validation failure

5. **Discount Display**
   - Description: Discount is clearly shown on order details
   - Acceptance Criteria:
     - Shows discount type and value
     - Shows calculated discount amount
     - Displays original subtotal, discount, and final total
     - Format: "Discount: 15% ($15.00)" or "Discount: $10.00"

6. **Discount Persistence**
   - Description: Discount is saved with the order
   - Acceptance Criteria:
     - Discount type and value stored in database
     - Discount persists across page refreshes
     - Discount appears on invoices and receipts
     - Discount visible in order history

7. **Discount Modification**
   - Description: Staff can edit or remove applied discounts
   - Acceptance Criteria:
     - "Edit Discount" button available on orders with discounts
     - "Remove Discount" button removes discount and recalculates total
     - Editing discount shows current values pre-filled
     - Changes are saved immediately

8. **Order Total Recalculation**
   - Description: System recalculates totals when discount changes
   - Acceptance Criteria:
     - Adding discount updates total immediately
     - Removing discount restores original total
     - Editing discount recalculates with new values
     - Tax calculated on discounted amount (discount applied before tax)

### Optional Requirements

- **Discount Reason Field**: Allow staff to enter optional reason for discount
- **Discount Authorization**: Require manager approval for discounts above certain threshold
- **Discount Reporting**: Generate reports on discount usage and revenue impact
- **Discount Templates**: Save common discount amounts for quick application

---

## Success Criteria

### Measurable Outcomes

- **Usability**: Manual QA session confirms staff can apply a discount to an order in under 10 seconds across three representative scenarios.
- **Accuracy**: Manual QA verification against prepared calculation worksheets confirms 100% accuracy for fixed and percentage discounts.
- **Reliability**: Manual smoke testing prior to each release confirms discount application succeeds without errors in 10 consecutive trials.
- **User Satisfaction**: Post-release manual survey of staff users yields at least 90% "easy to use" ratings.
- **Adoption**: Manual analytics review after one month confirms discounts applied to at least 15% of eligible orders.
- **Data Integrity**: Manual audit of database exports confirms zero invalid discount amounts are persisted.

---

## Key Entities

### Data Entities

- **Order**: Existing entity that will be extended
  - New attributes: `discount_type` (enum: 'fixed', 'percentage'), `discount_value` (decimal), `discount_amount` (decimal, calculated)
  
- **Discount** (future consideration): potential separate entity to support advanced reporting in later phases; not included in the current scope.

---

## Dependencies & Assumptions

### Dependencies

- Existing order management system
- Order calculation logic (subtotal, tax, total)
- Staff authentication and authorization system
- Database schema modification capability

### Assumptions

- All staff roles with order editing permissions can apply discounts (no approval workflow needed initially)
- Discounts apply to order subtotal before tax
- Only one discount can be active per order
- Discount is applied manually by staff, not by customers
- Currency formatting follows existing system conventions
- Order editing functionality exists and can be extended

---

## Constraints

### Technical Constraints

- Must integrate with existing order calculation logic
- Database must support decimal precision for currency
- UI must work on existing admin interface
- Must maintain backward compatibility with orders without discounts

### Business Constraints

- Discounts cannot result in negative order totals
- Maximum discount is 100% (order cannot be free beyond that)
- Discount feature available only to authenticated staff
- Discount data must be retained for accounting/audit purposes

### Timeline Constraints

- Feature should be implementable within 1-2 weeks
- No impact on existing order processing workflows

---

## Open Questions

None at this time. Specification is complete based on provided requirements.

---

## Revision History

| Date | Author | Changes |
|------|--------|---------|
| 2026-02-01 | Cascade AI | Initial specification |
