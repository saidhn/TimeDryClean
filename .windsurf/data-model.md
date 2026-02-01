# UI/UX Data Model - TimeDryClean

## Overview

This document defines the data structures and interfaces needed for the UI/UX enhancements. **No database schema changes are required** - all data structures are for frontend presentation and API communication only.

## UI State Management

### 1. Dashboard State

```typescript
interface DashboardState {
  user: {
    id: number;
    name: string;
    type: 'admin' | 'client' | 'driver' | 'employee';
    balance?: number;
  };
  stats: {
    totalOrders: number;
    activeOrders: number;
    revenue: number;
    pendingDeliveries: number;
    trends: {
      orders: number; // percentage change
      revenue: number; // percentage change
    };
  };
  recentActivity: ActivityItem[];
  charts: {
    orderTrends: ChartData;
    revenueBreakdown: ChartData;
    serviceDistribution: ChartData;
  };
  notifications: Notification[];
  loading: boolean;
  lastUpdated: Date;
}
```

### 2. Order Management State

```typescript
interface OrderState {
  orders: Order[];
  filters: {
    search: string;
    status: OrderStatus[];
    dateRange: {
      start: Date | null;
      end: Date | null;
    };
    user: number | null;
    driver: number | null;
  };
  pagination: {
    current: number;
    total: number;
    perPage: number;
  };
  sorting: {
    field: string;
    direction: 'asc' | 'desc';
  };
  selectedOrders: number[];
  loading: boolean;
  bulkActions: {
    assignDriver: boolean;
    updateStatus: boolean;
    delete: boolean;
  };
}
```

### 3. Form State

```typescript
interface OrderFormState {
  step: 1 | 2 | 3 | 4;
  data: {
    user_id: number | null;
    products: OrderProduct[];
    delivery: DeliveryOptions;
    address: Address;
  };
  validation: {
    [key: string]: string | null;
  };
  pricing: {
    subtotal: number;
    deliveryFee: number;
    total: number;
  };
  loading: boolean;
  saved: boolean;
}
```

## UI Component Data Structures

### 1. Notification System

```typescript
interface Notification {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message: string;
  duration?: number; // auto-dismiss in ms
  persistent?: boolean;
  actions?: NotificationAction[];
  timestamp: Date;
}

interface NotificationAction {
  label: string;
  action: () => void;
  primary?: boolean;
}
```

### 2. Modal System

```typescript
interface ModalState {
  [modalId: string]: {
    open: boolean;
    title?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    closable?: boolean;
    backdrop?: boolean;
    data?: any; // modal-specific data
  };
}
```

### 3. Theme System

```typescript
interface ThemeState {
  mode: 'light' | 'dark' | 'auto';
  primaryColor: string;
  accentColor: string;
  fontSize: 'sm' | 'md' | 'lg';
  reducedMotion: boolean;
  highContrast: boolean;
}
```

## API Data Contracts

### 1. Dashboard API

```typescript
// GET /api/dashboard/stats
interface DashboardStatsResponse {
  totalOrders: number;
  activeOrders: number;
  revenue: string; // formatted currency
  pendingDeliveries: number;
  trends: {
    orders: number;
    revenue: number;
  };
}

// GET /api/dashboard/activity
interface ActivityResponse {
  activities: ActivityItem[];
}

interface ActivityItem {
  id: number;
  type: 'order_created' | 'order_updated' | 'user_registered' | 'payment_received';
  description: string;
  user: string;
  timestamp: string;
  metadata?: any;
}

// GET /api/dashboard/charts
interface ChartsResponse {
  orderTrends: {
    labels: string[];
    data: number[];
  };
  revenueBreakdown: {
    labels: string[];
    data: number[];
  };
  serviceDistribution: {
    labels: string[];
    data: number[];
  };
}
```

### 2. Orders API

```typescript
// GET /api/orders
interface OrdersResponse {
  data: Order[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

interface Order {
  id: number;
  user: {
    id: number;
    name: string;
    mobile: string;
  };
  driver?: {
    id: number;
    name: string;
    mobile: string;
  };
  status: OrderStatus;
  total_price: string;
  created_at: string;
  updated_at: string;
  items: OrderItem[];
  delivery?: OrderDelivery;
}

type OrderStatus = 
  | 'pending'
  | 'confirmed'
  | 'processing'
  | 'ready'
  | 'delivering'
  | 'delivered'
  | 'cancelled';

interface OrderItem {
  id: number;
  product: {
    id: number;
    name: string;
  };
  service: {
    id: number;
    name: string;
    price: string;
  };
  quantity: number;
  subtotal: string;
}

interface OrderDelivery {
  id: number;
  driver: {
    id: number;
    name: string;
  };
  address: Address;
  bring_order: boolean;
  return_order: boolean;
  delivery_price: string;
  status: 'pending' | 'assigned' | 'picked_up' | 'delivered';
}
```

### 3. Users API (for search/select)

```typescript
// GET /api/users/search
interface UserSearchResponse {
  data: User[];
}

interface User {
  id: number;
  name: string;
  mobile: string;
  type: UserType;
  balance?: string;
  created_at: string;
}

type UserType = 'client' | 'driver' | 'employee' | 'admin';
```

### 4. Products & Services API

```typescript
// GET /api/products
interface ProductsResponse {
  data: Product[];
}

interface Product {
  id: number;
  name: string;
  description?: string;
  services: ProductService[];
}

interface ProductService {
  id: number;
  name: string;
  price: string;
  duration?: number; // in minutes
  description?: string;
}
```

## Form Data Structures

### 1. Order Creation Form

```typescript
interface OrderFormData {
  user_id?: number;
  products: OrderProductItem[];
  delivery: {
    bring_order: boolean;
    return_order: boolean;
    driver_id?: number;
    delivery_price: string;
  };
  address: {
    province_id: number;
    city_id: number;
    street: string;
    building: string;
    floor: string;
    apartment_number: string;
  };
}

interface OrderProductItem {
  product_id: number;
  service_id: number;
  quantity: number;
  unit_price: string;
  subtotal: string;
}
```

### 2. User Search Form

```typescript
interface UserSearchForm {
  query: string;
  type: UserType;
  limit: number;
}
```

### 3. Date Range Filter Form

```typescript
interface DateRangeForm {
  start_date: string;
  end_date: string;
  preset?: 'today' | 'week' | 'month' | 'year';
}
```

## Chart Data Structures

### 1. Line Chart Data

```typescript
interface LineChartData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    borderColor: string;
    backgroundColor: string;
    tension?: number;
  }[];
}
```

### 2. Bar Chart Data

```typescript
interface BarChartData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor: string[];
    borderColor?: string[];
  }[];
}
```

### 3. Pie Chart Data

```typescript
interface PieChartData {
  labels: string[];
  datasets: {
    data: number[];
    backgroundColor: string[];
    borderWidth?: number;
    borderColor?: string;
  }[];
}
```

## Validation Rules

### 1. Order Form Validation

```typescript
interface OrderFormValidation {
  user_id: {
    required: boolean;
    message: string;
  };
  products: {
    required: boolean;
    minLength: number;
    message: string;
  };
  delivery: {
    driver_id: {
      required_if: boolean; // required if delivery options selected
      message: string;
    };
    address: {
      required_if: boolean;
      fields: {
        province_id: { required: boolean };
        city_id: { required: boolean };
        street: { required: boolean };
        building: { required: boolean };
      };
    };
  };
}
```

### 2. User Search Validation

```typescript
interface UserSearchValidation {
  query: {
    minLength: number;
    maxLength: number;
    message: string;
  };
  type: {
    required: boolean;
    allowed: UserType[];
    message: string;
  };
}
```

## State Management Patterns

### 1. Alpine.js Store Pattern

```javascript
// stores/dashboard.js
export function dashboard() {
  return {
    state: {
      loading: false,
      stats: null,
      charts: null,
      notifications: []
    },
    
    async fetchStats() {
      this.state.loading = true;
      try {
        const response = await fetch('/api/dashboard/stats');
        this.state.stats = await response.json();
      } finally {
        this.state.loading = false;
      }
    },
    
    addNotification(notification) {
      this.state.notifications.push({
        ...notification,
        id: Date.now().toString(),
        timestamp: new Date()
      });
    },
    
    removeNotification(id) {
      this.state.notifications = this.state.notifications.filter(n => n.id !== id);
    }
  };
}
```

### 2. Component State Pattern

```javascript
// components/order-table.js
export function orderTable() {
  return {
    selectedOrders: [],
    sortField: 'created_at',
    sortDirection: 'desc',
    
    toggleOrderSelection(orderId) {
      const index = this.selectedOrders.indexOf(orderId);
      if (index > -1) {
        this.selectedOrders.splice(index, 1);
      } else {
        this.selectedOrders.push(orderId);
      }
    },
    
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
    },
    
    get sortedOrders() {
      return this.orders.sort((a, b) => {
        const aVal = a[this.sortField];
        const bVal = b[this.sortField];
        const modifier = this.sortDirection === 'asc' ? 1 : -1;
        return aVal > bVal ? modifier : -modifier;
      });
    }
  };
}
```

## Error Handling

### 1. API Error Structure

```typescript
interface ApiError {
  message: string;
  errors?: {
    [field: string]: string[];
  };
  status: number;
  code?: string;
}
```

### 2. Form Error Handling

```typescript
interface FormErrors {
  [fieldName: string]: string | null;
}

interface FormState {
  data: any;
  errors: FormErrors;
  loading: boolean;
  submitted: boolean;
}
```

## Performance Optimization Data

### 1. Caching Strategy

```typescript
interface CacheConfig {
  dashboard: {
    stats: number; // cache duration in seconds
    activity: number;
    charts: number;
  };
  orders: {
    list: number;
    details: number;
  };
  users: {
    search: number;
  };
}
```

### 2. Lazy Loading State

```typescript
interface LazyLoadState {
  [componentId: string]: {
    loaded: boolean;
    loading: boolean;
    error?: string;
    data?: any;
  };
}
```

## Accessibility Data

### 1. ARIA Labels Structure

```typescript
interface AriaLabels {
  buttons: {
    save: string;
    cancel: string;
    delete: string;
    edit: string;
  };
  status: {
    loading: string;
    success: string;
    error: string;
  };
  navigation: {
    menu: string;
    search: string;
    user: string;
  };
}
```

### 2. Focus Management

```typescript
interface FocusState {
  currentElement: HTMLElement | null;
  trapActive: boolean;
  restoreElement: HTMLElement | null;
}
```

## Conclusion

This data model provides the foundation for implementing the UI/UX enhancements while maintaining compatibility with the existing Laravel backend. All structures are designed to be:

1. **Backend Compatible**: Work with existing Laravel API responses
2. **Type Safe**: Clear interfaces for all data structures
3. **Performance Optimized**: Support for caching and lazy loading
4. **Accessible**: Include accessibility-related data structures
5. **Maintainable**: Clear separation of concerns and patterns

The data model supports the phased implementation approach, allowing gradual migration without breaking existing functionality.
