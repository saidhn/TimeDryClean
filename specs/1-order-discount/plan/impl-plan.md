# Implementation Plan: Order Discount System

**Created**: 2026-02-01
**Feature**: 1-order-discount
**Status**: Draft

---

## Technical Context

### Current System
- **Framework**: Laravel (PHP)
- **Frontend**: Bootstrap 5, jQuery, FontAwesome
- **Database**: MySQL/MariaDB
- **Build**: Vite
- **UI Components**: Custom Blade components with existing design system

### Dependencies
- Existing order management system
- Staff authentication and authorization
- Order calculation logic (subtotal, tax, total)
- Database schema modification capability

### Integration Points
- Order creation/editing workflows
- Order display and invoice generation
- Staff permission system
- Order status management

---

## Constitution Check

### Design Principles
- ✅ **Minimal Library Usage**: Use existing Bootstrap 5, jQuery, FontAwesome only
- ✅ **Simple & Easy to Use**: Intuitive discount interface with clear options
- ✅ **Organized Code**: Component-based approach following existing patterns
- ✅ **Non-Destructive**: Extend existing order system without breaking changes

### Technical Constraints
- ✅ **Bootstrap 5 Base**: Leverage existing design system
- ✅ **jQuery + Vanilla JS**: No new JavaScript frameworks
- ✅ **FontAwesome Icons**: Use existing icon library
- ✅ **Custom CSS Animations**: No external animation libraries

---

## Phase 0: Research & Analysis

### Research Tasks

1. **Order System Integration**
   - Task: Analyze existing order table structure and relationships
   - Task: Review current order calculation logic and tax handling
   - Task: Examine staff permission system and role definitions

2. **UI/UX Patterns**
   - Task: Research discount input patterns in admin interfaces
   - Task: Analyze existing form validation patterns
   - Task: Review current order display layouts

3. **Database Design**
   - Task: Evaluate order table schema for discount fields
   - Task: Research audit trail patterns for discount history
   - Task: Analyze migration strategy for existing orders

### Research Findings

**Decision**: Extend existing order table with discount fields
**Rationale**: Simpler than separate discount table, maintains data locality, easier queries
**Alternatives considered**: Separate discount table (more complex joins), JSON fields (less queryable)

**Decision**: Use existing form validation patterns
**Rationale**: Consistent UX, leverages existing validation infrastructure
**Alternatives considered**: Custom validation (inconsistent), frontend-only validation (less secure)

---

## Phase 1: Design & Contracts

### Data Model

#### Order Entity Extensions
```sql
-- Add to existing orders table
ALTER TABLE orders ADD COLUMN discount_type ENUM('fixed', 'percentage') NULL;
ALTER TABLE orders ADD COLUMN discount_value DECIMAL(10,2) NULL;
ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) NULL;
ALTER TABLE orders ADD COLUMN discount_applied_by INT NULL; -- staff_id
ALTER TABLE orders ADD COLUMN discount_applied_at TIMESTAMP NULL;
ALTER TABLE orders ADD INDEX idx_discount_applied_by (discount_applied_by);
```

#### Validation Rules
- `discount_type`: Required if discount_value is set
- `discount_value`: Must be > 0, ≤ order subtotal for fixed, ≤ 100 for percentage
- `discount_amount`: Calculated field, not user-editable
- `discount_applied_by`: Foreign key to staff table, required if discount applied

#### State Transitions
- Order status must be 'draft' or 'pending' to apply/edit discounts
- Discount can be modified while order remains in eligible status
- Discount becomes read-only when order status changes to 'processing' or higher

### UI Components

#### Discount Input Component
- **Type Selection**: Radio buttons for "Fixed Amount" vs "Percentage"
- **Value Input**: Number input with appropriate formatting
- **Real-time Validation**: Immediate feedback on invalid values
- **Preview**: Show calculated discount amount and new total

#### Discount Display Component
- **Order Summary**: Show discount breakdown in order totals
- **Order History**: Display who applied discount and when
- **Invoice Integration**: Include discount details on printed invoices

### API Contracts

#### Discount Application
```php
// POST /orders/{id}/discount
{
    "discount_type": "fixed|percentage",
    "discount_value": 10.00
}

// Response
{
    "success": true,
    "order": {
        "id": 123,
        "subtotal": 100.00,
        "discount_type": "fixed",
        "discount_value": 10.00,
        "discount_amount": 10.00,
        "tax": 9.00,
        "total": 99.00
    }
}
```

#### Discount Removal
```php
// DELETE /orders/{id}/discount
// Response
{
    "success": true,
    "order": {
        "id": 123,
        "subtotal": 100.00,
        "discount_type": null,
        "discount_value": null,
        "discount_amount": null,
        "tax": 10.00,
        "total": 110.00
    }
}
```

---

## Phase 2: Implementation Strategy

### Component Architecture

#### Frontend Components
1. **DiscountForm Component** (`discount-form.blade.php`)
   - Type selection (radio buttons)
   - Value input with validation
   - Real-time calculation preview
   - Apply/Cancel buttons

2. **DiscountDisplay Component** (`discount-display.blade.php`)
   - Show discount breakdown
   - Edit/Remove actions
   - Applied by/at information

3. **DiscountSummary Component** (`discount-summary.blade.php`)
   - Order totals with discount
   - Used in order details and invoices

#### Backend Components
1. **DiscountService** (`app/Services/DiscountService.php`)
   - Discount calculation logic
   - Validation rules
   - Tax integration

2. **DiscountController** (`app/Http/Controllers/DiscountController.php`)
   - Apply discount endpoint
   - Remove discount endpoint
   - Validation and authorization

3. **Order Model Extensions** (`app/Models/Order.php`)
   - Discount relationships
   - Calculation methods
   - Status validation

### Implementation Phases

#### Phase 2.1: Database & Models (Day 1)
1. Create migration for order table extensions
2. Update Order model with discount relationships
3. Create DiscountService with calculation logic
4. Draft manual QA checklist covering discount calculations and validation scenarios

#### Phase 2.2: Backend API (Day 2)
1. Create DiscountController with apply/remove endpoints
2. Add request validation classes
3. Implement authorization checks
4. Document manual API validation procedures (request/response samples)

#### Phase 2.3: Frontend Components (Day 3)
1. Create discount-form.blade.php component
2. Create discount-display.blade.php component  
3. Create discount-summary.blade.php component
4. Add JavaScript for real-time validation and calculation

#### Phase 2.4: Integration (Day 4)
1. Integrate discount components into order edit pages
2. Update order display to show discounts
3. Update invoice generation
4. Add discount history to order timeline

#### Phase 2.5: Manual QA & Polish (Day 5)
1. Execute manual QA checklist for discount workflows (fixed, percentage, removal)
2. Validate edge cases (invalid values, status changes) using scripted manual scenarios
3. Perform manual performance spot-checks with representative large orders
4. Conduct UI/UX review and accessibility spot-checks (keyboard, screen reader)

---

## Success Metrics

### Technical Metrics
- Discount calculation accuracy: 100%
- API response time: <200ms
- Form validation response: <100ms
- Zero database constraint violations

### User Experience Metrics
- Time to apply discount: <10 seconds
- Form validation clarity: 90% success rate on first attempt
- Discount visibility: Clear display in all order contexts

### Business Metrics
- Discount adoption rate: Target 15% of eligible orders
- Staff satisfaction: >90% rating for ease of use
- Error rate: <1% of discount operations

---

## Risk Mitigation

### Technical Risks
- **Data Integrity**: Use database constraints and comprehensive validation
- **Performance**: Optimize queries, use proper indexing
- **Compatibility**: Maintain backward compatibility with existing orders

### Business Risks
- **Incorrect Discounts**: Double-check calculations, audit trail
- **Unauthorized Use**: Proper role-based access control
- **Customer Disputes**: Clear discount display, full history tracking

---

## Rollout Strategy

### Phase 1: Internal Manual QA Session
- Deploy to staging environment
- Execute manual QA checklist with sample orders
- Validate calculations, edge cases, and authorization flows

### Phase 2: Pilot Release Manual Verification
- Enable for select staff members who complete QA checklist for live data
- Monitor manually logged issues and usage notes
- Collect feedback to augment QA documentation

### Phase 3: Full Release
- Enable for all staff with order editing permissions
- Review manual performance spot-check logs and production metrics
- Provide training and documentation informed by QA findings

---

## Next Steps

1. **Immediate**: Create database migration
2. **Day 1**: Implement DiscountService and Order model extensions
3. **Day 2**: Build DiscountController and API endpoints
4. **Day 3**: Create frontend components
5. **Day 4-5**: Integration, manual QA execution, and deployment

---

**Files to Create**:
- `database/migrations/add_discount_fields_to_orders_table.php`
- `app/Services/DiscountService.php`
- `app/Http/Controllers/DiscountController.php`
- `app/Http/Requests/ApplyDiscountRequest.php`
- `resources/views/components/discount-form.blade.php`
- `resources/views/components/discount-display.blade.php`
- `resources/views/components/discount-summary.blade.php`
- `resources/js/discount-calculator.js`
- `docs/qa/discount-manual-checklist.md`
- `docs/qa/discount-manual-results.md`
- `docs/qa/discount-api-validation.md`
- `docs/qa/discount-performance-report.md`
