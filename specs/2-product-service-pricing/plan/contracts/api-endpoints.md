# API Contracts: Product-Specific Service Pricing

## Product Service Prices API

### GET /api/products/{product}/services

**Purpose**: Get available services and prices for a specific product

**Authentication**: Required (admin, employee, driver)

**Parameters**:
- `product` (path, required) - Product ID

**Response**:
```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "product_name": "Bisht",
    "services": [
      {
        "id": 1,
        "name": "Dry Cleaning",
        "price": "5.000"
      },
      {
        "id": 2,
        "name": "Ironing", 
        "price": "2.000"
      }
    ]
  }
}
```

**Error Responses**:
- `404` - Product not found
- `403` - Unauthorized access

---

## Product Management API

### POST /api/products

**Purpose**: Create new product with service prices

**Authentication**: Required (admin, employee)

**Request Body**:
```json
{
  "name": "Abaya",
  "image": "base64_encoded_image_or_file_upload",
  "services": {
    "1": {
      "enabled": true,
      "price": "4.000"
    },
    "2": {
      "enabled": true,
      "price": "1.500"
    },
    "3": {
      "enabled": false
    }
  }
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Abaya",
    "image_path": "products/abaya_image.jpg",
    "service_prices": [
      {
        "service_id": 1,
        "service_name": "Dry Cleaning",
        "price": "4.000"
      },
      {
        "service_id": 2,
        "service_name": "Ironing",
        "price": "1.500"
      }
    ]
  }
}
```

### PUT /api/products/{product}

**Purpose**: Update product and service prices

**Authentication**: Required (admin, employee)

**Request Body**: Same as POST

**Response**: Same as POST with updated data

---

## Order Creation API

### POST /api/orders/{order}/items

**Purpose**: Add product-service combination to order

**Authentication**: Required (admin, employee, driver)

**Request Body**:
```json
{
  "product_id": 1,
  "product_service_id": 1,
  "quantity": 2
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 456,
    "product_id": 1,
    "product_name": "Bisht",
    "product_service_id": 1,
    "service_name": "Dry Cleaning",
    "quantity": 2,
    "price_at_order": "5.000",
    "line_total": "10.000"
  }
}
```

**Validation Errors**:
```json
{
  "success": false,
  "errors": [
    {
      "field": "product_id",
      "message": "Product has no configured services"
    }
  ]
}
```

---

## Validation Rules

### Service Price Validation
- Price must be numeric
- Price must be >= 0
- Price must have maximum 3 decimal places
- At least one service must be enabled for product

### Order Item Validation
- Product must exist
- Service must be configured for product
- Quantity must be positive integer
- Product must have at least one service price configured

---

## Error Response Format

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": [
    {
      "field": "services.1.price",
      "message": "Price is required when service is enabled"
    }
  ]
}
```

---

## Rate Limiting

- Product service price endpoints: 60 requests per minute
- Order creation endpoints: 120 requests per minute
- Authentication required for all endpoints

---

## Data Types

- **Price**: Decimal (8,3) - Maximum 9999.999
- **Quantity**: Integer - Positive values only
- **IDs**: Integer - Auto-increment primary keys
- **Timestamps**: ISO 8601 format (YYYY-MM-DDTHH:mm:ss.sssZ)
