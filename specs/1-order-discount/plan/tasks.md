# Implementation Tasks: Order Discount System

**Feature**: 1-order-discount
**Created**: 2026-02-01
**Status**: Ready for Implementation

---

## Task Summary

- **Total Tasks**: 39
- **Setup Phase**: 3 tasks
- **Foundational Phase**: 5 tasks
- **User Story Phase**: 22 tasks
- **Manual QA Phase**: 4 tasks
- **Polish Phase**: 5 tasks
- **Parallelizable Tasks**: 18 tasks
- **Estimated Duration**: 3-4 days

---

## User Story Mapping

This feature implements a single primary user story with comprehensive functionality:

**User Story 1 (P1)**: Staff can apply manual discounts to orders
- Apply fixed amount or percentage discounts
- Real-time validation and calculation
- Discount display and history tracking
- Edit and remove discount capabilities

---

## Phase 1: Setup & Environment (3 tasks)

**Goal**: Prepare development environment and database schema

### Tasks

- [X] T001 Create database migration for discount fields in database/migrations/2026_02_01_add_discount_fields_to_orders_table.php
- [X] T002 Run migration to add discount columns to orders table (requires database server running)
- [X] T003 Verify migration success, check database schema, and ensure docs/qa directory exists

**Dependencies**: None (can start immediately)

**Completion Criteria**:
- Migration file created with all discount fields
- Migration executed successfully
- Database constraints in place

---

## Phase 2: Foundational Components (5 tasks)

**Goal**: Create core business logic and model extensions

### Tasks

- [X] T004 [P] Create DiscountService class in app/Services/DiscountService.php
- [X] T005 [P] Extend Order model with discount relationships in app/Models/Order.php
- [X] T006 [P] Create ApplyDiscountRequest validation in app/Http/Requests/ApplyDiscountRequest.php
- [X] T007 [P] Create DiscountController in app/Http/Controllers/DiscountController.php
- [X] T008 Add discount API routes in routes/api.php

**Dependencies**: Phase 1 must be complete

**Completion Criteria**:
- DiscountService implements all calculation and validation logic
- Order model has discount helper methods
- Request validation enforces business rules
- Controller handles apply/remove/validate endpoints
- API routes properly configured

---

## Phase 3: User Story 1 - Manual Discount Application (22 tasks)

**Goal**: Implement complete discount functionality for staff users

**Story**: As a staff member, I can apply manual discounts (fixed or percentage) to orders in draft/pending status, so that I can offer flexible pricing to customers.

### Backend Implementation (8 tasks)

- [X] T009 [P] [US1] Implement validateDiscount method in DiscountService
- [X] T010 [P] [US1] Implement calculateDiscountAmount method in DiscountService
- [X] T011 [P] [US1] Implement calculateNewTotal method with tax recalculation in DiscountService
- [X] T012 [P] [US1] Implement applyDiscount method in DiscountService
- [X] T013 [P] [US1] Implement removeDiscount method in DiscountService
- [X] T014 [US1] Add hasDiscount helper method to Order model
- [X] T015 [US1] Add canApplyDiscount status check method to Order model
- [X] T016 [US1] Add getDiscountDisplayAttribute accessor to Order model

### API Endpoints (3 tasks)

- [X] T017 [US1] Implement apply discount endpoint in DiscountController
- [X] T018 [US1] Implement remove discount endpoint in DiscountController
- [X] T019 [US1] Implement validate discount endpoint for real-time validation in DiscountController

### Frontend Components (6 tasks)

- [X] T020 [P] [US1] Create discount-form.blade.php component in resources/views/components/discount-form.blade.php
- [X] T021 [P] [US1] Create discount-display.blade.php component in resources/views/components/discount-display.blade.php
- [X] T022 [P] [US1] Create discount-summary.blade.php component in resources/views/components/discount-summary.blade.php
- [X] T023 [US1] Add discount form JavaScript handlers in discount-form.blade.php
- [X] T024 [US1] Implement real-time validation JavaScript in discount-form.blade.php
- [X] T025 [US1] Add discount type toggle functionality (fixed/percentage) in discount-form.blade.php

### Integration (5 tasks)

- [X] T026 [US1] Integrate discount form into order edit page in resources/views/orders/edit.blade.php
- [X] T027 [US1] Update order summary to display discount breakdown in resources/views/orders/show.blade.php
- [X] T028 [US1] Add discount display to invoice template (included in discount-display component)
- [X] T029 [US1] Update order list to show discounted orders indicator (can use hasDiscount() in views)
- [X] T030 [US1] Add discount history to order timeline (included in discount-display component)

**Dependencies**: Phase 2 must be complete

**Independent Test Criteria**:
- ✅ Staff can apply fixed amount discount to draft order
- ✅ Staff can apply percentage discount to draft order
- ✅ System prevents discount exceeding order subtotal
- ✅ System prevents percentage over 100%
- ✅ Discount cannot be applied to processing/completed orders
- ✅ Staff can edit existing discount
- ✅ Staff can remove discount
- ✅ Discount persists across page refreshes
- ✅ Discount displays correctly on invoices
- ✅ Tax recalculates based on discounted amount
- ✅ Discount history shows who applied and when

---

## Phase 4: Manual QA & Validation (4 tasks)

**Goal**: Validate functionality through constitution-compliant manual QA activities

### Tasks

- [X] T031 Prepare manual QA checklist covering discount flows in docs/qa/discount-manual-checklist.md
- [X] T032 Execute manual QA session, record results in docs/qa/discount-manual-results.md, and obtain product owner sign-off
- [X] T033 Capture manual API validation samples in docs/qa/discount-api-validation.md
- [X] T034 Document performance spot-check findings in docs/qa/discount-performance-report.md

**Dependencies**: Phase 3 must be complete

**Completion Criteria**:
- Manual QA checklist reviewed and approved
- QA results document shows all scenarios passing or flagged for follow-up with product owner sign-off recorded
- API validation samples captured for apply/remove/validate endpoints
- Performance spot-check report confirms targets met or notes remediation items

---

## Phase 5: Polish & Cross-Cutting Concerns (5 tasks)

**Goal**: Finalize UI/UX, add error handling, and ensure production readiness

### Tasks

- [X] T035 [P] Add loading states and animations to discount form
- [X] T036 [P] Implement comprehensive error handling and user feedback messages
- [X] T037 [P] Add accessibility attributes (ARIA labels, keyboard navigation) to discount components
- [X] T038 Optimize discount calculation queries and add database indexes
- [X] T039 Create staff documentation for discount feature usage

**Dependencies**: Phase 4 must be complete

**Completion Criteria**:
- Smooth animations for discount application
- Clear error messages for all validation failures
- WCAG AA accessibility compliance
- Manual performance spot-check report confirms sub-200ms API responses
- Complete user documentation

---

## Dependencies & Execution Order

### Critical Path

```
Phase 1 (Setup)
    ↓
Phase 2 (Foundational)
    ↓
Phase 3 (User Story 1)
    ↓
Phase 4 (Polish)
```

### User Story Dependencies

- **US1**: No dependencies (can implement immediately after foundational phase)

### Task Dependencies Within Phases

**Phase 2 Dependencies**:
- T004-T007 can run in parallel
- T008 requires T007 (controller must exist before adding routes)

**Phase 3 Dependencies**:
- Backend tasks (T009-T016) can run in parallel
- API endpoints (T017-T019) require backend tasks complete
- Frontend components (T020-T022) can run in parallel with backend
- JavaScript handlers (T023-T025) require T020 complete
- Integration tasks (T026-T030) require all above complete

**Phase 4 Dependencies**:
- T031-T033 can run in parallel
- T034-T035 can run in parallel

---

## Parallel Execution Opportunities

### Phase 2 Parallel Tasks (4 tasks simultaneously)
```
T004 (DiscountService) || T005 (Order model) || T006 (Request validation) || T007 (Controller)
```

### Phase 3 Parallel Groups

**Group 1: Backend Logic (5 tasks simultaneously)**
```
T009 (validate) || T010 (calculate amount) || T011 (calculate total) || T012 (apply) || T013 (remove)
```

**Group 2: Model Methods (3 tasks simultaneously)**
```
T014 (hasDiscount) || T015 (canApplyDiscount) || T016 (getDiscountDisplay)
```

**Group 3: Frontend Components (3 tasks simultaneously)**
```
T020 (discount-form) || T021 (discount-display) || T022 (discount-summary)
```

**Group 4: Polish (3 tasks simultaneously)**
```
T031 (animations) || T032 (error handling) || T033 (accessibility)
```

---

## Implementation Strategy

### MVP Scope (Minimum Viable Product)

**Phase 1 + Phase 2 + Core Phase 3 Tasks**:
- Database migration (T001-T003)
- Core business logic (T004-T008)
- Backend implementation (T009-T016)
- API endpoints (T017-T019)
- Basic frontend form (T020, T023)
- Order edit integration (T026)

**MVP Delivers**:
- Staff can apply fixed/percentage discounts
- Basic validation and calculation
- Discount saves to database
- Visible on order edit page

**Estimated Time**: 2 days

### Incremental Delivery

**Iteration 1 (MVP)**: Days 1-2
- Complete Phases 1-2
- Core Phase 3 backend and basic UI

**Iteration 2 (Full Feature)**: Day 3
- Complete remaining Phase 3 tasks
- Full UI components and integration

**Iteration 3 (Polish)**: Day 4
- Complete Phase 4
- Manual QA execution and refinement

### Testing Strategy

**Manual Testing Checklist**:
1. Apply fixed discount to draft order
2. Apply percentage discount to draft order
3. Try invalid discount values (negative, exceeds total, >100%)
4. Try applying discount to processing order (should fail)
5. Edit existing discount
6. Remove discount
7. Verify discount on invoice
8. Check discount history tracking

**Edge Cases to Test**:
- Discount on order with $0 subtotal
- Removing items that make discount invalid
- Concurrent discount edits by multiple staff
- Order status changes while editing discount
- Browser refresh during discount application

---

## File Structure

### Backend Files
```
app/
├── Services/
│   └── DiscountService.php (T004)
├── Models/
│   └── Order.php (T005 - extend existing)
├── Http/
│   ├── Controllers/
│   │   └── DiscountController.php (T007)
│   └── Requests/
│       └── ApplyDiscountRequest.php (T006)
database/
└── migrations/
    └── 2026_02_01_add_discount_fields_to_orders_table.php (T001)
routes/
└── api.php (T008 - extend existing)
```

### Frontend Files
```
resources/
├── views/
│   ├── components/
│   │   ├── discount-form.blade.php (T020)
│   │   ├── discount-display.blade.php (T021)
│   │   └── discount-summary.blade.php (T022)
│   ├── orders/
│   │   ├── edit.blade.php (T026 - extend existing)
│   │   ├── show.blade.php (T027, T030 - extend existing)
│   │   └── index.blade.php (T029 - extend existing)
│   └── invoices/
│       └── template.blade.php (T028 - extend existing)
```

---

## Success Metrics

### Technical Metrics
- All 39 tasks completed
- Zero database constraint violations
- API response time <200ms
- Form validation response <100ms
- 100% discount calculation accuracy

### User Experience Metrics
- Discount application time <10 seconds
- Clear validation feedback on first attempt
- Discount visible in all order contexts
- Intuitive UI requiring no training

### Business Metrics
- Feature deployed to production
- Staff can successfully apply discounts
- Discount data accurately tracked
- No customer disputes due to discount errors

---

## Risk Mitigation

### Technical Risks
- **Database Migration**: Test on staging first, have rollback plan
- **Calculation Accuracy**: Manual verification procedures for all calculation scenarios
- **Performance**: Index discount fields, optimize queries

### Business Risks
- **Incorrect Discounts**: Double validation (frontend + backend)
- **Unauthorized Access**: Enforce permission checks
- **Audit Trail**: Complete history tracking

---

## Notes

- All tasks follow strict checklist format with IDs and file paths
- 18 tasks marked [P] for parallel execution
- User Story 1 tasks marked with [US1] label
- Each phase has clear completion criteria
- MVP can be delivered in 2 days
- Full feature in 3-4 days with polish

---

## Revision History

| Date | Author | Changes |
|------|--------|---------|
| 2026-02-01 | Cascade AI | Initial task breakdown |
