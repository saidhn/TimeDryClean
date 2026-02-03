# API Validation Samples: Order Discount System

**Feature**: Order Discount System  
**Version**: 1.0.0  
**Date**: 2026-02-01

---

## API Endpoints

### 1. Apply Discount

**Endpoint**: `POST /api/orders/{order}/discount`  
**Authentication**: Required  
**Authorization**: User must have order editing permissions

#### Request Sample (Fixed Amount)

```http
POST /api/orders/123/discount HTTP/1.1
Host: localhost
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "discount_type": "fixed",
  "discount_value": 10.00
}
```

#### Response Sample (Success)

```json
{
  "success": true,
  "message": "Discount applied successfully",
  "data": {
    "order": {
      "id": 123,
      "user_id": 45,
      "sum_price": 100.00,
      "discount_type": "fixed",
      "discount_value": 10.00,
      "discount_amount": 10.00,
      "discount_applied_by": 1,
      "discount_applied_at": "2026-02-01T20:30:00.000000Z",
      "status": "draft",
      "created_at": "2026-02-01T10:00:00.000000Z",
      "updated_at": "2026-02-01T20:30:00.000000Z"
    }
  }
}
```

#### Request Sample (Percentage)

```http
POST /api/orders/123/discount HTTP/1.1
Host: localhost
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "discount_type": "percentage",
  "discount_value": 15.00
}
```

#### Response Sample (Success - Percentage)

```json
{
  "success": true,
  "message": "Discount applied successfully",
  "data": {
    "order": {
      "id": 123,
      "sum_price": 100.00,
      "discount_type": "percentage",
      "discount_value": 15.00,
      "discount_amount": 15.00,
      "discount_applied_by": 1,
      "discount_applied_at": "2026-02-01T20:30:00.000000Z"
    }
  }
}
```

#### Response Sample (Validation Error)

```json
{
  "success": false,
  "message": "Fixed discount cannot exceed order subtotal"
}
```

**Status Code**: 422 Unprocessable Entity

#### Response Sample (Authorization Error)

```json
{
  "success": false,
  "message": "Unauthorized"
}
```

**Status Code**: 403 Forbidden

---

### 2. Remove Discount

**Endpoint**: `DELETE /api/orders/{order}/discount`  
**Authentication**: Required  
**Authorization**: User must have order editing permissions

#### Request Sample

```http
DELETE /api/orders/123/discount HTTP/1.1
Host: localhost
X-CSRF-TOKEN: {token}
```

#### Response Sample (Success)

```json
{
  "success": true,
  "message": "Discount removed successfully",
  "data": {
    "order": {
      "id": 123,
      "sum_price": 100.00,
      "discount_type": null,
      "discount_value": null,
      "discount_amount": null,
      "discount_applied_by": null,
      "discount_applied_at": null
    }
  }
}
```

#### Response Sample (No Discount Found)

```json
{
  "success": false,
  "message": "No discount found on this order"
}
```

**Status Code**: 404 Not Found

#### Response Sample (Invalid Order Status)

```json
{
  "success": false,
  "message": "Cannot remove discount from this order status"
}
```

**Status Code**: 422 Unprocessable Entity

---

### 3. Validate Discount

**Endpoint**: `POST /api/orders/{order}/discount/validate`  
**Authentication**: Required

#### Request Sample

```http
POST /api/orders/123/discount/validate HTTP/1.1
Host: localhost
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "discount_type": "percentage",
  "discount_value": 20.00
}
```

#### Response Sample (Valid)

```json
{
  "success": true,
  "message": "Discount is valid",
  "data": {
    "valid": true,
    "discount_amount": "20.00",
    "discounted_subtotal": "80.00",
    "new_total": "92.00",
    "savings": "20.00"
  }
}
```

#### Response Sample (Invalid)

```json
{
  "success": false,
  "message": "Discount validation failed",
  "data": {
    "valid": false,
    "errors": [
      "Discounts can only be applied to draft or pending orders",
      "Percentage discount cannot exceed 100%"
    ]
  }
}
```

---

## Manual Validation Procedures

### Test Case 1: Apply Fixed Discount

**Steps**:
1. Use Postman or curl to send POST request to `/api/orders/{order}/discount`
2. Include valid CSRF token and authentication
3. Set `discount_type` to "fixed"
4. Set `discount_value` to 10.00
5. Verify response status is 200
6. Verify response contains updated order with discount fields populated
7. Check database to confirm discount_amount = discount_value
8. Verify discount_applied_by matches authenticated user ID
9. Verify discount_applied_at is current timestamp

**Expected Result**: Discount applied, all fields populated correctly

---

### Test Case 2: Apply Percentage Discount

**Steps**:
1. Send POST request with `discount_type` = "percentage"
2. Set `discount_value` to 15.00
3. Verify discount_amount = sum_price × 0.15
4. Verify calculation rounded to 2 decimal places

**Expected Result**: Percentage calculated correctly

---

### Test Case 3: Validation - Exceeds Subtotal

**Steps**:
1. Create order with sum_price = 50.00
2. Attempt to apply fixed discount of 60.00
3. Verify response status is 422
4. Verify error message returned
5. Check database - no discount should be saved

**Expected Result**: Validation error, no database changes

---

### Test Case 4: Validation - Invalid Order Status

**Steps**:
1. Update order status to "processing"
2. Attempt to apply discount
3. Verify validation error returned
4. Verify discount not applied

**Expected Result**: Status constraint enforced

---

### Test Case 5: Remove Discount

**Steps**:
1. Apply discount to order
2. Send DELETE request to remove discount
3. Verify all discount fields set to NULL
4. Verify order total recalculated
5. Check database confirms NULL values

**Expected Result**: Discount removed cleanly

---

### Test Case 6: Real-Time Validation

**Steps**:
1. Send validation request with valid values
2. Verify preview calculations returned
3. Send validation request with invalid values
4. Verify error array returned
5. Verify no database changes occur during validation

**Expected Result**: Validation provides feedback without persisting data

---

## Performance Benchmarks

### Target Response Times

| Endpoint | Target | Acceptable | Notes |
|----------|--------|------------|-------|
| Apply Discount | <100ms | <200ms | Includes DB write |
| Remove Discount | <100ms | <200ms | Includes DB write |
| Validate Discount | <50ms | <100ms | Read-only operation |

### Load Testing Scenarios

**Scenario 1: Concurrent Discount Applications**
- 10 simultaneous discount applications
- Expected: All succeed without conflicts
- Verify: Database integrity maintained

**Scenario 2: Rapid Validation Requests**
- 50 validation requests in 5 seconds
- Expected: All respond within target time
- Verify: No rate limiting issues

---

## Error Scenarios

### Network Errors

**Test**: Disconnect network during API call  
**Expected**: Frontend displays user-friendly error message  
**Verify**: No partial data saved to database

### Database Errors

**Test**: Simulate database connection failure  
**Expected**: 500 error returned with generic message  
**Verify**: Error logged for debugging

### Invalid Input

**Test**: Send malformed JSON  
**Expected**: 400 Bad Request  
**Verify**: Clear validation error message

---

## Security Validation

### Authentication

- [ ] Unauthenticated requests rejected (401)
- [ ] Invalid CSRF token rejected (419)
- [ ] Expired session handled gracefully

### Authorization

- [ ] Users without order edit permission rejected (403)
- [ ] Users can only modify their own orders (if applicable)
- [ ] Admin users can modify any order

### Input Sanitization

- [ ] SQL injection attempts blocked
- [ ] XSS attempts sanitized
- [ ] Large values handled (max 999999.99)
- [ ] Special characters in values handled

---

## Manual Verification Checklist

- [ ] All endpoints return correct HTTP status codes
- [ ] Response JSON structure matches documentation
- [ ] Database changes persist correctly
- [ ] Rollback occurs on validation failures
- [ ] Error messages are user-friendly
- [ ] Performance targets met
- [ ] Security validations pass
- [ ] CORS headers configured (if needed)

**Verified By**: _________________  
**Date**: _________________

---

## Notes

- Use actual order IDs from test database
- Ensure test orders have appropriate status
- Clear test data between validation runs
- Document any API behavior deviations
- Report performance issues immediately
