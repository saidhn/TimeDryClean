# API Contracts: Order Discount System

**Created**: 2026-02-01
**Feature**: 1-order-discount

---

## Overview

The Order Discount System provides RESTful API endpoints for applying, modifying, and removing discounts from orders. All endpoints follow REST conventions and include proper validation, authorization, and error handling.

---

## Authentication & Authorization

### Requirements
- All endpoints require staff authentication
- Staff must have `order.edit` permission to apply/edit discounts
- Orders must be in `draft` or `pending` status to modify discounts

### Headers
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
Accept: application/json
```

---

## Endpoints

### 1. Apply Discount to Order

**Endpoint**: `POST /api/v1/orders/{order_id}/discount`

**Description**: Apply a new discount to an order or replace existing discount

**Request Body**:
```json
{
    "discount_type": "fixed|percentage",
    "discount_value": 10.00
}
```

**Request Validation**:
```json
{
    "discount_type": {
        "required": true,
        "in": ["fixed", "percentage"]
    },
    "discount_value": {
        "required": true,
        "numeric": true,
        "min": 0.01,
        "max": 999999.99
    }
}
```

**Response**:
```json
{
    "success": true,
    "message": "Discount applied successfully",
    "data": {
        "order": {
            "id": 123,
            "order_number": "ORD-2026-00123",
            "status": "draft",
            "subtotal": "100.00",
            "discount_type": "fixed",
            "discount_value": "10.00",
            "discount_amount": "10.00",
            "tax": "9.00",
            "total": "99.00",
            "discount_applied_by": {
                "id": 45,
                "name": "John Doe",
                "email": "john@laundry.com"
            },
            "discount_applied_at": "2026-02-01T15:30:00Z",
            "created_at": "2026-02-01T14:00:00Z",
            "updated_at": "2026-02-01T15:30:00Z"
        }
    }
}
```

**Error Responses**:

400 Bad Request - Invalid input:
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "discount_value": [
            "Fixed discount cannot exceed order subtotal"
        ]
    }
}
```

403 Forbidden - Insufficient permissions:
```json
{
    "success": false,
    "message": "You do not have permission to apply discounts to this order"
}
```

422 Unprocessable Entity - Business rule violation:
```json
{
    "success": false,
    "message": "Discounts can only be applied to draft or pending orders"
}
```

---

### 2. Remove Discount from Order

**Endpoint**: `DELETE /api/v1/orders/{order_id}/discount`

**Description**: Remove the discount from an order

**Request Body**: None

**Response**:
```json
{
    "success": true,
    "message": "Discount removed successfully",
    "data": {
        "order": {
            "id": 123,
            "order_number": "ORD-2026-00123",
            "status": "draft",
            "subtotal": "100.00",
            "discount_type": null,
            "discount_value": null,
            "discount_amount": null,
            "tax": "10.00",
            "total": "110.00",
            "discount_applied_by": null,
            "discount_applied_at": null,
            "updated_at": "2026-02-01T15:35:00Z"
        }
    }
}
```

**Error Responses**:

404 Not Found - No discount exists:
```json
{
    "success": false,
    "message": "No discount found on this order"
}
```

422 Unprocessable Entity - Order status:
```json
{
    "success": false,
    "message": "Cannot remove discount from orders in 'processing' status or higher"
}
```

---

### 3. Get Order with Discount Details

**Endpoint**: `GET /api/v1/orders/{order_id}`

**Description**: Get order details including discount information

**Response**:
```json
{
    "success": true,
    "data": {
        "order": {
            "id": 123,
            "order_number": "ORD-2026-00123",
            "status": "draft",
            "customer": {
                "id": 78,
                "name": "Jane Smith",
                "phone": "+1-555-0123"
            },
            "items": [
                {
                    "id": 1,
                    "name": "Shirt - Regular",
                    "quantity": 5,
                    "unit_price": "8.00",
                    "total": "40.00"
                },
                {
                    "id": 2,
                    "name": "Pants - Regular",
                    "quantity": 3,
                    "unit_price": "12.00",
                    "total": "36.00"
                }
            ],
            "subtotal": "76.00",
            "discount": {
                "type": "percentage",
                "value": "10.00",
                "amount": "7.60",
                "applied_by": {
                    "id": 45,
                    "name": "John Doe",
                    "email": "john@laundry.com"
                },
                "applied_at": "2026-02-01T15:30:00Z"
            },
            "tax": "6.84",
            "total": "75.24",
            "created_at": "2026-02-01T14:00:00Z",
            "updated_at": "2026-02-01T15:30:00Z"
        }
    }
}
```

---

### 4. Validate Discount (Preview)

**Endpoint**: `POST /api/v1/orders/{order_id}/discount/validate`

**Description**: Validate a discount without applying it (for real-time validation)

**Request Body**:
```json
{
    "discount_type": "percentage",
    "discount_value": 15.00
}
```

**Response**:
```json
{
    "success": true,
    "message": "Discount is valid",
    "data": {
        "valid": true,
        "discount_amount": "11.40",
        "discounted_subtotal": "64.60",
        "new_tax": "5.81",
        "new_total": "70.41",
        "savings": "11.40"
    }
}
```

**Error Response** - Invalid discount:
```json
{
    "success": false,
    "message": "Discount validation failed",
    "data": {
        "valid": false,
        "errors": [
            "Percentage discount cannot exceed 100%"
        ]
    }
}
```

---

### 5. Get Discount History

**Endpoint**: `GET /api/v1/orders/{order_id}/discount/history`

**Description**: Get the history of discount changes for an order

**Response**:
```json
{
    "success": true,
    "data": {
        "history": [
            {
                "action": "applied",
                "discount_type": "fixed",
                "discount_value": "10.00",
                "discount_amount": "10.00",
                "applied_by": {
                    "id": 45,
                    "name": "John Doe"
                },
                "applied_at": "2026-02-01T15:30:00Z",
                "order_total_before": "110.00",
                "order_total_after": "99.00"
            },
            {
                "action": "modified",
                "discount_type": "percentage",
                "discount_value": "15.00",
                "discount_amount": "11.40",
                "applied_by": {
                    "id": 45,
                    "name": "John Doe"
                },
                "applied_at": "2026-02-01T15:45:00Z",
                "order_total_before": "99.00",
                "order_total_after": "70.41"
            }
        ]
    }
}
```

---

## Data Models

### Order Model
```json
{
    "id": "integer",
    "order_number": "string",
    "status": "enum[draft,pending,processing,completed,cancelled]",
    "subtotal": "decimal",
    "discount_type": "enum[fixed,percentage]|null",
    "discount_value": "decimal|null",
    "discount_amount": "decimal|null",
    "tax": "decimal",
    "total": "decimal",
    "discount_applied_by": "object|null",
    "discount_applied_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Staff Model
```json
{
    "id": "integer",
    "name": "string",
    "email": "string"
}
```

### Discount Object
```json
{
    "type": "enum[fixed,percentage]",
    "value": "decimal",
    "amount": "decimal",
    "applied_by": "object",
    "applied_at": "datetime"
}
```

---

## Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `ORDER_NOT_FOUND` | Order does not exist | 404 |
| `ORDER_NOT_EDITABLE` | Order status does not allow discount changes | 422 |
| `INSUFFICIENT_PERMISSIONS` | Staff cannot apply discounts | 403 |
| `INVALID_DISCOUNT_TYPE` | Discount type not supported | 400 |
| `DISCOUNT_VALUE_INVALID` | Discount value fails validation | 400 |
| `DISCOUNT_EXCEEDS_SUBTOTAL` | Fixed discount too large | 400 |
| `PERCENTAGE_TOO_HIGH` | Percentage over 100% | 400 |
| `NO_DISCOUNT_FOUND` | No discount exists to remove | 404 |
| `CALCULATION_ERROR` | Error in discount calculation | 500 |

---

## Rate Limiting

- **Apply/Remove Discount**: 10 requests per minute per staff member
- **Validate Discount**: 30 requests per minute per staff member
- **Get Order**: 100 requests per minute per staff member

---

## Pagination

For list endpoints (if added in future):
```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 150,
        "last_page": 10
    },
    "links": {
        "first": "https://api.example.com/orders?page=1",
        "last": "https://api.example.com/orders?page=10",
        "prev": null,
        "next": "https://api.example.com/orders?page=2"
    }
}
```

---

## Webhook Events (Future)

### Discount Applied
```json
{
    "event": "discount.applied",
    "order_id": 123,
    "discount_type": "fixed",
    "discount_value": "10.00",
    "discount_amount": "10.00",
    "applied_by": {
        "id": 45,
        "name": "John Doe"
    },
    "timestamp": "2026-02-01T15:30:00Z"
}
```

### Discount Removed
```json
{
    "event": "discount.removed",
    "order_id": 123,
    "previous_discount": {
        "type": "fixed",
        "value": "10.00",
        "amount": "10.00"
    },
    "removed_by": {
        "id": 45,
        "name": "John Doe"
    },
    "timestamp": "2026-02-01T15:35:00Z"
}
```

---

## Testing Examples

### Apply Fixed Discount
```bash
curl -X POST "https://api.laundry.com/api/v1/orders/123/discount" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "discount_type": "fixed",
    "discount_value": 10.00
  }'
```

### Apply Percentage Discount
```bash
curl -X POST "https://api.laundry.com/api/v1/orders/123/discount" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "discount_type": "percentage",
    "discount_value": 15.00
  }'
```

### Remove Discount
```bash
curl -X DELETE "https://api.laundry.com/api/v1/orders/123/discount" \
  -H "Authorization: Bearer {token}"
```

### Validate Discount
```bash
curl -X POST "https://api.laundry.com/api/v1/orders/123/discount/validate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "discount_type": "percentage",
    "discount_value": 20.00
  }'
```

---

## Implementation Notes

### Validation Logic
- All business logic validation occurs in the service layer
- Database constraints provide additional data integrity
- Real-time validation uses the validate endpoint

### Performance Considerations
- Use database indexes for discount-related queries
- Cache frequently accessed order calculations
- Optimize discount history queries with proper indexing

### Security
- All endpoints require authentication
- Staff permissions are validated before processing
- Discount amounts are validated server-side
- Audit trail maintained for all discount operations

### Error Handling
- Consistent error response format across all endpoints
- Detailed validation errors for form feedback
- Proper HTTP status codes for different error types
- Logging of all discount operations for audit purposes
