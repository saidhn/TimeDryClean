# Manual QA Checklist: Order Discount System

**Feature**: Order Discount System  
**Version**: 1.0.0  
**Date**: 2026-02-01  
**Tester**: _________________  
**Environment**: _________________

---

## Pre-Test Setup

- [ ] Database migration completed successfully
- [ ] All discount fields added to orders table
- [ ] Test orders created in draft/pending status
- [ ] Staff user account with order editing permissions available
- [ ] Browser console open for JavaScript error monitoring

---

## Test Scenarios

### 1. Fixed Amount Discount Application

**Scenario**: Apply a fixed dollar amount discount to a draft order

- [ ] Navigate to order edit page for a draft order
- [ ] Verify discount form is visible
- [ ] Select "Fixed Amount" discount type
- [ ] Enter a valid fixed amount (e.g., $10.00)
- [ ] Verify real-time preview shows correct discount amount
- [ ] Verify preview shows updated subtotal and total
- [ ] Click "Apply Discount" button
- [ ] Verify success message appears
- [ ] Verify page reloads with discount applied
- [ ] Verify discount display component shows correct information
- [ ] **Expected**: Discount applied successfully, order total reduced by exact amount

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 2. Percentage Discount Application

**Scenario**: Apply a percentage-based discount to a draft order

- [ ] Navigate to order edit page for a draft order
- [ ] Select "Percentage" discount type
- [ ] Verify input prefix changes from $ to %
- [ ] Enter a valid percentage (e.g., 15%)
- [ ] Verify real-time preview calculates correct discount amount
- [ ] Verify preview shows percentage and calculated dollar amount
- [ ] Click "Apply Discount" button
- [ ] Verify discount applied successfully
- [ ] **Expected**: Discount amount = subtotal × (percentage / 100)

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 3. Discount Validation - Exceeds Subtotal

**Scenario**: Attempt to apply fixed discount greater than order subtotal

- [ ] Navigate to order with subtotal of $50.00
- [ ] Select "Fixed Amount" discount type
- [ ] Enter $60.00 (exceeds subtotal)
- [ ] Verify validation error appears in real-time
- [ ] Verify error message: "Fixed discount cannot exceed order subtotal"
- [ ] Verify "Apply Discount" button remains enabled but submission fails
- [ ] **Expected**: Validation prevents discount exceeding subtotal

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 4. Discount Validation - Percentage Over 100%

**Scenario**: Attempt to apply percentage discount over 100%

- [ ] Select "Percentage" discount type
- [ ] Enter 150%
- [ ] Verify validation error appears
- [ ] Verify error message: "Percentage discount cannot exceed 100%"
- [ ] **Expected**: Validation prevents percentage over 100%

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 5. Discount Validation - Negative Values

**Scenario**: Attempt to apply negative discount value

- [ ] Enter -10 in discount value field
- [ ] Verify validation error appears
- [ ] Verify error message: "Discount value must be positive"
- [ ] **Expected**: Validation prevents negative values

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 6. Order Status Constraints

**Scenario**: Verify discount can only be applied to draft/pending orders

- [ ] Navigate to order in "processing" status
- [ ] Verify discount form is NOT visible
- [ ] Verify discount display component shows existing discount (if any)
- [ ] Navigate to order in "completed" status
- [ ] Verify discount form is NOT visible
- [ ] Navigate to order in "draft" status
- [ ] Verify discount form IS visible
- [ ] **Expected**: Discount form only appears for draft/pending orders

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 7. Remove Discount

**Scenario**: Remove an existing discount from an order

- [ ] Navigate to order with existing discount
- [ ] Verify "Remove Discount" button is visible
- [ ] Click "Remove Discount" button
- [ ] Verify confirmation dialog appears
- [ ] Confirm removal
- [ ] Verify discount removed successfully
- [ ] Verify order total recalculated without discount
- [ ] **Expected**: Discount removed, totals updated correctly

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 8. Edit Existing Discount

**Scenario**: Modify an existing discount value

- [ ] Navigate to order with existing discount
- [ ] Change discount type or value
- [ ] Verify real-time preview updates
- [ ] Apply new discount
- [ ] Verify old discount replaced with new values
- [ ] Verify discount_applied_at timestamp updated
- [ ] **Expected**: Discount updated successfully

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 9. Discount Display on Order Summary

**Scenario**: Verify discount appears correctly on order show page

- [ ] Navigate to order detail page with discount
- [ ] Verify discount summary component displays
- [ ] Verify subtotal shown
- [ ] Verify discount amount shown with negative sign
- [ ] Verify discounted subtotal calculated correctly
- [ ] Verify tax shown
- [ ] Verify final total correct
- [ ] Verify "You saved $X" message displays
- [ ] **Expected**: All discount information displayed accurately

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 10. Discount History Tracking

**Scenario**: Verify discount application is tracked with user and timestamp

- [ ] Apply discount to order
- [ ] Navigate to order detail page
- [ ] Verify discount display shows "Applied By" with staff name
- [ ] Verify "Applied" timestamp shows correct date/time
- [ ] **Expected**: Audit trail captured correctly

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 11. Real-Time Validation

**Scenario**: Verify real-time validation provides immediate feedback

- [ ] Start entering discount value
- [ ] Verify validation occurs after 500ms delay (debounced)
- [ ] Verify preview updates automatically
- [ ] Change discount type
- [ ] Verify preview recalculates immediately
- [ ] **Expected**: Smooth, responsive validation without lag

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 12. Browser Refresh Persistence

**Scenario**: Verify discount persists after page refresh

- [ ] Apply discount to order
- [ ] Refresh browser page (F5)
- [ ] Verify discount still displayed
- [ ] Verify discount values unchanged
- [ ] **Expected**: Discount data persists correctly

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 13. UI/UX Responsiveness

**Scenario**: Verify discount form works on different screen sizes

- [ ] Test on desktop (1920x1080)
- [ ] Test on tablet (768px width)
- [ ] Test on mobile (375px width)
- [ ] Verify form layout adapts appropriately
- [ ] Verify buttons remain accessible
- [ ] Verify input fields usable on touch devices
- [ ] **Expected**: Responsive design works across all breakpoints

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 14. Keyboard Navigation

**Scenario**: Verify form is accessible via keyboard

- [ ] Tab through all form fields
- [ ] Verify tab order is logical
- [ ] Use arrow keys to select discount type radio buttons
- [ ] Press Enter to submit form
- [ ] Press Escape to cancel (if applicable)
- [ ] **Expected**: Full keyboard accessibility

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

### 15. Error Handling

**Scenario**: Verify graceful error handling for API failures

- [ ] Simulate network error (disconnect network)
- [ ] Attempt to apply discount
- [ ] Verify user-friendly error message appears
- [ ] Verify no JavaScript console errors
- [ ] Reconnect network
- [ ] Retry discount application
- [ ] **Expected**: Graceful degradation with clear error messages

**Result**: ☐ Pass ☐ Fail  
**Notes**: _________________

---

## Summary

**Total Tests**: 15  
**Passed**: _____  
**Failed**: _____  
**Pass Rate**: _____%

**Critical Issues Found**: _________________

**Recommendations**: _________________

**Sign-off**:
- Tester: _________________ Date: _________
- Product Owner: _________________ Date: _________

---

## Notes

- All tests must pass before deployment to production
- Failed tests must be documented with screenshots and steps to reproduce
- Critical issues must be resolved before proceeding
- Product owner sign-off required for deployment approval
