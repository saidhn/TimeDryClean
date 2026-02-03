# Staff Guide: Order Discount System

**Version**: 1.0.0  
**Last Updated**: 2026-02-01  
**For**: Staff Members with Order Editing Permissions

---

## Overview

The Order Discount System allows you to apply manual discounts to customer orders. You can apply either a fixed dollar amount or a percentage-based discount to orders in draft or pending status.

---

## Quick Start

### Applying a Discount

1. Navigate to the order edit page
2. Scroll to the "Apply Discount" section
3. Select discount type (Fixed Amount or Percentage)
4. Enter the discount value
5. Review the preview showing the new total
6. Click "Apply Discount"

**That's it!** The discount is now applied to the order.

---

## Discount Types

### Fixed Amount Discount

A fixed amount discount reduces the order total by a specific dollar amount.

**Example**: 
- Order subtotal: $100.00
- Fixed discount: $10.00
- New total: $90.00 (plus tax)

**When to use**:
- Promotional offers with specific dollar amounts
- Goodwill gestures for service issues
- Employee discounts with set amounts

### Percentage Discount

A percentage discount reduces the order total by a percentage of the subtotal.

**Example**:
- Order subtotal: $100.00
- Percentage discount: 15%
- Discount amount: $15.00
- New total: $85.00 (plus tax)

**When to use**:
- Seasonal sales (e.g., "20% off")
- Loyalty program discounts
- Volume discounts

---

## Step-by-Step Instructions

### 1. Accessing the Discount Form

**Requirements**:
- Order must be in "Draft" or "Pending" status
- You must have order editing permissions

**Steps**:
1. Go to Orders → View All Orders
2. Find the order you want to discount
3. Click "Edit" button
4. Scroll down to the "Apply Discount" section

**Note**: If you don't see the discount form, the order may be in processing, completed, or cancelled status. Discounts can only be applied to draft or pending orders.

---

### 2. Selecting Discount Type

**Fixed Amount**:
1. Click the "Fixed Amount" button
2. The input field will show a "$" prefix
3. Enter the dollar amount (e.g., 10.00)

**Percentage**:
1. Click the "Percentage" button
2. The input field will show a "%" suffix
3. Enter the percentage (e.g., 15)

**Tip**: You can switch between types at any time. The preview will update automatically.

---

### 3. Entering the Discount Value

**For Fixed Amount**:
- Enter the dollar amount without the $ symbol
- Use decimal format (e.g., 10.00, not 10)
- Maximum: $999,999.99

**For Percentage**:
- Enter the percentage without the % symbol
- Use decimal format for precision (e.g., 15.5 for 15.5%)
- Maximum: 100%

**Validation**:
The system will automatically check:
- ✅ Value is positive
- ✅ Fixed amount doesn't exceed order subtotal
- ✅ Percentage doesn't exceed 100%

---

### 4. Reviewing the Preview

As you type, the system shows a real-time preview:

**Preview includes**:
- **Discount Amount**: The calculated discount in dollars
- **New Subtotal**: Order subtotal minus discount
- **New Total**: Final amount including tax
- **Savings**: Amount the customer saves

**Example Preview**:
```
Discount Preview:
Discount Amount: $15.00
New Subtotal: $85.00
New Total: $97.75
Save $15.00
```

**Tip**: Wait for the preview to appear before clicking "Apply Discount" to ensure your discount is calculated correctly.

---

### 5. Applying the Discount

1. Review the preview to confirm the discount is correct
2. Click the "Apply Discount" button
3. Wait for the success message
4. The page will reload showing the applied discount

**Success indicators**:
- Green success message appears
- Discount display shows the applied discount
- Order total reflects the discount

---

### 6. Editing an Existing Discount

To change a discount that's already applied:

1. Navigate to the order edit page
2. The discount form will show current values
3. Change the type or value as needed
4. Click "Apply Discount" to update

**Note**: The new discount replaces the old one completely.

---

### 7. Removing a Discount

To remove a discount from an order:

1. Navigate to the order edit page
2. Click the "Remove Discount" button
3. Confirm the removal in the dialog
4. The discount will be removed and totals recalculated

**Warning**: This action cannot be undone. The order will return to its original total.

---

## Business Rules

### Order Status Requirements

**Discounts CAN be applied to**:
- ✅ Draft orders
- ✅ Pending orders

**Discounts CANNOT be applied to**:
- ❌ Processing orders
- ❌ Shipped orders
- ❌ Completed orders
- ❌ Cancelled orders

**Why?** Once an order moves to processing, it's being fulfilled. Changing the price at that point could cause billing issues.

---

### Discount Limits

**Fixed Amount**:
- Must be positive (greater than $0.01)
- Cannot exceed the order subtotal
- Maximum: $999,999.99

**Percentage**:
- Must be positive (greater than 0%)
- Cannot exceed 100%
- Calculated on order subtotal only

---

### Tax Calculation

**Important**: Discounts are applied to the subtotal BEFORE tax is calculated.

**Example**:
```
Subtotal: $100.00
Discount (10%): -$10.00
Discounted Subtotal: $90.00
Tax (15%): $13.50
Total: $103.50
```

---

## Common Scenarios

### Scenario 1: Promotional Discount

**Situation**: Customer has a coupon for 20% off

**Steps**:
1. Open order in edit mode
2. Select "Percentage"
3. Enter 20
4. Review preview (20% of subtotal)
5. Apply discount

---

### Scenario 2: Price Match

**Situation**: Customer found item cheaper elsewhere, price match of $15

**Steps**:
1. Open order in edit mode
2. Select "Fixed Amount"
3. Enter 15.00
4. Review preview
5. Apply discount

---

### Scenario 3: Service Recovery

**Situation**: Order was delayed, offering $10 goodwill discount

**Steps**:
1. Open order in edit mode
2. Select "Fixed Amount"
3. Enter 10.00
4. Apply discount
5. Add note in order comments explaining reason

---

### Scenario 4: Correcting a Mistake

**Situation**: Applied wrong discount amount

**Steps**:
1. Open order in edit mode
2. Change the discount value to correct amount
3. Apply discount (replaces old discount)

**OR**:
1. Click "Remove Discount"
2. Re-apply correct discount

---

## Troubleshooting

### "Discount exceeds order subtotal"

**Problem**: Trying to apply a fixed discount larger than the order total

**Solution**: 
- Check the order subtotal
- Enter a discount amount less than the subtotal
- Consider using a percentage instead

---

### "Percentage cannot exceed 100%"

**Problem**: Entered a percentage over 100

**Solution**:
- Enter a value between 0 and 100
- For 100% off, enter exactly 100

---

### "Discounts can only be applied to draft or pending orders"

**Problem**: Order is in processing, completed, or cancelled status

**Solution**:
- Discounts cannot be applied to orders past the pending stage
- Contact your supervisor if you need to adjust pricing on a processed order

---

### Discount form not visible

**Problem**: Can't see the discount section on order edit page

**Possible causes**:
1. Order is not in draft/pending status
2. You don't have order editing permissions
3. Order already has a discount (check for discount display instead)

**Solution**:
- Verify order status
- Contact administrator if you need permissions
- Look for discount display component showing existing discount

---

### Preview not updating

**Problem**: Real-time preview doesn't show after entering value

**Solution**:
- Wait 1-2 seconds (preview is debounced)
- Check browser console for errors
- Refresh the page and try again
- Ensure JavaScript is enabled

---

## Best Practices

### 1. Always Review the Preview

Before applying any discount, check the preview to ensure:
- Discount amount is correct
- New total makes sense
- Customer is getting the expected savings

### 2. Document the Reason

Add a note in the order comments explaining why the discount was applied:
- "20% promotional discount - Summer Sale"
- "$10 goodwill discount - delayed delivery"
- "Price match - competitor offer"

### 3. Verify Order Status

Always check the order status before attempting to apply a discount. This saves time and prevents errors.

### 4. Use Appropriate Discount Type

- **Fixed amounts** for specific dollar values
- **Percentages** for proportional discounts

### 5. Double-Check Large Discounts

For discounts over $50 or 25%, consider:
- Getting supervisor approval
- Documenting the authorization
- Verifying customer eligibility

---

## Audit Trail

Every discount application is tracked with:
- **Who applied it**: Your user account
- **When it was applied**: Date and timestamp
- **What was applied**: Type, value, and calculated amount

This information appears in the discount display on the order detail page.

**Why?** This ensures accountability and helps with reporting and customer service inquiries.

---

## Permissions

### Who Can Apply Discounts?

Staff members with "order editing" permissions can apply discounts.

**If you don't have access**:
- Contact your administrator
- Request order editing permissions
- Explain your role and why you need discount access

---

## Frequently Asked Questions

### Can I apply multiple discounts to one order?

No. Only one discount can be active on an order at a time. Applying a new discount replaces the existing one.

### Can I apply a discount after an order is completed?

No. Discounts can only be applied to draft or pending orders. For completed orders, you would need to process a refund separately.

### What happens to the discount if I edit the order items?

The discount remains applied, but you should verify the discount still makes sense with the new order total. You may need to adjust or remove the discount.

### Can customers see who applied the discount?

This depends on your system configuration. Typically, only staff can see the "Applied By" information.

### Is there a limit to how many discounts I can apply per day?

There are no system limits, but your organization may have policies about discount usage. Check with your supervisor.

---

## Getting Help

### Technical Issues

If you encounter technical problems:
1. Refresh the page and try again
2. Clear your browser cache
3. Try a different browser
4. Contact IT support

### Policy Questions

For questions about discount policies:
- Consult your supervisor
- Review company discount guidelines
- Check with the sales manager

### Training

Need additional training on the discount system?
- Request a refresher session
- Review this guide
- Practice on test orders (if available)

---

## Summary Checklist

Before applying a discount, ensure:

- [ ] Order is in draft or pending status
- [ ] You have the authority to apply this discount
- [ ] Discount type is appropriate (fixed vs percentage)
- [ ] Discount value is correct
- [ ] Preview shows expected results
- [ ] You've documented the reason (in order notes)
- [ ] Customer is eligible for this discount

---

## Quick Reference

| Action | Steps |
|--------|-------|
| Apply fixed discount | Select "Fixed Amount" → Enter value → Apply |
| Apply percentage | Select "Percentage" → Enter value → Apply |
| Edit discount | Change value → Apply (replaces old) |
| Remove discount | Click "Remove Discount" → Confirm |
| View discount history | Check order detail page → Discount display |

---

**Questions?** Contact your supervisor or IT support for assistance.

**Document Version**: 1.0.0  
**Last Updated**: 2026-02-01
