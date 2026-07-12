# Order Workflow Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the security, concurrency, and state-machine gaps found in a full audit of the TimeDryClean (laundry service) Laravel app's order workflow, so the system is production-safe for real money and real drivers.

**Architecture:** No new framework/package is introduced except where noted (Task 9 optionally considers `spatie/laravel-model-states`, but the default path is a small hand-rolled transition-guard service, consistent with this codebase's existing `app/Services` pattern). Fixes are layered: Phase 1 stops active money/security bleeding with minimal-diff patches to existing files; Phase 2 hardens the schema under that code; Phase 3 replaces the fake state machine with a real one; Phase 4 adds the mid-cycle repricing flow; Phase 5 is performance/infra cleanup.

**Tech Stack:** Laravel 11.9, PHP, MySQL (production — code already uses MySQL-only `DATE_FORMAT`; local dev defaults to sqlite per `.env.example`, but assume MySQL semantics for all migrations), PHPUnit (not Pest — no Pest config found), Blade views, no frontend framework.

## Global Constraints

- Target Laravel version: `^11.9` (composer.json). No Doctrine DBAL installed — do **not** use `$table->column()->change()`; use raw `DB::statement(...)` for column-type changes instead.
- Test suite is plain PHPUnit (`phpunit.xml`), test classes extend `Tests\TestCase` which currently has **no** `RefreshDatabase`/`DatabaseTransactions` trait wired in — every Feature test task below must `use RefreshDatabase;` explicitly in the test class.
- `QUEUE_CONNECTION=sync` in the testing environment (`phpunit.xml`), `database` in default `.env` — queued jobs run inline during tests, so `Queue::fake()` must be used explicitly where a test asserts "was dispatched" rather than "ran".
- `database/factories/UserFactory.php` does **not** set `mobile` (unique, NOT NULL) or `user_type`. Every test in this plan that creates a `User`/`Client`/`Driver` must pass `mobile` and `user_type` explicitly to the factory state, e.g. `User::factory()->create(['user_type' => 'client', 'mobile' => '500'.fake()->unique()->numerify('#####'), 'balance' => 100])`.
- Money fields (`balance`, `points_balance`, `sum_price`, etc.) are `decimal` — compare with `bccomp`/small epsilon or cast to string in assertions, not `===` on floats, to avoid float-precision test flakiness.
- All 4 auth guards (`admin`, `employee`, `driver`, `client`) map to **separate provider models** (`Admin`, `Employee`, `Driver`, `Client`) that all `extend User` and share the single `users` table via a global scope on `user_type` (see Context Snapshot). `Auth::guard('driver')->id()` returns the same numeric ID as `users.id` / `order_deliveries.user_id`.
- Do not rename the `orders.status` column or change its existing values without a data migration (Task 9) — it is read in `resources/views/orders/index.blade.php`, `resources/views/orders/show.blade.php`, `resources/views/admin/users/show.blade.php`, `resources/views/client/bills/index.blade.php`, and 6+ controllers.
- Never invent KNET's real signature algorithm — the codebase's own `KnetService::getKnetPaymentUrl()` is an explicit `// TODO: Integrate with KNET PHP SDK` stub; production never talks to real KNET yet. Task 1 below secures the **existing self-hosted test-gateway loop** (which is what actually runs today, `debug` mode) with our own HMAC, and leaves a clearly marked TODO to swap in KNET's real verification method when the SDK is integrated.

---

## Context Snapshot (read once, reference forever — do not re-explore the repo for this)

This section is the complete result of the audit performed before this plan. Every fact below was confirmed by reading the actual files at the paths given. Treat this as ground truth for planning; re-verify only the specific lines you are about to edit.

### Directory layout (relevant parts)
```
app/Enums/            OrderStatus.php, DeliveryStatus.php, DeliveryDirection.php, UserType.php
app/Models/            Order.php, OrderDelivery.php, OrderProductService.php, Payment.php,
                       User.php, Driver.php, Client.php, Admin.php, Employee.php,
                       ProductServicePrice.php, ProductService.php, Product.php,
                       Discount.php, DiscountFreeProduct.php, Subscription.php, ClientSubscription.php,
                       PointsPackage.php, UserPointsPackage.php, NotificationTemplate.php,
                       Address.php, City.php, Province.php, Contact.php, Advertiser.php
app/Http/Controllers/
  Order/OrdersController.php, Order/OrderAssignmentController.php
  Driver/DriverController.php, Driver/DriverDashboardController.php
  Client/PaymentController.php, Client/ClientDashboardController.php, Client/ClientController.php,
  Client/ClientProfileController.php, Client/ClientSettingsController.php
  Employee/EmployeeDashboardController.php
  Admin/AdminDashboardController.php, Admin/AdminManageUsersController.php,
  Admin/AdminNotificationSettingsController.php, Admin/Client/ManageClientSubscriptionsController.php
  Auth/{Admin,Client,Driver,Employee}AuthController.php
  Points/PointsPackageController.php, Points/ClientPointsController.php
  Subscription/SubscriptionController.php, DiscountController.php
  Product/ProductController.php, ProductService/ProductServiceController.php
  Contact/ContactController.php, Admin/Contact/AdminContactController.php
  Api/ProductController.php
app/Services/          WhatsAppService.php, DiscountService.php, NotificationService.php, KnetService.php
app/Jobs/               DOES NOT EXIST — no queued jobs anywhere in the app today.
database/migrations/    35 files; key one: 2025_01_30_145210_create_orders_with_all_stuff.php
routes/web.php          all routes; driver group at lines 168-175, KNET callback at line 111
bootstrap/app.php       CSRF exemption for payment/callback at line 17
config/auth.php         guards: web/client/driver/employee/admin, each own provider+model
config/queue.php        default connection env(QUEUE_CONNECTION, 'database')
tests/                  tests/Feature/ExampleTest.php, tests/Unit/ExampleTest.php, tests/TestCase.php — all stock/empty
database/factories/     only UserFactory.php exists (no OrderFactory, ProductFactory, etc.)
```

### Auth / multi-role model (already correct, no action needed)
`config/auth.php` defines 4 guards (`client`, `driver`, `employee`, `admin`), each with its own Eloquent provider model (`App\Models\Client`, `Driver`, `Employee`, `Admin`). All 4 models **extend `App\Models\User`**, set `protected $table = 'users'`, and add a `static::addGlobalScope('driver', fn($q) => $q->where('user_type','driver'))`-style scope (see `app/Models/Driver.php`, `app/Models/Client.php`). So there is one physical `users` table with a `user_type` enum column (`client|driver|employee|admin`, default `client`, migration `2025_01_07_092841_create_users_table.php:44`), and `Auth::guard('driver')->id()` is a plain `users.id` — the same value stored in `order_deliveries.user_id`. This is a legitimate, working single-table-per-role pattern; **not** a finding, just important context for Task 3's authorization fix. There is no multi-tenant/branch concept anywhere in the codebase (`grep -r "branch|tenant|facility" app/` → no matches) — this is a single-facility app.

### Current `Order` model — `app/Models/Order.php`
```php
class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'discount_id', 'sum_price', 'discount_amount', 'status',
        'payment_method', 'payment_id', 'is_paid', 'points_used', 'notes',
        'discount_type', 'discount_value', 'discount_applied_by', 'discount_applied_at',
    ];

    protected $casts = [
        'status' => 'string', 'payment_method' => 'string', 'is_paid' => 'boolean',
        'points_used' => 'integer', 'sum_price' => 'decimal:2',
        'discount_value' => 'decimal:2', 'discount_amount' => 'decimal:2',
        'discount_applied_at' => 'datetime',
    ];

    // relations: user() belongsTo User, discount() belongsTo Discount,
    // clientSubscription() belongsTo ClientSubscription,
    // orderProductServices() hasMany OrderProductService,
    // orderDelivery() hasOne OrderDelivery (NOTE: no DB unique constraint backs this hasOne — see Finding F5)

    public function canApplyDiscount(): bool { return in_array($this->status, ['draft', 'pending']); }
    public function scopeExcludingPointsPayments($query) { return $query->where('payment_method', '!=', 'points'); }
    public function getItemsSubtotalAttribute(): float { /* sums orderProductServices price_at_order*quantity */ }
}
```

### Current enums
`app/Enums/OrderStatus.php`:
```php
class OrderStatus {
    const PENDING = 'Pending'; const PROCESSING = 'Processing'; const SHIPPED = 'Shipped';
    const COMPLETED = 'Completed'; const CANCELLED = 'Cancelled';
}
```
`app/Enums/DeliveryStatus.php`:
```php
class DeliveryStatus {
    const ASSIGNED = 'Assigned'; const EN_ROUTE = 'En Route'; const DELIVERED = 'Delivered'; const CANCELLED = 'Cancelled';
}
```
Grep confirmed **`Processing` and `Shipped` are never set by any controller** — only reachable via the raw admin edit-status `<select>` at `resources/views/orders/edit.blade.php:75-79`, and the seeder. Nothing moves an order through them in the app's real logic.

### `orders` / `order_deliveries` schema — `database/migrations/2025_01_30_145210_create_orders_with_all_stuff.php`
```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');       // Finding F4: cascade wipes order history if a client is hard-deleted
    $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('set null');
    $table->decimal('sum_price', 10, 2);
    $table->decimal('discount_amount', 10, 2)->default(0);
    $table->enum('status', [OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::COMPLETED, OrderStatus::CANCELLED])
          ->default(OrderStatus::PENDING); // "No ->change() here!" comment already in the migration
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('order_product_services', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->foreignId('product_service_id')->constrained('product_services')->onDelete('cascade');
    $table->integer('quantity')->default(1);
    $table->timestamps();
});

Schema::create('order_deliveries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');       // Finding F4: cascade wipes delivery history if a driver is hard-deleted
    $table->enum('direction', [DeliveryDirection::ORDER_TO_WORK, DeliveryDirection::WORK_TO_ORDER, DeliveryDirection::BOTH]);
    $table->decimal('price', 10, 2);
    $table->enum('status', [DeliveryStatus::ASSIGNED, DeliveryStatus::EN_ROUTE, DeliveryStatus::DELIVERED, DeliveryStatus::CANCELLED])->default('Assigned');
    $table->timestamp('delivery_date')->nullable();
    $table->timestamps();
    $table->softDeletes();
    // NOTE: no unique() on order_id — Finding F5, duplicate-delivery race condition
});
```
Later migrations added to `orders`: `discount_type`, `discount_value`, `discount_applied_by`, `discount_applied_at` (`2026_02_01_000000_add_discount_fields_to_orders_table.php`), `notes` (`2026_04_13_000000`), `payment_method` (`2026_04_13_100003_add_payment_method_to_orders.php`), `points_used`/points fields (`2026_04_13_1000xx`), `is_paid` (`2026_04_24_100000_add_is_paid_to_orders.php`), `payment_id` (`2026_04_24_000001_add_knet_payment_to_orders.php`).

`users` table (`database/migrations/2025_01_07_092841_create_users_table.php` + `0001_01_01_000000_create_users_table.php`): has `mobile` (string, **unique, NOT NULL**), `user_type` (enum, default `client`), `address_id` (nullable FK), plus later-added `balance`, `points_balance`, `notification_language` (`2026_02_20_100000...`). `User` uses `SoftDeletes` (`app/Models/User.php:13`).

### Controllers with order-money logic (full behavior already read)

**`app/Http/Controllers/Order/OrdersController.php`** (909 lines) — constructor injects `NotificationService`, `KnetService`.
- `store()`: validates, computes `sum_price` from `ProductServicePrice` per line (query-per-line, N+1), applies discount (non-client only), `DB::beginTransaction()`, creates `Order` with `status = OrderStatus::PENDING`, creates one `OrderDelivery` row if `bring_order`/`return_order` checked, creates `OrderProductService` rows, then branches on `payment_method`:
  - `points`: checks `$user->points_balance < $total_points` → rollback+error, else `$user->points_balance -= $total_points; $user->save();` then `$order->update(['status'=>COMPLETED,'is_paid'=>true])`, then **synchronously** calls `$this->notificationService->sendTransactionNotification(...)` (WhatsApp HTTP call) **before** `DB::commit()` at line 380 — Finding F7 (I/O inside open transaction).
  - `knet`: commits early, then calls `KnetService::createOrderPayment()`, redirects to gateway. Order stays `Pending` until callback.
  - `money`/default: `$user->balance -= $orderCost; $user->save();` (unlocked read-modify-write — Finding F2), sets `status=COMPLETED,is_paid=true`, same synchronous-notification-inside-transaction issue.
- `pay(Request, Order $order)`: same three branches for paying an existing unpaid order; also unlocked balance/points mutation.
- `publicPay`/`publicPayComplete`: signed(?) shareable payment link — **not actually using Laravel's `signed` URL middleware**, just a plain route bound to `Order $order`; relies entirely on `is_paid` check.
- `edit()`/`update()`: **Finding F1 (state machine)** — `order_status` field validated only with `in:` against all 5 enum values, no transition guard, admin can set any status. **Finding F6 (cancellation refund gap)**: reversal logic at lines 831-857 only reverses/reapplies balance based on **price delta and payment method**, never on status change — setting status to `Cancelled` via this form does **not** refund the customer. **Finding F8 (refresh() bug)**: at lines 741-756, `$order->update($orderData)` is called, then if no discount fields were submitted, code does a raw `DB::table('orders')->where('id',...)->update([...clear discount columns...])` followed by `$order->refresh()`. This `refresh()` reloads the model from DB, which **resets Eloquent's "original" attribute tracking** to the just-saved values. The reversal code at lines 837-838 (`$order->getOriginal('payment_method')`, `$order->getOriginal('points_used')`) runs **after** this refresh, so if an edit simultaneously changes `payment_method` (e.g. points → money) **and** clears a discount, `getOriginal()` no longer returns the true pre-edit values — it returns the already-updated ones — corrupting the balance/points reversal math.
- `destroy()`: soft-deletes order, refunds full `sum_price`/`points_used` unconditionally based on original payment method — this is the **only** path that actually refunds on cancellation, and it's inconsistent with the status-dropdown "Cancelled" path above.

**`app/Http/Controllers/Order/OrderAssignmentController.php`** (164 lines):
- `assignOrder()`: reads `$order->orderDelivery` (no lock), branches update-vs-create with no transaction — **Finding F5**: concurrent requests can both see "no delivery yet" and both `OrderDelivery::create()`, producing two delivery rows for one order (no unique constraint stops this; `hasOne` just silently returns one of them later).
- `recommendDriver()`: builds a driver-availability query using `whereDoesntHave('orderDeliveries', fn($q) => $q->where('status', DeliveryStatus::ASSIGNED)->where('user_id', DB::raw('users.id')))` — the `DB::raw('users.id')` correlation is suspicious/likely incorrect (the outer query is on `Driver`/`users`, not correctly correlated inside the `whereDoesntHave` subquery context) but is **out of scope for this plan** (not money/security-critical; flagged for a future cleanup, not a task here).

**`app/Http/Controllers/Driver/DriverController.php`** (98 lines) — **Finding F3, the most severe individual bug**:
```php
public function updateOrderStatus(Order $order, $status)
{
    if ($order->status == OrderStatus::COMPLETED && $status != OrderStatus::COMPLETED) {
        $driver = Auth::user();
        $driver->decrement('balance', $order->orderDelivery->price);
        $driver->save();
    } else if ($order->status != OrderStatus::COMPLETED && $status == OrderStatus::COMPLETED) {
        $driver = Auth::user();
        $driver->increment('balance', $order->orderDelivery->price);
        $driver->save();
        $driver->refresh();
        $this->notificationService->sendTransactionNotification($driver, 'driver_delivery_completed', [...]);
    }
    $order->update(['status' => $status]);
    return redirect()->back()->with('success', __('messages.updated_successfully'));
}
```
Bound from `routes/web.php:173`: `Route::put('driver/orders/{order}/{status}', [DriverController::class, 'updateOrderStatus'])->name('driver.orders.update')` inside `Route::prefix('driver')->middleware('auth:driver')`. `{status}` is a **raw path segment**, never validated against the `OrderStatus` enum before being written to `$order->update(['status' => $status])` (MySQL enum column will just reject truly invalid values with a DB error, but any of the 5 real enum strings work regardless of legality). **There is no check anywhere in this method that `$order->orderDelivery->user_id === Auth::id()`** — any authenticated driver (auth only requires *being* a driver, not being *the assigned* driver) can `PUT /driver/orders/{any_order_id}/{any_valid_status}` for an order that isn't theirs, silently crediting/debiting **their own** balance based on that order's `orderDelivery->price`, and also throws an unhandled error if `$order->orderDelivery` is null (order with no delivery assigned) since `->price` is accessed on potentially null.

### Payment / KNET (webhook, the most severe finding overall)

**`app/Services/KnetService.php`** (214 lines): `createPayment`/`createOrderPayment`/`createPointsPackagePayment` each generate a `tracking_id` (`'TRK-ORD-' . time() . '-' . Str::random(8)` — predictable structure, only 8 random chars of entropy after a guessable prefix+timestamp) and create a `Payment` row (`status: 'pending'`). `getKnetPaymentUrl()` is **an explicit stub** (`// TODO: Integrate with KNET PHP SDK`) — production KNET is not actually wired up; `debug` config flag routes everything to a local `client.payment.test-gateway`/`client.payment.public-test-gateway` view instead.

`handleCallback(array $data)` — **Finding F1, most critical**:
```php
public function handleCallback(array $data): array
{
    $trackingId = $data['tracking_id'] ?? null;
    $result = $data['result'] ?? 'FAILED';
    // ... loads $payment = Payment::where('transaction_id', $trackingId)->first();
    $paymentStatus = ($result === 'CAPTURED' || $result === 'successful') ? 'completed' : 'failed';
    $payment->update(['status' => $paymentStatus, 'payment_date' => now(), 'details' => ...]);
    if ($paymentStatus === 'completed') {
        $details = json_decode($payment->details, true) ?? [];
        $type = $details['type'] ?? null;
        if ($type === 'points_package' && isset($details['purchase_id'])) { /* completes purchase */ }
        elseif ($type === 'order') {
            if (!empty($details['order_id'])) {
                Order::where('id', $details['order_id'])->update(['status' => OrderStatus::COMPLETED, 'is_paid' => true]);
            }
        } else {
            $payment->user->increment('balance', $payment->amount);   // <-- unconditional credit, every call
        }
    }
    return [...];
}
```
- **No signature/HMAC verification of `$data` anywhere.** `PaymentController::callback()` (`app/Http/Controllers/Client/PaymentController.php:70-110`) does `$data = $request->all(); $result = $this->knetService->handleCallback($data);` directly — trusts the raw POST body completely.
- Route: `routes/web.php:111`: `Route::match(['get','post'], '/payment/callback', [PaymentController::class,'callback'])->name('client.payment.callback')` — **outside** every `auth:*` middleware group (must be, for a real gateway to reach it) and CSRF-exempted at `bootstrap/app.php:17` (`$middleware->validateCsrfTokens(except: ['payment/callback'])`) — also required for a webhook, but there is **no compensating control**.
- **No idempotency check**: nothing verifies `$payment->status` is still `pending` before crediting. A duplicate webhook delivery (real gateways retry on timeout) re-runs the `increment('balance', ...)` or re-sets `Order::status=COMPLETED` a second time — for the `else` branch (generic balance top-up) this is a **direct double-credit of real money**.
- Net effect: anyone who can guess/observe a `tracking_id` can `POST /payment/callback {tracking_id, result: 'CAPTURED'}` unauthenticated and mark any pending payment completed — crediting balance or completing an order for free.

**`app/Http/Controllers/Client/PaymentController.php`** (133 lines): `create()`, `store()` (initiates a generic balance top-up payment), `testGateway()` (renders the local fake KNET page in debug mode), `callback()` (above), `complete()` (result page). No signature generation exists yet anywhere in the debug-mode test-gateway loop either — it is a fully open loop end to end today.

### Notifications / Queue

**`app/Services/NotificationService.php`** (60 lines): `sendTransactionNotification(User $user, string $messageKey, array $replace)` → resolves a message from `NotificationTemplate` (admin-editable, per-language) or falls back to `lang` files, then calls `WhatsAppService::sendMessage()` (a real outbound HTTP call, implementation not read in this audit but confirmed to exist at `app/Services/WhatsAppService.php`). Called **synchronously, inline, inside open `DB::transaction()` blocks** from `OrdersController::store()` (before `DB::commit()`), `OrdersController::update()`, `DriverController::updateOrderStatus()`, `PaymentController::callback()`.

**No `app/Jobs` directory exists at all** — confirmed via `Glob app/Jobs/**/*.php` → no results. `config/queue.php` is stock Laravel, `default => env('QUEUE_CONNECTION', 'database')`. Nothing in the app is ever queued; all "async-shaped" work (notifications, would-be invoice generation) runs synchronously on the request thread today.

### N+1 / performance

`OrdersController::show()` (lines 412-426) and `OrdersController::pay()` (lines 450-464) both loop `$order->orderProductServices as $line` and run `ProductServicePrice::where('product_id', ...)->where('product_service_id', ...)->first()` **per line item**, inside a single request — one query per order line instead of a single batched query. Bounded (orders rarely have >10 lines) but still avoidable and on a hot page (every order detail view + every payment attempt).

### Full priority-ranked finding list (source of the phases below)

| # | Finding | Severity | File(s) |
|---|---|---|---|
| F1 | KNET webhook forgeable (no signature) + not idempotent (double-credit on retry) | **P0 — live money/security** | `app/Services/KnetService.php`, `app/Http/Controllers/Client/PaymentController.php`, `routes/web.php:111` |
| F2 | Unlocked read-modify-write on `users.balance`/`points_balance` (lost-update race) | **P0 — live money** | `app/Http/Controllers/Order/OrdersController.php` (store/pay/update/destroy) |
| F3 | Driver IDOR: no ownership check on `updateOrderStatus`, `$status` unvalidated, null-delivery crash | **P0 — live security/money** | `app/Http/Controllers/Driver/DriverController.php:43-62`, `routes/web.php:173` |
| F5 | No unique constraint / lock on `order_deliveries.order_id` → duplicate delivery race | **P1 — data integrity** | `app/Http/Controllers/Order/OrderAssignmentController.php:69-123`, migration |
| F4 | `onDelete('cascade')` on `orders.user_id` / `order_deliveries.user_id` destroys history on user soft/hard delete | **P1 — data integrity** | `database/migrations/2025_01_30_145210_create_orders_with_all_stuff.php:85,119` |
| F1b | No audit trail of who changed order status / when | **P1 — data integrity** | new table needed |
| F9 | No flagged/on-hold sub-workflow for damaged/missing items | **P1 — product gap** | new column needed |
| F1c | `OrderStatus` has no real fulfillment lifecycle; `Processing`/`Shipped` are dead; `status` is set to `Completed` at **payment** time, not delivery time; edit-form allows illegal jumps | **P2 — architecture** | `app/Enums/OrderStatus.php`, `app/Http/Controllers/Order/OrdersController.php` |
| F6 | Cancelling via admin edit-status dropdown never refunds balance/points (only `destroy()` does) | **P2 — architecture, depends on F1c** | `app/Http/Controllers/Order/OrdersController.php:588-869` |
| F8 | `$order->refresh()` after raw discount-clearing update corrupts `getOriginal()` reversal math | **P2 — bugfix, same method as F6** | `app/Http/Controllers/Order/OrdersController.php:741-757` |
| F10 | No mid-cycle repricing/re-authorization flow for weight/item-count correction at facility | **P3 — product gap, depends on Phase 3** | new flow needed |
| F7 | Synchronous WhatsApp HTTP calls inside open DB transactions; no queue used anywhere | **P4 — infra/perf** | `app/Services/NotificationService.php` + 4 call sites |
| F11 | N+1 `ProductServicePrice` lookups in `show()`/`pay()` | **P4 — perf** | `app/Http/Controllers/Order/OrdersController.php:412-426,450-464` |

---

## Phase 1 — P0: Stop active money/security bleeding

These four tasks are minimal-diff patches to existing files/routes. No schema changes. Ship this phase first, independently of everything else.

### Task 1: KNET webhook signature verification + idempotency guard

**Files:**
- Modify: `app/Services/KnetService.php`
- Modify: `app/Http/Controllers/Client/PaymentController.php`
- Modify: `resources/views/client/payment/test-gateway.blade.php` (need to read current content before editing — not read during audit; find the "pay"/"complete" button/form in it and add the signature field to whatever it POSTs/GETs to `client.payment.callback`)
- Modify: `config/services.php` (add `knet.secret` — read current `services.knet` block before editing, likely already has `debug` key per `KnetService::__construct`)
- Test: `tests/Feature/KnetWebhookSecurityTest.php`

**Interfaces:**
- Produces: `KnetService::signCallback(string $trackingId, string $result): string` — HMAC-SHA256 of `"{$trackingId}|{$result}"` keyed by `config('services.knet.secret')`.
- Produces: `KnetService::handleCallback(array $data): array` — same signature as today, but now returns `['status' => 'error', 'description' => 'Payment already processed']` (no state change) when the target `Payment` is not `pending`.
- Consumes (in `PaymentController::callback`): `KnetService::signCallback()` to verify `$request->input('signature')` before calling `handleCallback()`.

- [ ] **Step 1: Read current `config/services.php` and `resources/views/client/payment/test-gateway.blade.php` to confirm exact existing structure**

Run: read both files fully before writing the diff (they were not part of the original audit read-set). Confirm whether `services.knet` already has a `debug` key (it must, since `KnetService::__construct` reads `config('services.knet')['debug']`) and note the exact array shape so the added `secret` key matches style.

- [ ] **Step 2: Write the failing test for signature verification**

```php
<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnetWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.knet.secret' => 'test-secret-key']);
    }

    public function test_callback_without_valid_signature_is_rejected(): void
    {
        $client = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000001',
            'balance' => 0,
        ]);

        $payment = Payment::create([
            'user_id' => $client->id,
            'amount' => 25.000,
            'payment_method' => 'KNET',
            'transaction_id' => 'TRK-ORD-forged-1',
            'status' => 'pending',
            'details' => json_encode(['tracking_id' => 'TRK-ORD-forged-1', 'type' => 'balance']),
        ]);

        $response = $this->post('/payment/callback', [
            'tracking_id' => 'TRK-ORD-forged-1',
            'result' => 'CAPTURED',
            // no signature at all
        ]);

        $client->refresh();
        $payment->refresh();

        $this->assertSame(0.0, (float) $client->balance);
        $this->assertSame('pending', $payment->status);
    }

    public function test_callback_with_valid_signature_completes_payment_once(): void
    {
        $client = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000002',
            'balance' => 0,
        ]);

        $payment = Payment::create([
            'user_id' => $client->id,
            'amount' => 25.000,
            'payment_method' => 'KNET',
            'transaction_id' => 'TRK-ORD-valid-1',
            'status' => 'pending',
            'details' => json_encode(['tracking_id' => 'TRK-ORD-valid-1', 'type' => 'balance']),
        ]);

        $signature = hash_hmac('sha256', 'TRK-ORD-valid-1|CAPTURED', 'test-secret-key');

        $this->post('/payment/callback', [
            'tracking_id' => 'TRK-ORD-valid-1',
            'result' => 'CAPTURED',
            'signature' => $signature,
        ]);

        $client->refresh();
        $payment->refresh();

        $this->assertEquals(25.0, (float) $client->balance);
        $this->assertSame('completed', $payment->status);
    }

    public function test_duplicate_valid_callback_does_not_double_credit(): void
    {
        $client = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000003',
            'balance' => 0,
        ]);

        Payment::create([
            'user_id' => $client->id,
            'amount' => 25.000,
            'payment_method' => 'KNET',
            'transaction_id' => 'TRK-ORD-dup-1',
            'status' => 'pending',
            'details' => json_encode(['tracking_id' => 'TRK-ORD-dup-1', 'type' => 'balance']),
        ]);

        $signature = hash_hmac('sha256', 'TRK-ORD-dup-1|CAPTURED', 'test-secret-key');
        $payload = ['tracking_id' => 'TRK-ORD-dup-1', 'result' => 'CAPTURED', 'signature' => $signature];

        $this->post('/payment/callback', $payload);
        $this->post('/payment/callback', $payload); // retry, same as a real gateway resend

        $client->refresh();

        $this->assertEquals(25.0, (float) $client->balance); // not 50
    }
}
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `php artisan test --filter=KnetWebhookSecurityTest`
Expected: All 3 tests FAIL (signature not implemented yet, callback currently trusts unsigned data and double-credits).

- [ ] **Step 4: Add `signCallback()` and the idempotency guard to `KnetService`**

In `app/Services/KnetService.php`, add this method (place near the top, after the constructor):

```php
    /**
     * Sign a callback payload so our own test gateway (and, later, real KNET
     * verification per their SDK docs) can be authenticated by handleCallback().
     */
    public function signCallback(string $trackingId, string $result): string
    {
        return hash_hmac('sha256', "{$trackingId}|{$result}", (string) config('services.knet.secret'));
    }
```

Then modify `handleCallback(array $data)` to add the idempotency guard right after the `$payment` lookup (before the `$paymentStatus = ...` line):

```php
        if (!$payment) {
            return [
                'status' => 'error',
                'description' => 'Payment not found',
            ];
        }

        if ($payment->status !== 'pending') {
            // Idempotency: a retried/duplicated webhook must not re-apply side effects.
            return [
                'status' => 'success',
                'payment_status' => $payment->status,
                'tracking_id' => $trackingId,
                'redirect_url' => route('client.payment.complete', ['tracking_id' => $trackingId]),
            ];
        }

        $paymentStatus = ($result === 'CAPTURED' || $result === 'successful') ? 'completed' : 'failed';
```

- [ ] **Step 5: Verify the signature in `PaymentController::callback()` before trusting the payload**

In `app/Http/Controllers/Client/PaymentController.php`, replace the start of `callback()`:

```php
    public function callback(Request $request)
    {
        $data = $request->all();

        $trackingIdForSig = $data['tracking_id'] ?? '';
        $resultForSig = $data['result'] ?? 'FAILED';
        $providedSignature = (string) ($data['signature'] ?? '');
        $expectedSignature = $this->knetService->signCallback($trackingIdForSig, $resultForSig);

        if ($trackingIdForSig === '' || !hash_equals($expectedSignature, $providedSignature)) {
            \Illuminate\Support\Facades\Log::warning('Rejected KNET callback with invalid signature', [
                'tracking_id' => $trackingIdForSig,
            ]);
            abort(403, 'Invalid callback signature.');
        }

        $result = $this->knetService->handleCallback($data);
```

Leave the rest of the method unchanged (it already reads `$result` from this point on).

> **TODO for production KNET integration:** when the real KNET SDK is wired into `KnetService::getKnetPaymentUrl()`, replace this HMAC check with KNET's own signature/hash verification per their SDK documentation — this HMAC scheme only secures our own self-hosted `debug`-mode test gateway loop.

- [ ] **Step 6: Add the `signature` param to the test-gateway flow so the debug loop keeps working**

After reading the actual `resources/views/client/payment/test-gateway.blade.php` in Step 1, find the form/link that posts to `route('client.payment.callback', ...)` or similar, and add a hidden `signature` field computed the same way the "Simulate success" action already knows its `tracking_id` and intended `result`. If the callback URL/result is built in `PaymentController::testGateway()` or generated client-side in the Blade file, generate the signature server-side in `PaymentController::testGateway()` (inject `KnetService`, call `signCallback($trackingId, 'CAPTURED')` and `signCallback($trackingId, 'FAILED')`, pass both into the view) and render them as hidden fields on the two success/fail buttons. Exact edit depends on what Step 1 reveals — do not guess the Blade structure before reading it.

- [ ] **Step 7: Add `config/services.php` entry**

Read the existing `services.knet` array first (Step 1). Add a `secret` key alongside the existing `debug` key, sourced from env:

```php
    'knet' => [
        'debug' => env('KNET_DEBUG', true),
        'secret' => env('KNET_CALLBACK_SECRET', 'change-me-in-production'),
        // ...keep any other existing keys found in Step 1 untouched
    ],
```

Add to `.env.example` (read it first, append near other KNET-related vars if any exist, otherwise at the end):
```
KNET_CALLBACK_SECRET=change-me-in-production
```

- [ ] **Step 8: Run the tests to verify they pass**

Run: `php artisan test --filter=KnetWebhookSecurityTest`
Expected: All 3 tests PASS.

- [ ] **Step 9: Manually verify the debug payment loop still works end-to-end**

Run: `php artisan serve`, log in as a client, go through Pay → KNET → test gateway → "simulate success", confirm balance updates and no 403 is thrown. This exercises Step 6's Blade change, which cannot be unit-tested without reading the view first.

- [ ] **Step 10: Commit**

```bash
git add app/Services/KnetService.php app/Http/Controllers/Client/PaymentController.php \
        resources/views/client/payment/test-gateway.blade.php config/services.php .env.example \
        tests/Feature/KnetWebhookSecurityTest.php
git commit -m "fix: verify KNET callback signature and enforce webhook idempotency"
```

---

### Task 2: Atomic balance/points mutations (fix the lost-update race, F2)

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Http/Controllers/Order/OrdersController.php` (all 4 balance/points mutation sites: `store()`, `pay()`, `update()`, `destroy()`)
- Modify: `app/Services/KnetService.php` (the `else` branch generic-balance-credit in `handleCallback()`)
- Test: `tests/Feature/OrderBalanceConcurrencyTest.php`

**Interfaces:**
- Produces on `User` model: `public static function adjustBalance(int $userId, float|string $delta): self` and `public static function adjustPoints(int $userId, float|string $delta): self` — both open `DB::transaction()`, `self::whereKey($userId)->lockForUpdate()->firstOrFail()`, apply the delta, `save()`, return the fresh locked instance. `$delta` may be negative (debit) or positive (credit). Throws the underlying DB exception if the user row doesn't exist — callers must already know the user exists (all current call sites do a `User::find()`/`Auth::user()` first).
- Consumes: every call site in `OrdersController` and `KnetService` replaces `$user->balance -= $x; $user->save();` / `$user->points_balance -= $x; $user->save();` / `increment('balance', ...)` with calls to these two static methods.

- [ ] **Step 1: Write the failing concurrency test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderBalanceConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjust_balance_is_atomic_under_concurrent_writers(): void
    {
        $user = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000010',
            'balance' => 100,
        ]);

        // Simulate two "concurrent" debits using two separate connections'
        // worth of sequential calls through the atomic helper — this proves
        // each call re-reads the latest committed value instead of relying
        // on a stale in-memory $user instance (the bug pattern being fixed).
        $staleCopy1 = User::find($user->id);
        $staleCopy2 = User::find($user->id);

        User::adjustBalance($user->id, -30); // using the atomic helper, not $staleCopy1
        User::adjustBalance($user->id, -30); // using the atomic helper, not $staleCopy2

        $user->refresh();

        $this->assertEquals(40.0, (float) $user->balance); // 100 - 30 - 30, not 70 (lost update)
    }

    public function test_adjust_points_is_atomic(): void
    {
        $user = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000011',
            'points_balance' => 50,
        ]);

        User::adjustPoints($user->id, -20);
        User::adjustPoints($user->id, -20);

        $user->refresh();

        $this->assertEquals(10.0, (float) $user->points_balance);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=OrderBalanceConcurrencyTest`
Expected: FAIL with "Call to undefined method App\Models\User::adjustBalance()".

- [ ] **Step 3: Add the atomic helpers to `User` model**

In `app/Models/User.php`, add (near the other public methods, after `user_type_translated()`):

```php
    /**
     * Atomically adjust balance by $delta (positive to credit, negative to debit),
     * taking a row lock so concurrent requests for the same user cannot lose an update.
     */
    public static function adjustBalance(int $userId, float|string $delta): self
    {
        return DB::transaction(function () use ($userId, $delta) {
            $user = self::whereKey($userId)->lockForUpdate()->firstOrFail();
            $user->balance = bcadd((string) $user->balance, (string) $delta, 2);
            $user->save();
            return $user;
        });
    }

    /**
     * Atomically adjust points_balance by $delta, taking a row lock so concurrent
     * requests for the same user cannot lose an update.
     */
    public static function adjustPoints(int $userId, float|string $delta): self
    {
        return DB::transaction(function () use ($userId, $delta) {
            $user = self::whereKey($userId)->lockForUpdate()->firstOrFail();
            $user->points_balance = bcadd((string) $user->points_balance, (string) $delta, 2);
            $user->save();
            return $user;
        });
    }
```

Add `use Illuminate\Support\Facades\DB;` to the top of the file if not already imported (it was not seen in the model in the audit read — check before adding to avoid a duplicate `use`).

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=OrderBalanceConcurrencyTest`
Expected: Both tests PASS.

- [ ] **Step 5: Replace all unlocked mutation sites in `OrdersController`**

In `app/Http/Controllers/Order/OrdersController.php`:

`store()`, points branch (was `$user->points_balance -= $total_points; $user->save();` around line 350):
```php
            if ($paymentMethod === 'points') {
                if ($user->points_balance < $total_points) {
                    DB::rollBack();
                    return back()->withErrors(['message' => __('messages.insufficient_points')])->withInput();
                }
                $user = User::adjustPoints($user->id, -$total_points);
                $order->update(['status' => OrderStatus::COMPLETED, 'is_paid' => true]);
                $this->notificationService->sendTransactionNotification($user, 'order_placed_balance', ['balance' => $user->balance]);
            } elseif ($paymentMethod === 'knet') {
```

`store()`, money branch (was `$user->balance -= $orderCost; $user->save();` around line 374):
```php
            } else {
                $user = User::adjustBalance($user->id, -$orderCost);
                $order->update(['status' => OrderStatus::COMPLETED, 'is_paid' => true]);
                $this->notificationService->sendTransactionNotification($user, 'order_placed_balance', ['balance' => $user->balance]);
            }
```

`pay()`, points branch (was `$user->points_balance -= $totalPoints; $user->save();` around line 472):
```php
            if ($user->points_balance < $totalPoints) {
                return back()->withErrors(['message' => __('messages.insufficient_points')]);
            }
            foreach ($order->orderProductServices as $line) {
                $line->update(['points_at_order' => $linePoints[$line->id]]);
            }
            $user = User::adjustPoints($user->id, -$totalPoints);
            $order->update([
```

`pay()`, money branch (was `$user->balance -= $order->sum_price; $user->save();` around line 497):
```php
        } else {
            $user = User::adjustBalance($user->id, -$order->sum_price);
            $order->update([
                'payment_method' => 'money',
                'is_paid'        => true,
                'status'         => OrderStatus::COMPLETED,
            ]);
```

`update()`, reversal + re-apply block (was two separate `$user->balance +=`/`$user->points_balance +=` then `$user->balance -=`/`points_balance -=` then one `$user->save()` around lines 840-857) — collapse the reverse+reapply into two atomic calls:
```php
            // Reverse original charge, then apply new charge — each step is its own
            // atomic, locked operation so a concurrent request for the same user
            // can't interleave and lose part of this adjustment.
            if ($originalPaymentMethod === 'points') {
                $user = User::adjustPoints($user->id, $originalPointsUsed);
            } else {
                $user = User::adjustBalance($user->id, $originalPrice);
            }

            if ($editPaymentMethod === 'points') {
                if ($user->points_balance < $total_points_edit) {
                    DB::rollBack();
                    return back()->withErrors(['message' => __('messages.insufficient_points')])->withInput();
                }
                $user = User::adjustPoints($user->id, -$total_points_edit);
            } else {
                $user = User::adjustBalance($user->id, -$orderCost);
            }
```
Remove the now-redundant trailing `$user->save();` that followed this block.

`destroy()` (was `$user->points_balance += $deletedPointsUsed;`/`$user->balance += $orderCost;` then `$user->save()`):
```php
            $order->delete();

            if ($deletedPaymentMethod === 'points') {
                $user = User::adjustPoints($user->id, $deletedPointsUsed);
            } else {
                $user = User::adjustBalance($user->id, $orderCost);
            }

            $this->notificationService->sendTransactionNotification($user, 'order_deleted_balance', ['balance' => $user->balance]);
```

> Note: `store()`/`update()`/`destroy()` already run inside an outer `DB::beginTransaction()`/`DB::commit()`. `User::adjustBalance`/`adjustPoints` opening their own `DB::transaction()` nested inside that is safe — Laravel's query builder treats nested `DB::transaction()` calls as savepoints, and `lockForUpdate()` still takes effect within the outer transaction's connection.

- [ ] **Step 6: Replace the generic-credit branch in `KnetService::handleCallback()`**

Change `$payment->user->increment('balance', $payment->amount);` to:
```php
            } else {
                User::adjustBalance($payment->user_id, $payment->amount);
            }
```
(`use App\Models\User;` is already imported at the top of `KnetService.php` per the audit read.)

- [ ] **Step 7: Run the full order test suite to check nothing broke**

Run: `php artisan test --filter=Order`
Expected: All PASS (this will also catch Task 1's `KnetWebhookSecurityTest` since it touches `handleCallback`).

- [ ] **Step 8: Commit**

```bash
git add app/Models/User.php app/Http/Controllers/Order/OrdersController.php app/Services/KnetService.php \
        tests/Feature/OrderBalanceConcurrencyTest.php
git commit -m "fix: make balance/points mutations atomic and lock-safe under concurrency"
```

---

### Task 3: Driver order-ownership authorization + status validation (F3)

**Files:**
- Modify: `app/Http/Controllers/Driver/DriverController.php`
- Modify: `routes/web.php` (line 173 area)
- Test: `tests/Feature/DriverOrderAuthorizationTest.php`

**Interfaces:**
- Produces: `DriverController::updateOrderStatus(Order $order, string $status)` now `abort(404)`s if `$order->orderDelivery` is null, `abort(403)`s if `$order->orderDelivery->user_id !== Auth::guard('driver')->id()`, and `abort(422)`s if `$status` is not one of `OrderStatus::PENDING/PROCESSING/SHIPPED/COMPLETED/CANCELLED`.
- Consumes: nothing new from other tasks (independent of Task 1/2, can ship in parallel).

- [ ] **Step 1: Write the failing authorization test**

```php
<?php

namespace Tests\Feature;

use App\Enums\DeliveryDirection;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverOrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderWithDelivery(User $assignedDriver): Order
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000020', 'balance' => 0]);

        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 10,
            'status' => OrderStatus::PENDING,
        ]);

        OrderDelivery::create([
            'order_id' => $order->id,
            'user_id' => $assignedDriver->id,
            'direction' => DeliveryDirection::BOTH,
            'price' => 5,
            'status' => DeliveryStatus::ASSIGNED,
            'delivery_date' => now(),
        ]);

        return $order;
    }

    public function test_driver_cannot_update_status_of_order_not_assigned_to_them(): void
    {
        $assignedDriver = User::factory()->create(['user_type' => 'driver', 'mobile' => '50000021', 'balance' => 0]);
        $otherDriver = User::factory()->create(['user_type' => 'driver', 'mobile' => '50000022', 'balance' => 0]);
        $order = $this->makeOrderWithDelivery($assignedDriver);

        $response = $this->actingAs($otherDriver, 'driver')
            ->put(route('driver.orders.update', ['order' => $order->id, 'status' => OrderStatus::COMPLETED]));

        $response->assertForbidden();
        $otherDriver->refresh();
        $this->assertEquals(0.0, (float) $otherDriver->balance);
    }

    public function test_assigned_driver_can_update_status(): void
    {
        $assignedDriver = User::factory()->create(['user_type' => 'driver', 'mobile' => '50000023', 'balance' => 0]);
        $order = $this->makeOrderWithDelivery($assignedDriver);

        $response = $this->actingAs($assignedDriver, 'driver')
            ->put(route('driver.orders.update', ['order' => $order->id, 'status' => OrderStatus::COMPLETED]));

        $response->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::COMPLETED, $order->status);
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        $assignedDriver = User::factory()->create(['user_type' => 'driver', 'mobile' => '50000024', 'balance' => 0]);
        $order = $this->makeOrderWithDelivery($assignedDriver);

        $response = $this->actingAs($assignedDriver, 'driver')
            ->put('/driver/orders/' . $order->id . '/NotARealStatus');

        $response->assertStatus(422);
        $order->refresh();
        $this->assertSame(OrderStatus::PENDING, $order->status);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=DriverOrderAuthorizationTest`
Expected: `test_driver_cannot_update_status_of_order_not_assigned_to_them` and `test_invalid_status_value_is_rejected` FAIL (currently allowed / currently 500s or succeeds silently); `test_assigned_driver_can_update_status` PASSes already (establishes no regression).

- [ ] **Step 3: Add the guard clauses to `DriverController::updateOrderStatus()`**

```php
    public function updateOrderStatus(Order $order, string $status)
    {
        $validStatuses = [
            OrderStatus::PENDING,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::COMPLETED,
            OrderStatus::CANCELLED,
        ];

        if (!in_array($status, $validStatuses, true)) {
            abort(422, __('messages.validation_order_status_invalid'));
        }

        $delivery = $order->orderDelivery;

        if (!$delivery) {
            abort(404);
        }

        if ((int) $delivery->user_id !== (int) Auth::guard('driver')->id()) {
            abort(403);
        }

        //make a complete order Not complete (subtract from driver the delivery balance)
        if ($order->status == OrderStatus::COMPLETED && $status != OrderStatus::COMPLETED) {
            $driver = Auth::user();
            $driver->decrement('balance', $delivery->price);
            $driver->save();
        } else if ($order->status != OrderStatus::COMPLETED && $status == OrderStatus::COMPLETED) { //make Not complete order be complete (add delivery balance to driver)
            $driver = Auth::user();
            $driver->increment('balance', $delivery->price);
            $driver->save();
            $driver->refresh();
            $this->notificationService->sendTransactionNotification($driver, 'driver_delivery_completed', [
                'amount' => $delivery->price,
                'balance' => $driver->balance,
            ]);
        }
        $order->update(['status' => $status]);
        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }
```

(This still uses plain `decrement`/`increment` for the driver's own balance rather than Task 2's `User::adjustBalance` — that's acceptable since Laravel's `increment`/`decrement` already issue an atomic `UPDATE ... SET balance = balance + x` at the SQL level, unlike the `$user->balance -= x; $user->save()` pattern fixed in Task 2. No change needed here.)

- [ ] **Step 4: Loosen the route constraint doesn't help here (route param is a plain string) — no route change needed**

Confirm `routes/web.php:173` still reads `Route::put('driver/orders/{order}/{status}', [DriverController::class, 'updateOrderStatus'])->name('driver.orders.update');` — the validation now happens in the controller (Step 3), which is sufficient; no `where()` regex constraint is required since invalid values now `abort(422)` cleanly instead of hitting the DB.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=DriverOrderAuthorizationTest`
Expected: All 3 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Driver/DriverController.php tests/Feature/DriverOrderAuthorizationTest.php
git commit -m "fix: authorize driver order-status updates and validate status value"
```

---

### Task 4: Guard concurrent order-delivery assignment (F5, code half — schema half is Task 6)

**Files:**
- Modify: `app/Http/Controllers/Order/OrderAssignmentController.php`
- Test: `tests/Feature/OrderAssignmentConcurrencyTest.php`

**Interfaces:**
- Produces: `OrderAssignmentController::assignOrder()` now wraps the read-then-create/update in `DB::transaction()` with `Order::whereKey($request->order_id)->lockForUpdate()->firstOrFail()`.
- Depends on: Task 6 (unique index on `order_deliveries.order_id`) for a hard DB-level backstop — this task's app-level lock is the primary fix and works standalone; do this task before or in either order relative to Phase 2, but the test in Step 1 will only fully pass once Task 6's unique index exists too (documented in the test).

- [ ] **Step 1: Write the failing concurrency test**

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Province;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAssignmentConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigning_same_order_twice_updates_the_single_delivery_row_not_duplicates_it(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000030', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000031', 'balance' => 0]);
        $driverA = User::factory()->create(['user_type' => 'driver', 'mobile' => '50000032', 'balance' => 0]);
        $driverB = User::factory()->create(['user_type' => 'driver', 'mobile' => '50000033', 'balance' => 0]);
        $province = Province::create(['name' => 'Test Province']);
        $city = City::create(['name' => 'Test City', 'province_id' => $province->id]);

        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 10,
            'status' => OrderStatus::PENDING,
        ]);

        $payload = fn (User $driver) => [
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'bring_order' => 'on',
            'province_id' => $province->id,
            'city_id' => $city->id,
        ];

        $this->actingAs($admin, 'admin')->post(route('orders.assign.store'), $payload($driverA));
        $this->actingAs($admin, 'admin')->post(route('orders.assign.store'), $payload($driverB));

        $this->assertSame(1, $order->fresh()->orderDelivery()->withTrashed()->count());
        $this->assertSame($driverB->id, $order->fresh()->orderDelivery->user_id);
    }
}
```
> Note: confirm the actual route name for `OrderAssignmentController::assignOrder` before running this (audit read the controller, not the full route list around it) — search `routes/web.php` for `OrderAssignmentController::class, 'assignOrder'` and use whatever name is registered there in place of `orders.assign.store` if different.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=OrderAssignmentConcurrencyTest`
Expected: FAILs today only if a duplicate-row bug reproduces under this sequential simulation — note that this specific test issues the two requests **sequentially**, which the *current* code actually already handles correctly (second call finds the existing row and updates it) since there's no true parallelism in a single PHPUnit process. This test therefore mainly guards against a regression; the real race (two truly concurrent requests) is only fully closed by combining this task's `lockForUpdate()` with Task 6's unique index. Document this limitation in the test file as a comment above the class.

- [ ] **Step 3: Wrap `assignOrder()` in a locked transaction**

In `app/Http/Controllers/Order/OrderAssignmentController.php`, wrap the body of `assignOrder()` from `$order = Order::findOrFail($request->order_id);` through the end of the update/create branch in:

```php
        DB::transaction(function () use ($request) {
            $order = Order::whereKey($request->order_id)->lockForUpdate()->firstOrFail();

            // ... existing $direction computation unchanged ...

            $orderDelivery = OrderDelivery::where('order_id', $order->id)->lockForUpdate()->first();

            if ($orderDelivery) {
                // ... existing update branch, unchanged ...
            } else {
                // ... existing create branch, unchanged ...
            }
        });

        return redirect()->route('orders.show', $request->order_id)->with('success', __('messages.order_assigned_successfully'));
```

Move the `return redirect()->route(...)` (previously referencing `$order->id`) outside the closure since `$order` is now scoped inside it — use `$request->order_id` instead, which is already validated as `exists:orders,id` upstream.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=OrderAssignmentConcurrencyTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Order/OrderAssignmentController.php tests/Feature/OrderAssignmentConcurrencyTest.php
git commit -m "fix: lock order row during driver assignment to prevent duplicate deliveries"
```

---

## Phase 2 — P1: Data integrity under the code (schema hardening)

Do this after Phase 1 lands (Task 4 in particular pairs with Task 6 here). These are additive/safe migrations — no destructive column changes yet (that's Phase 3, Task 9).

### Task 5: Stop cascade-deleting order/delivery history when a user is removed (F4)

**Files:**
- Create: `database/migrations/2026_07_13_100000_fix_order_cascade_deletes.php`
- Test: `tests/Feature/OrderHistoryPreservedOnUserDeleteTest.php`

**Interfaces:**
- Produces: `orders.user_id` FK changes `onDelete('cascade')` → `onDelete('restrict')`; `order_deliveries.user_id` FK changes `onDelete('cascade')` → `onDelete('restrict')`.
- Consumes: nothing. `User` already uses `SoftDeletes` (`app/Models/User.php:13`), so normal user "deletion" in the app is already a soft delete and never touches this FK today — this migration only matters for (a) any `forceDelete()` call, (b) manual DB cleanup, (c) future code that force-deletes. `restrict` is the correct choice over `set null` because `orders.user_id`/`order_deliveries.user_id` are NOT NULL columns tied to financial records — a hard delete of a user with existing orders should fail loudly, not silently orphan the order.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHistoryPreservedOnUserDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_deleting_a_client_with_orders_is_blocked(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000040', 'balance' => 0]);

        Order::create([
            'user_id' => $client->id,
            'sum_price' => 10,
            'status' => OrderStatus::COMPLETED,
        ]);

        $this->expectException(QueryException::class);
        $client->forceDelete();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=OrderHistoryPreservedOnUserDeleteTest`
Expected: FAIL — today `forceDelete()` succeeds and cascade-deletes the order (no exception thrown).

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::table('order_deliveries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('order_deliveries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
```

- [ ] **Step 4: Run the migration and the test**

Run: `php artisan migrate` then `php artisan test --filter=OrderHistoryPreservedOnUserDeleteTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_13_100000_fix_order_cascade_deletes.php \
        tests/Feature/OrderHistoryPreservedOnUserDeleteTest.php
git commit -m "fix: block hard-delete of users with existing order/delivery history"
```

---

### Task 6: Unique constraint on `order_deliveries.order_id`

**Files:**
- Create: `database/migrations/2026_07_13_100100_add_unique_order_id_to_order_deliveries.php`
- Test: `tests/Feature/OrderDeliveryUniqueConstraintTest.php`

**Interfaces:**
- Produces: unique index `order_deliveries_order_id_unique` on `order_deliveries.order_id`.
- Consumes: none. Backstops Task 4's app-level lock with a real DB constraint — even if the lock is ever bypassed (e.g. a future direct `OrderDelivery::create()` call elsewhere), the DB now refuses a second row for the same order.

- [ ] **Step 1: Before writing the migration, check for existing duplicate rows so the migration doesn't fail on deploy**

Run this manually against the production/staging DB before applying (not a test — an operational check):
```sql
SELECT order_id, COUNT(*) c FROM order_deliveries GROUP BY order_id HAVING c > 1;
```
If any rows are returned, resolve them manually (keep the most recent, soft-delete the rest) before running this migration in that environment. Note this requirement in the migration's own PHPDoc comment (Step 3).

- [ ] **Step 2: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Enums\DeliveryDirection;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDeliveryUniqueConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_second_delivery_row_for_the_same_order_is_rejected_by_the_database(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000050', 'balance' => 0]);
        $driver = User::factory()->create(['user_type' => 'driver', 'mobile' => '50000051', 'balance' => 0]);

        $order = Order::create(['user_id' => $client->id, 'sum_price' => 10, 'status' => OrderStatus::PENDING]);

        OrderDelivery::create([
            'order_id' => $order->id, 'user_id' => $driver->id, 'direction' => DeliveryDirection::BOTH,
            'price' => 5, 'status' => DeliveryStatus::ASSIGNED, 'delivery_date' => now(),
        ]);

        $this->expectException(QueryException::class);
        OrderDelivery::create([
            'order_id' => $order->id, 'user_id' => $driver->id, 'direction' => DeliveryDirection::BOTH,
            'price' => 5, 'status' => DeliveryStatus::ASSIGNED, 'delivery_date' => now(),
        ]);
    }
}
```

- [ ] **Step 3: Write the migration**

```php
<?php
/**
 * Before deploying to any environment with existing data, run:
 *   SELECT order_id, COUNT(*) c FROM order_deliveries GROUP BY order_id HAVING c > 1;
 * and resolve any duplicates manually — this migration will fail to apply otherwise.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });
    }
};
```

- [ ] **Step 4: Run the migration and the test**

Run: `php artisan migrate` then `php artisan test --filter=OrderDeliveryUniqueConstraintTest`
Expected: PASS.

- [ ] **Step 5: Re-run Task 4's concurrency test to confirm the two layers work together**

Run: `php artisan test --filter=OrderAssignmentConcurrencyTest`
Expected: still PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_13_100100_add_unique_order_id_to_order_deliveries.php \
        tests/Feature/OrderDeliveryUniqueConstraintTest.php
git commit -m "fix: add unique constraint on order_deliveries.order_id"
```

---

### Task 7: Order status history / audit trail table

**Files:**
- Create: `database/migrations/2026_07_13_100200_create_order_status_histories_table.php`
- Create: `app/Models/OrderStatusHistory.php`
- Modify: `app/Models/Order.php` (add relation)
- Test: `tests/Feature/OrderStatusHistoryTest.php`

**Interfaces:**
- Produces model: `App\Models\OrderStatusHistory` with fillable `order_id, from_status, to_status, changed_by_type, changed_by_id, note`, belongsTo `Order`.
- Produces on `Order`: `public function statusHistories() { return $this->hasMany(OrderStatusHistory::class); }`
- Note: this task only creates the storage + model. **Nothing writes to it yet** — Task 10 (Phase 3) is where the new transition service actually records rows here. This task is schema-only, split out because it's independently reviewable/testable (a reviewer can approve "the audit table exists and behaves correctly" without needing the whole state-machine rewrite to exist yet).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_and_read_status_history_for_an_order(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000060', 'balance' => 0]);
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000061', 'balance' => 0]);

        $order = Order::create(['user_id' => $client->id, 'sum_price' => 10, 'status' => OrderStatus::PENDING]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => OrderStatus::PENDING,
            'to_status' => OrderStatus::COMPLETED,
            'changed_by_type' => 'admin',
            'changed_by_id' => $admin->id,
            'note' => 'Manual completion for testing',
        ]);

        $this->assertCount(1, $order->fresh()->statusHistories);
        $this->assertSame(OrderStatus::COMPLETED, $order->statusHistories->first()->to_status);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=OrderStatusHistoryTest`
Expected: FAIL — table/model don't exist yet.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('changed_by_type', 20)->comment('admin|employee|driver|client|system');
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
```
(`order_id` here is intentionally still `cascade` — deleting the *order* itself should remove its own history rows; this is distinct from Task 5's fix, which was about deleting the *user*, not the order.)

- [ ] **Step 4: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'changed_by_type',
        'changed_by_id',
        'note',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
```

- [ ] **Step 5: Add the relation to `Order`**

In `app/Models/Order.php`, add near the other relations:
```php
    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
```

- [ ] **Step 6: Run migration and test**

Run: `php artisan migrate` then `php artisan test --filter=OrderStatusHistoryTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_13_100200_create_order_status_histories_table.php \
        app/Models/OrderStatusHistory.php app/Models/Order.php tests/Feature/OrderStatusHistoryTest.php
git commit -m "feat: add order_status_histories audit trail table"
```

---

### Task 8: Flagged/on-hold sub-workflow columns (damaged/missing items)

**Files:**
- Create: `database/migrations/2026_07_13_100300_add_flagging_to_orders_table.php`
- Modify: `app/Models/Order.php`
- Test: `tests/Feature/OrderFlaggingTest.php`

**Interfaces:**
- Produces columns on `orders`: `is_flagged` (boolean, default false), `flag_reason` (string 255, nullable), `flagged_at` (timestamp, nullable), `flagged_by` (unsignedBigInteger FK to `users.id`, nullable, `onDelete('set null')`).
- Produces on `Order`: `flag(string $reason, int $flaggedByUserId): void` and `unflag(): void` helper methods, plus `flaggedBy()` belongsTo relation.
- Design choice (already decided during the audit, not re-litigated here): flagging is a **boolean orthogonal to `status`**, not a status value itself — an order can be `Washing` **and** flagged (item found damaged mid-wash) at the same time; a flagged order does not stop progressing through the pipeline automatically, it just surfaces to staff. This avoids exploding the linear status enum with a branch state.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlaggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_flagged_and_unflagged(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000070', 'balance' => 0]);
        $employee = User::factory()->create(['user_type' => 'employee', 'mobile' => '50000071', 'balance' => 0]);

        $order = Order::create(['user_id' => $client->id, 'sum_price' => 10, 'status' => OrderStatus::PENDING]);

        $order->flag('Shirt found with a tear before washing', $employee->id);

        $this->assertTrue($order->fresh()->is_flagged);
        $this->assertSame('Shirt found with a tear before washing', $order->fresh()->flag_reason);
        $this->assertNotNull($order->fresh()->flagged_at);
        $this->assertSame($employee->id, $order->fresh()->flagged_by);

        $order->unflag();

        $this->assertFalse($order->fresh()->is_flagged);
        $this->assertNull($order->fresh()->flag_reason);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=OrderFlaggingTest`
Expected: FAIL — columns/methods don't exist yet.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('status');
            $table->string('flag_reason', 255)->nullable()->after('is_flagged');
            $table->timestamp('flagged_at')->nullable()->after('flag_reason');
            $table->foreignId('flagged_by')->nullable()->after('flagged_at')
                ->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['flagged_by']);
            $table->dropColumn(['is_flagged', 'flag_reason', 'flagged_at', 'flagged_by']);
        });
    }
};
```

- [ ] **Step 4: Add fillable/casts/methods to `Order`**

In `app/Models/Order.php`, add `'is_flagged', 'flag_reason', 'flagged_at', 'flagged_by',` to `$fillable`, add `'is_flagged' => 'boolean', 'flagged_at' => 'datetime',` to `$casts`, and add:
```php
    public function flaggedBy()
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }

    public function flag(string $reason, int $flaggedByUserId): void
    {
        $this->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
            'flagged_at' => now(),
            'flagged_by' => $flaggedByUserId,
        ]);
    }

    public function unflag(): void
    {
        $this->update([
            'is_flagged' => false,
            'flag_reason' => null,
            'flagged_at' => null,
            'flagged_by' => null,
        ]);
    }
```

- [ ] **Step 5: Run migration and test**

Run: `php artisan migrate` then `php artisan test --filter=OrderFlaggingTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_13_100300_add_flagging_to_orders_table.php app/Models/Order.php \
        tests/Feature/OrderFlaggingTest.php
git commit -m "feat: add order flagging (damaged/missing item) sub-workflow columns"
```

> **Not included in this task, intentionally deferred:** the actual UI (admin/employee "flag this order" button, driver-facing flag indicator, customer notification on flag). This task only lands the data layer, per YAGNI — build the UI in a follow-up once Phase 3's real status pipeline exists to hang it off of.

---

## Phase 3 — P2: Replace the fake state machine with a real one

This is the architectural core. Do this only after Phase 1 and Phase 2 are merged — Task 9 depends on Task 7 (history table) and touches the same `update()`/`updateOrderStatus()` methods Phase 1 already hardened.

### Task 9: Real `OrderStatus` lifecycle enum + transition-guard service

**Files:**
- Modify: `app/Enums/OrderStatus.php` (full rewrite)
- Create: `database/migrations/2026_07_14_100000_convert_orders_status_to_flexible_string.php`
- Create: `app/Services/OrderWorkflowService.php`
- Create: `app/Exceptions/InvalidOrderTransitionException.php`
- Test: `tests/Unit/OrderStatusEnumTest.php`
- Test: `tests/Feature/OrderWorkflowServiceTest.php`

**Interfaces:**
- Produces on `OrderStatus` (now holds the real fulfillment lifecycle, decoupled from payment which stays on `orders.is_paid`):
  ```php
  const PLACED = 'placed';
  const PICKUP_SCHEDULED = 'pickup_scheduled';
  const AT_FACILITY = 'at_facility';
  const SORTING = 'sorting';
  const WASHING = 'washing';
  const READY_FOR_DELIVERY = 'ready_for_delivery';
  const OUT_FOR_DELIVERY = 'out_for_delivery';
  const DELIVERED = 'delivered';
  const CANCELLED = 'cancelled';
  ```
  plus `public static function all(): array`, `public static function transitionsFrom(string $status): array` (returns the list of legal next statuses), `public static function label(string $status): string` (translation key lookup).
- Produces: `App\Services\OrderWorkflowService::transition(Order $order, string $toStatus, string $actorType, int $actorId, ?string $note = null): Order` — locks the order row, validates the transition against `OrderStatus::transitionsFrom()`, throws `InvalidOrderTransitionException` if illegal, updates `orders.status`, writes an `OrderStatusHistory` row (Task 7), returns the fresh `Order`.
- **Data migration decision (documented here, not re-derived at implementation time):** the existing `orders.status` column currently holds only `Pending`, `Completed`, or `Cancelled` in practice (Processing/Shipped are dead — confirmed in Context Snapshot). The migration maps: existing `Pending` (unpaid or paid-but-not-yet-fulfilled orders — cannot be distinguished retroactively from `status` alone, see note below) → `placed`; existing `Completed` → `delivered` (best-effort backfill assumption: historically, "Completed" was set the instant payment cleared, so we cannot know from `status` alone whether the physical order was ever actually delivered — this migration treats all historical `Completed` orders as `delivered` since they are closed/paid and there is no way to recover true fulfillment state for old data; this is a one-time, one-way backfill approximation and must be called out to stakeholders before running in production); existing `Cancelled` → `cancelled`.
- Consumes: Task 7's `OrderStatusHistory` model.

- [ ] **Step 1: Write the failing enum test**

This is a plain PHPUnit `TestCase` (not Laravel's `Tests\TestCase`) — it needs no DB/app boot, it's pure enum logic.

```php
<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusEnumTest extends TestCase
{
    public function test_placed_can_only_move_to_pickup_scheduled_or_cancelled(): void
    {
        $this->assertEqualsCanonicalizing(
            [OrderStatus::PICKUP_SCHEDULED, OrderStatus::CANCELLED],
            OrderStatus::transitionsFrom(OrderStatus::PLACED)
        );
    }

    public function test_delivered_and_cancelled_are_terminal(): void
    {
        $this->assertSame([], OrderStatus::transitionsFrom(OrderStatus::DELIVERED));
        $this->assertSame([], OrderStatus::transitionsFrom(OrderStatus::CANCELLED));
    }

    public function test_full_happy_path_is_linear_and_legal(): void
    {
        $path = [
            OrderStatus::PLACED, OrderStatus::PICKUP_SCHEDULED, OrderStatus::AT_FACILITY,
            OrderStatus::SORTING, OrderStatus::WASHING, OrderStatus::READY_FOR_DELIVERY,
            OrderStatus::OUT_FOR_DELIVERY, OrderStatus::DELIVERED,
        ];
        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertContains($path[$i + 1], OrderStatus::transitionsFrom($path[$i]),
                "{$path[$i]} should be able to move to {$path[$i+1]}");
        }
    }

    public function test_cannot_jump_from_placed_to_ready_for_delivery(): void
    {
        $this->assertNotContains(OrderStatus::READY_FOR_DELIVERY, OrderStatus::transitionsFrom(OrderStatus::PLACED));
    }

    public function test_every_non_terminal_status_can_be_cancelled(): void
    {
        foreach (OrderStatus::all() as $status) {
            if (in_array($status, [OrderStatus::DELIVERED, OrderStatus::CANCELLED], true)) {
                continue;
            }
            $this->assertContains(OrderStatus::CANCELLED, OrderStatus::transitionsFrom($status),
                "{$status} should always be cancellable");
        }
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=OrderStatusEnumTest`
Expected: FAIL — `transitionsFrom`/`all` don't exist, old constants don't match.

- [ ] **Step 3: Rewrite `app/Enums/OrderStatus.php`**

```php
<?php

namespace App\Enums;

class OrderStatus
{
    const PLACED = 'placed';
    const PICKUP_SCHEDULED = 'pickup_scheduled';
    const AT_FACILITY = 'at_facility';
    const SORTING = 'sorting';
    const WASHING = 'washing';
    const READY_FOR_DELIVERY = 'ready_for_delivery';
    const OUT_FOR_DELIVERY = 'out_for_delivery';
    const DELIVERED = 'delivered';
    const CANCELLED = 'cancelled';

    private const TRANSITIONS = [
        self::PLACED => [self::PICKUP_SCHEDULED, self::CANCELLED],
        self::PICKUP_SCHEDULED => [self::AT_FACILITY, self::CANCELLED],
        self::AT_FACILITY => [self::SORTING, self::CANCELLED],
        self::SORTING => [self::WASHING, self::CANCELLED],
        self::WASHING => [self::READY_FOR_DELIVERY, self::CANCELLED],
        self::READY_FOR_DELIVERY => [self::OUT_FOR_DELIVERY, self::CANCELLED],
        self::OUT_FOR_DELIVERY => [self::DELIVERED, self::CANCELLED],
        self::DELIVERED => [],
        self::CANCELLED => [],
    ];

    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    public static function transitionsFrom(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitionsFrom($from), true);
    }

    public static function label(string $status): string
    {
        return __('messages.order_status_' . $status);
    }
}
```

> Add the 9 new `order_status_<value>` translation keys to `resources/lang/en/messages.php` and `resources/lang/ar/messages.php` (read both files first to match existing key-naming/quoting style before inserting) — e.g. `'order_status_placed' => 'Placed', 'order_status_pickup_scheduled' => 'Pickup Scheduled', ...` and the Arabic equivalents. This is required for `statusTranslated()` on `Order` (existing method, currently does `__('messages.' . strtolower($this->status))` — update it in Step 6 below to call `OrderStatus::label($this->status)` instead, since the old convention of lowercasing the status string no longer matches the new snake_case constants cleanly for multi-word ones like `pickup_scheduled`).

- [ ] **Step 4: Run the enum test to verify it passes**

Run: `php artisan test --filter=OrderStatusEnumTest`
Expected: PASS.

- [ ] **Step 5: Write the migration converting `orders.status` from ENUM to flexible string, with data backfill**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // No doctrine/dbal installed — use raw SQL, MySQL-specific (this codebase
        // already assumes MySQL elsewhere, e.g. DATE_FORMAT() in EmployeeDashboardController).
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'placed'");

        // One-time backfill of historical data. See Task 9 interface notes in the
        // implementation plan for why 'Completed' maps to 'delivered': historically
        // 'Completed' was set the instant payment cleared, not when the order was
        // actually delivered, so true fulfillment state for old rows is unrecoverable —
        // this is a best-effort approximation, not a precise migration.
        DB::table('orders')->where('status', 'Pending')->update(['status' => 'placed']);
        DB::table('orders')->where('status', 'Processing')->update(['status' => 'at_facility']);
        DB::table('orders')->where('status', 'Shipped')->update(['status' => 'out_for_delivery']);
        DB::table('orders')->where('status', 'Completed')->update(['status' => 'delivered']);
        DB::table('orders')->where('status', 'Cancelled')->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'placed')->update(['status' => 'Pending']);
        DB::table('orders')->where('status', 'pickup_scheduled')->update(['status' => 'Pending']);
        DB::table('orders')->where('status', 'at_facility')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'sorting')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'washing')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'ready_for_delivery')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'out_for_delivery')->update(['status' => 'Shipped']);
        DB::table('orders')->where('status', 'delivered')->update(['status' => 'Completed']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'Cancelled']);

        DB::statement("ALTER TABLE orders MODIFY status ENUM('Pending','Processing','Shipped','Completed','Cancelled') NOT NULL DEFAULT 'Pending'");
    }
};
```

- [ ] **Step 6: Update `Order::statusTranslated()` and `canApplyDiscount()` for the new values**

In `app/Models/Order.php`:
```php
    public function statusTranslated()
    {
        return \App\Enums\OrderStatus::label($this->status);
    }

    public function canApplyDiscount(): bool
    {
        return in_array($this->status, [\App\Enums\OrderStatus::PLACED, \App\Enums\OrderStatus::PICKUP_SCHEDULED]);
    }
```
(The old `canApplyDiscount()` checked for `['draft', 'pending']` — neither of which was ever a real `OrderStatus` constant to begin with, `'draft'` never existed and `'pending'` lowercase never matched the actual `'Pending'` constant; this was already dead/no-op logic. The new version fixes it to actually work, gating discount-eligibility to the two earliest real lifecycle states.)

- [ ] **Step 7: Create `InvalidOrderTransitionException`**

```php
<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderTransitionException extends Exception
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Cannot transition order from '{$from}' to '{$to}'.");
    }
}
```

- [ ] **Step 8: Write the failing `OrderWorkflowService` test**

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_transition_updates_status_and_records_history(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000080', 'balance' => 0]);
        $employee = User::factory()->create(['user_type' => 'employee', 'mobile' => '50000081', 'balance' => 0]);
        $order = Order::create(['user_id' => $client->id, 'sum_price' => 10, 'status' => OrderStatus::PLACED]);

        $service = app(OrderWorkflowService::class);
        $updated = $service->transition($order, OrderStatus::PICKUP_SCHEDULED, 'employee', $employee->id, 'Driver assigned');

        $this->assertSame(OrderStatus::PICKUP_SCHEDULED, $updated->status);
        $this->assertCount(1, $order->fresh()->statusHistories);
        $history = $order->fresh()->statusHistories->first();
        $this->assertSame(OrderStatus::PLACED, $history->from_status);
        $this->assertSame(OrderStatus::PICKUP_SCHEDULED, $history->to_status);
        $this->assertSame('employee', $history->changed_by_type);
        $this->assertSame($employee->id, $history->changed_by_id);
        $this->assertSame('Driver assigned', $history->note);
    }

    public function test_illegal_transition_throws_and_does_not_mutate_order(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000082', 'balance' => 0]);
        $employee = User::factory()->create(['user_type' => 'employee', 'mobile' => '50000083', 'balance' => 0]);
        $order = Order::create(['user_id' => $client->id, 'sum_price' => 10, 'status' => OrderStatus::PLACED]);

        $service = app(OrderWorkflowService::class);

        $this->expectException(InvalidOrderTransitionException::class);
        try {
            $service->transition($order, OrderStatus::READY_FOR_DELIVERY, 'employee', $employee->id);
        } finally {
            $this->assertSame(OrderStatus::PLACED, $order->fresh()->status);
            $this->assertCount(0, $order->fresh()->statusHistories);
        }
    }
}
```

- [ ] **Step 9: Run the test to verify it fails**

Run: `php artisan test --filter=OrderWorkflowServiceTest`
Expected: FAIL — `OrderWorkflowService` doesn't exist.

- [ ] **Step 10: Write `OrderWorkflowService`**

```php
<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    public function transition(Order $order, string $toStatus, string $actorType, int $actorId, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $toStatus, $actorType, $actorId, $note) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $locked->status;

            if (!OrderStatus::canTransition($fromStatus, $toStatus)) {
                throw new InvalidOrderTransitionException($fromStatus, $toStatus);
            }

            $locked->update(['status' => $toStatus]);

            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_type' => $actorType,
                'changed_by_id' => $actorId,
                'note' => $note,
            ]);

            return $locked;
        });
    }
}
```

- [ ] **Step 11: Run the migration and both tests**

Run: `php artisan migrate` then `php artisan test --filter=OrderWorkflowServiceTest` and `php artisan test --filter=OrderStatusEnumTest`
Expected: All PASS.

- [ ] **Step 12: Run the full suite to find every call site that still references the old status strings**

Run: `php artisan test`
Expected: Failures will surface in every existing test/controller path that hardcoded `OrderStatus::PENDING`/`COMPLETED`/etc. or the old string literals — this is the checklist for Task 10, which fixes each call site. Do not fix them in this task; just confirm the failure list matches the known call sites from the Context Snapshot table (`OrdersController`, `DriverController`, `DriverDashboardController`, `ClientDashboardController`, `ClientController`, `AdminDashboardController`, `EmployeeDashboardController`, the 6 Blade views, `OrderSeeder`).

- [ ] **Step 13: Commit**

```bash
git add app/Enums/OrderStatus.php app/Services/OrderWorkflowService.php app/Exceptions/InvalidOrderTransitionException.php \
        app/Models/Order.php database/migrations/2026_07_14_100000_convert_orders_status_to_flexible_string.php \
        resources/lang/en/messages.php resources/lang/ar/messages.php \
        tests/Unit/OrderStatusEnumTest.php tests/Feature/OrderWorkflowServiceTest.php
git commit -m "feat: replace OrderStatus enum with real fulfillment lifecycle + guarded transition service"
```

---

### Task 10: Wire every call site to the new lifecycle + decouple payment from fulfillment

**Files:**
- Modify: `app/Http/Controllers/Order/OrdersController.php` (all `OrderStatus::` references, `update()` status field, `store()`/`pay()` payment branches)
- Modify: `app/Http/Controllers/Driver/DriverController.php` (`updateOrderStatus()`, `deliveryOrders()`, `deliveryHistory()`)
- Modify: `app/Http/Controllers/Driver/DriverDashboardController.php`
- Modify: `app/Http/Controllers/Client/ClientDashboardController.php`, `app/Http/Controllers/Client/ClientController.php`
- Modify: `app/Http/Controllers/Admin/AdminDashboardController.php`, `app/Http/Controllers/Employee/EmployeeDashboardController.php`
- Modify: `resources/views/orders/index.blade.php`, `resources/views/orders/show.blade.php`, `resources/views/orders/edit.blade.php`, `resources/views/admin/users/show.blade.php`, `resources/views/client/bills/index.blade.php`, `resources/views/driver/orders/details.blade.php`
- Modify: `database/seeders/OrderSeeder.php`
- Test: `tests/Feature/OrderPaymentFulfillmentDecoupledTest.php`, `tests/Feature/OrderCancellationRefundTest.php`

**Interfaces:**
- Produces: `store()`/`pay()` no longer set `status => OrderStatus::COMPLETED` on payment — they set only `is_paid => true` and leave `status` at whatever it already is (starts at `OrderStatus::PLACED` from `store()`, unaffected by payment). Fulfillment progress is now driven exclusively through `OrderWorkflowService::transition()`.
- Produces: `OrdersController::update()`'s status field now calls `app(OrderWorkflowService::class)->transition($order, $request->order_status, $actorType, auth()->id(), $request->input('status_note'))` instead of including `status` in the raw `$orderData` array passed to `$order->update()`. Actor type is resolved via a small local helper (Step 4).
- Produces: **Finding F6 fix** — transitioning to `OrderStatus::CANCELLED` through the service now triggers a refund via `User::adjustBalance`/`adjustPoints` (Task 2's helpers) if `$order->is_paid` is true and it hasn't already been refunded (new `orders.refunded_at` timestamp column, added in this task's migration, prevents double-refund if cancelled twice — though the state machine already makes `CANCELLED` terminal so a second cancel attempt would throw `InvalidOrderTransitionException` first; `refunded_at` is a defense-in-depth belt-and-suspenders column, not the primary guard).
- Produces: **Finding F8 fix** — the `update()` method's discount-clearing raw `DB::table('orders')->update()` + `$order->refresh()` pattern (lines 741-757 in the original) is restructured so the payment-method reversal math (previously reading `getOriginal()`) captures `$originalPaymentMethod`/`$originalPointsUsed`/`$originalPrice` into **local variables before any update touches the model**, not via `getOriginal()` after a `refresh()`. This removes the bug's root cause entirely rather than working around `refresh()` timing.

- [ ] **Step 1: Write the failing payment/fulfillment decoupling test**

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPaymentFulfillmentDecoupledTest extends TestCase
{
    use RefreshDatabase;

    public function test_paying_for_an_order_does_not_mark_it_delivered(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000090', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000091', 'balance' => 100]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $response = $this->actingAs($admin, 'admin')->post(route('orders.store'), [
            'user_id' => $client->id,
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'money',
        ]);

        $order = Order::latest('id')->first();

        $this->assertTrue($order->is_paid);
        $this->assertSame(OrderStatus::PLACED, $order->status);
        $this->assertNotEquals(OrderStatus::DELIVERED, $order->status);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=OrderPaymentFulfillmentDecoupledTest`
Expected: FAIL — `store()` still sets `status => OrderStatus::COMPLETED` (which no longer exists as a constant after Task 9, so this will currently error/fail hard, confirming the call site needs fixing).

- [ ] **Step 3: Update `OrdersController::store()` and `pay()` to stop conflating payment with fulfillment**

In `store()`, replace every occurrence of `$order->update(['status' => OrderStatus::COMPLETED, 'is_paid' => true]);` with `$order->update(['is_paid' => true]);` (3 occurrences: points branch, money branch; the `knet` branch already doesn't touch `status`, it stays `Pending`→`placed` until callback).

In `KnetService::handleCallback()` (already modified in Task 1/2), the `order` branch currently does:
```php
Order::where('id', $details['order_id'])->update(['status' => OrderStatus::COMPLETED, 'is_paid' => true]);
```
change to:
```php
Order::where('id', $details['order_id'])->update(['is_paid' => true]);
```

In `pay()`, replace both `'status' => OrderStatus::COMPLETED,` lines (points branch and money branch) — remove the `status` key entirely from those `update()` calls, keep `is_paid => true`.

- [ ] **Step 4: Add an actor-type resolver helper and rewire `OrdersController::update()`'s status handling**

Add a small private method to `OrdersController`:
```php
    private function currentActorType(): string
    {
        foreach (['admin', 'employee', 'driver', 'client'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return $guard;
            }
        }
        return 'system';
    }
```

In `update()`, remove `'status' => $request->order_status,` from the `$orderData` array entirely. After the existing `$order->update($orderData);` call (and after the existing discount-clearing block — see Step 5 for that block's own fix), add:
```php
            if ($request->filled('order_status') && $request->order_status !== $order->status) {
                app(\App\Services\OrderWorkflowService::class)->transition(
                    $order,
                    $request->order_status,
                    $this->currentActorType(),
                    auth()->id(),
                    $request->input('status_note')
                );
                $order->refresh();
            }
```
Update the `editRules` validation array: replace the `order_status` rule's `in:` list (currently the 5 old constants) with `implode(',', \App\Enums\OrderStatus::all())`.

- [ ] **Step 5: Fix the `getOriginal()`/`refresh()` bug (F8) by capturing originals before any mutation**

At the very top of `update()`, immediately after `DB::beginTransaction();`, before any `$order->update()` call happens anywhere in the method, add:
```php
            // Captured up front, before any mutation, so the balance-reversal math
            // below is never affected by an intermediate refresh()/update() call.
            $originalPaymentMethod = $order->payment_method ?? 'money';
            $originalPointsUsed = (float) ($order->points_used ?? 0);
            $originalPrice = (float) $order->sum_price;
```
Then delete the later duplicate lines that used to compute these from `getOriginal()` (`$originalPrice = ($order->sum_price);` right before `$orderData['status'] = ...` used to be, and the `$originalPaymentMethod = $order->getOriginal('payment_method') ...` / `$originalPointsUsed = (float) ($order->getOriginal('points_used') ...)` lines near the reversal block) — the reversal block now just uses these three pre-captured local variables directly, unchanged otherwise.

- [ ] **Step 6: Implement the cancellation refund (F6) inside `OrderWorkflowService`**

Add a `refunded_at` column via migration:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('is_paid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refunded_at');
        });
    }
};
```
Save as `database/migrations/2026_07_14_100100_add_refunded_at_to_orders_table.php`.

Update `OrderWorkflowService::transition()` to refund on cancellation:
```php
<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    public function transition(Order $order, string $toStatus, string $actorType, int $actorId, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $toStatus, $actorType, $actorId, $note) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $locked->status;

            if (!OrderStatus::canTransition($fromStatus, $toStatus)) {
                throw new InvalidOrderTransitionException($fromStatus, $toStatus);
            }

            $locked->update(['status' => $toStatus]);

            if ($toStatus === OrderStatus::CANCELLED && $locked->is_paid && !$locked->refunded_at) {
                if ($locked->payment_method === 'points') {
                    User::adjustPoints($locked->user_id, (float) $locked->points_used);
                } else {
                    User::adjustBalance($locked->user_id, (float) $locked->sum_price);
                }
                $locked->update(['refunded_at' => now()]);
            }

            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_type' => $actorType,
                'changed_by_id' => $actorId,
                'note' => $note,
            ]);

            return $locked;
        });
    }
}
```

Write the failing-then-passing test for this before implementing (TDD — do this sub-step properly):

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_a_paid_order_refunds_the_customer_balance(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000100', 'balance' => 0]);
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000101', 'balance' => 0]);

        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 40,
            'status' => OrderStatus::PLACED,
            'is_paid' => true,
            'payment_method' => 'money',
        ]);

        app(OrderWorkflowService::class)->transition($order, OrderStatus::CANCELLED, 'admin', $admin->id, 'Customer requested cancellation');

        $client->refresh();
        $order->refresh();

        $this->assertEquals(40.0, (float) $client->balance);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(OrderStatus::CANCELLED, $order->status);
    }

    public function test_cancelling_a_paid_points_order_refunds_points(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000102', 'points_balance' => 0, 'balance' => 0]);
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000103', 'balance' => 0]);

        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 40,
            'status' => OrderStatus::PLACED,
            'is_paid' => true,
            'payment_method' => 'points',
            'points_used' => 15,
        ]);

        app(OrderWorkflowService::class)->transition($order, OrderStatus::CANCELLED, 'admin', $admin->id);

        $client->refresh();
        $this->assertEquals(15.0, (float) $client->points_balance);
    }
}
```

Run: `php artisan test --filter=OrderCancellationRefundTest`
Expected: FAIL first (before Step 6's service change), then PASS after.

- [ ] **Step 7: Fix `DriverController` (`updateOrderStatus`, `deliveryOrders`, `deliveryHistory`)**

`updateOrderStatus()` (already hardened for authz in Task 3) — replace the hand-rolled balance logic and raw `$order->update(['status' => $status])` with the workflow service, and change the "completed" comparison from `OrderStatus::COMPLETED` (no longer exists) to `OrderStatus::DELIVERED`:
```php
    public function updateOrderStatus(Order $order, string $status)
    {
        $delivery = $order->orderDelivery;

        if (!$delivery) {
            abort(404);
        }

        if ((int) $delivery->user_id !== (int) Auth::guard('driver')->id()) {
            abort(403);
        }

        if (!in_array($status, \App\Enums\OrderStatus::all(), true)) {
            abort(422, __('messages.validation_order_status_invalid'));
        }

        $wasDelivered = $order->status === OrderStatus::DELIVERED;

        try {
            $order = app(\App\Services\OrderWorkflowService::class)
                ->transition($order, $status, 'driver', Auth::id());
        } catch (\App\Exceptions\InvalidOrderTransitionException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        $nowDelivered = $order->status === OrderStatus::DELIVERED;

        if ($wasDelivered && !$nowDelivered) {
            $driver = Auth::user();
            $driver->decrement('balance', $delivery->price);
            $driver->save();
        } elseif (!$wasDelivered && $nowDelivered) {
            $driver = Auth::user();
            $driver->increment('balance', $delivery->price);
            $driver->save();
            $driver->refresh();
            $this->notificationService->sendTransactionNotification($driver, 'driver_delivery_completed', [
                'amount' => $delivery->price,
                'balance' => $driver->balance,
            ]);
        }

        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }
```
`deliveryOrders()` and `deliveryHistory()`: replace `->where('status', '!=', OrderStatus::CANCELLED)->where('status', '!=', OrderStatus::COMPLETED)` with `->where('status', '!=', OrderStatus::CANCELLED)->where('status', '!=', OrderStatus::DELIVERED)`, and `deliveryHistory()`'s `->where('status', OrderStatus::COMPLETED)` with `->where('status', OrderStatus::DELIVERED)`.

- [ ] **Step 8: Fix `DriverDashboardController`, `ClientDashboardController`, `ClientController`, `AdminDashboardController`, `EmployeeDashboardController`**

In each, replace every `OrderStatus::COMPLETED` with `OrderStatus::DELIVERED`, and in `AdminDashboardController`/`EmployeeDashboardController`'s status-list arrays (`[OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::COMPLETED, OrderStatus::CANCELLED]`) replace with `OrderStatus::all()`.

- [ ] **Step 9: Fix the 6 Blade views**

In each of `resources/views/orders/index.blade.php`, `resources/views/orders/show.blade.php`, `resources/views/admin/users/show.blade.php`, `resources/views/client/bills/index.blade.php`: replace `App\Enums\OrderStatus::COMPLETED`/`PENDING` badge-color checks with `App\Enums\OrderStatus::DELIVERED`/`PLACED` respectively (read each file's exact surrounding ternary before editing — do not guess the full line, only the enum-constant tokens change).

`resources/views/orders/edit.blade.php`: replace the 5 hardcoded `<option>` tags (lines 75-79) with a loop:
```blade
@foreach (\App\Enums\OrderStatus::all() as $statusValue)
    <option value="{{ $statusValue }}" {{ $selectedStatus === $statusValue ? 'selected' : '' }}>
        {{ \App\Enums\OrderStatus::label($statusValue) }}
    </option>
@endforeach
```
Also add a `status_note` text input near this select (used by Step 4's `$request->input('status_note')`), e.g.:
```blade
<div class="mb-3">
    <label for="status_note" class="form-label">{{ __('messages.status_change_note') }}</label>
    <input type="text" name="status_note" id="status_note" class="form-control" maxlength="500">
</div>
```
(Add the `status_change_note` translation key to both lang files alongside the `order_status_*` keys from Task 9.)

`resources/views/driver/orders/details.blade.php`: replace the two `App\Enums\OrderStatus::SHIPPED`/`COMPLETED` route-param usages (lines 66, 72) with the actual next legal statuses for a driver's action buttons — `App\Enums\OrderStatus::OUT_FOR_DELIVERY` and `App\Enums\OrderStatus::DELIVERED` respectively (read the surrounding button labels first to keep copy consistent, only the enum token changes).

- [ ] **Step 10: Fix `database/seeders/OrderSeeder.php`**

Replace the array of `[OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::COMPLETED, OrderStatus::CANCELLED]` with `OrderStatus::all()`.

- [ ] **Step 11: Run the full test suite**

Run: `php artisan test`
Expected: All PASS, including `OrderPaymentFulfillmentDecoupledTest`, `OrderCancellationRefundTest`, and everything from Phases 1-2.

- [ ] **Step 12: Manually smoke-test the admin edit-order screen and driver detail screen**

Run: `php artisan serve`, log in as admin, open an order's edit screen, confirm the status dropdown now shows the 9 new lifecycle labels and an illegal jump (e.g. Placed → Ready for Delivery) shows the `InvalidOrderTransitionException` message via `back()->withErrors(...)` rather than silently succeeding. Log in as the assigned driver for a test order, confirm the two action buttons on the details page move it through `out_for_delivery` → `delivered` correctly and driver balance updates once.

- [ ] **Step 13: Commit**

```bash
git add app/Http/Controllers/Order/OrdersController.php app/Http/Controllers/Driver/DriverController.php \
        app/Http/Controllers/Driver/DriverDashboardController.php app/Http/Controllers/Client/ClientDashboardController.php \
        app/Http/Controllers/Client/ClientController.php app/Http/Controllers/Admin/AdminDashboardController.php \
        app/Http/Controllers/Employee/EmployeeDashboardController.php app/Services/OrderWorkflowService.php \
        app/Services/KnetService.php resources/views/orders/index.blade.php resources/views/orders/show.blade.php \
        resources/views/orders/edit.blade.php resources/views/admin/users/show.blade.php \
        resources/views/client/bills/index.blade.php resources/views/driver/orders/details.blade.php \
        resources/lang/en/messages.php resources/lang/ar/messages.php database/seeders/OrderSeeder.php \
        database/migrations/2026_07_14_100100_add_refunded_at_to_orders_table.php \
        tests/Feature/OrderPaymentFulfillmentDecoupledTest.php tests/Feature/OrderCancellationRefundTest.php
git commit -m "feat: decouple payment from fulfillment status, wire all call sites to the guarded workflow, fix cancellation refund"
```

---

## Phase 4 — P3: Mid-cycle repricing (weight/item-count correction)

Do this after Phase 3 — it depends on `OrderWorkflowService` and the `AT_FACILITY`/`SORTING` states existing.

### Task 11: Facility re-weigh/re-price flow with re-authorization

**Files:**
- Create: `database/migrations/2026_07_15_100000_add_repricing_fields_to_orders_table.php`
- Modify: `app/Http/Controllers/Order/OrdersController.php` (new `reprice()` action)
- Modify: `routes/web.php`
- Test: `tests/Feature/OrderRepricingTest.php`

**Interfaces:**
- Produces columns on `orders`: `repriced_amount` (decimal 10,2, nullable), `repriced_at` (timestamp, nullable), `repriced_by` (FK users, nullable), `requires_additional_payment` (boolean, default false).
- Produces route: `PUT /orders/{order}/reprice` → `OrdersController::reprice(Request $request, Order $order)`, staff-only (`admin`/`employee` guard), only legal when `$order->status` is `AT_FACILITY` or `SORTING` (the two stages where a physical recount happens).
- Behavior: if new computed total (from a resubmitted `order_product_services` line-item array, reusing the exact same per-line `ProductServicePrice` lookup logic already in `store()`/`update()`) is **less than or equal to** the original `sum_price`, apply it immediately (no reauthorization needed — this is a data-integrity note about actual behavior, not a security control) — refund the difference via `User::adjustBalance`/`adjustPoints` if `is_paid`. If the new total is **greater**, do **not** silently charge more: set `requires_additional_payment = true`, `repriced_amount = <new total>`, leave `sum_price` unchanged until the customer completes an additional KNET charge for the delta (reuses `KnetService::createOrderPayment()` for the delta amount, reuses the existing `is_paid` semantics — the order remains `is_paid = true` for the original amount; a second `Payment` row tracks the delta).

- [ ] **Step 1: Write the failing test for the down-reprice (refund) path**

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepricingTest extends TestCase
{
    use RefreshDatabase;

    private function orderAtFacility(User $client, Product $product, ProductService $service): Order
    {
        $order = Order::create([
            'user_id' => $client->id, 'sum_price' => 20, 'status' => OrderStatus::PLACED,
            'is_paid' => true, 'payment_method' => 'money',
        ]);
        $order->orderProductServices()->create([
            'product_id' => $product->id, 'product_service_id' => $service->id,
            'quantity' => 4, 'price_at_order' => 5,
        ]);
        app(OrderWorkflowService::class)->transition($order, OrderStatus::PICKUP_SCHEDULED, 'admin', $client->id);
        app(OrderWorkflowService::class)->transition($order, OrderStatus::AT_FACILITY, 'admin', $client->id);
        return $order;
    }

    public function test_reweighing_to_a_lower_total_refunds_the_difference_immediately(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000110', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000111', 'balance' => 0]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $order = $this->orderAtFacility($client, $product, $service);

        $this->actingAs($admin, 'admin')->put(route('orders.reprice', $order), [
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 2],
            ],
        ]);

        $order->refresh();
        $client->refresh();

        $this->assertEquals(10.0, (float) $order->sum_price);
        $this->assertEquals(10.0, (float) $client->balance); // refunded the 10 difference
        $this->assertFalse($order->requires_additional_payment);
    }

    public function test_reweighing_to_a_higher_total_requires_additional_payment_and_does_not_auto_charge(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000112', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000113', 'balance' => 0]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $order = $this->orderAtFacility($client, $product, $service);

        $this->actingAs($admin, 'admin')->put(route('orders.reprice', $order), [
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 8],
            ],
        ]);

        $order->refresh();
        $client->refresh();

        $this->assertEquals(20.0, (float) $order->sum_price); // unchanged — not silently charged
        $this->assertEquals(0.0, (float) $client->balance); // not debited automatically
        $this->assertTrue($order->requires_additional_payment);
        $this->assertEquals(40.0, (float) $order->repriced_amount);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=OrderRepricingTest`
Expected: FAIL — route/columns/method don't exist yet.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('repriced_amount', 10, 2)->nullable()->after('sum_price');
            $table->timestamp('repriced_at')->nullable()->after('repriced_amount');
            $table->foreignId('repriced_by')->nullable()->after('repriced_at')
                ->constrained('users')->onDelete('set null');
            $table->boolean('requires_additional_payment')->default(false)->after('repriced_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['repriced_by']);
            $table->dropColumn(['repriced_amount', 'repriced_at', 'repriced_by', 'requires_additional_payment']);
        });
    }
};
```

- [ ] **Step 4: Add fillable/casts for the new columns**

In `app/Models/Order.php`, add `'repriced_amount', 'repriced_at', 'repriced_by', 'requires_additional_payment',` to `$fillable`, and `'repriced_amount' => 'decimal:2', 'repriced_at' => 'datetime', 'requires_additional_payment' => 'boolean',` to `$casts`.

- [ ] **Step 5: Add the route**

In `routes/web.php`, in the same route group as the other `orders.*` routes (find `Route::put('orders/{order}', [OrdersController::class, 'update'])` or similar and add immediately after it):
```php
Route::put('orders/{order}/reprice', [OrdersController::class, 'reprice'])->name('orders.reprice')->middleware('auth:admin,employee');
```

- [ ] **Step 6: Implement `OrdersController::reprice()`**

```php
    public function reprice(Request $request, Order $order)
    {
        if (!in_array($order->status, [\App\Enums\OrderStatus::AT_FACILITY, \App\Enums\OrderStatus::SORTING], true)) {
            return back()->withErrors(['message' => __('messages.reprice_only_at_facility')]);
        }

        $request->validate([
            'order_product_services' => 'required|array',
            'order_product_services.*.product_id' => 'required|exists:products,id',
            'order_product_services.*.product_service_id' => 'required|exists:product_services,id',
            'order_product_services.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $newTotal = 0;
            $lines = [];
            foreach ($request->order_product_services as $lineData) {
                $productServicePrice = ProductServicePrice::where('product_id', $lineData['product_id'])
                    ->where('product_service_id', $lineData['product_service_id'])
                    ->first();

                if (!$productServicePrice) {
                    DB::rollBack();
                    return back()->withErrors(['message' => __('messages.product_no_services_warning')]);
                }

                $newTotal += $productServicePrice->price * $lineData['quantity'];
                $lines[] = array_merge($lineData, ['price_at_order' => $productServicePrice->price]);
            }

            $order->orderProductServices()->delete();
            foreach ($lines as $line) {
                $order->orderProductServices()->create($line);
            }

            $originalTotal = (float) $order->sum_price;
            $delta = $newTotal - $originalTotal;

            if ($delta <= 0) {
                // New total is lower or equal — apply immediately and refund the difference.
                if ($order->is_paid && $delta < 0) {
                    if ($order->payment_method === 'points') {
                        User::adjustPoints($order->user_id, -$delta); // -$delta is positive here
                    } else {
                        User::adjustBalance($order->user_id, -$delta);
                    }
                }
                $order->update([
                    'sum_price' => $newTotal,
                    'repriced_amount' => null,
                    'repriced_at' => now(),
                    'repriced_by' => auth()->id(),
                    'requires_additional_payment' => false,
                ]);
            } else {
                // New total is higher — do not silently charge more. Flag for
                // additional authorization; sum_price stays at the originally
                // authorized amount until the customer completes the extra payment.
                $order->update([
                    'repriced_amount' => $newTotal,
                    'repriced_at' => now(),
                    'repriced_by' => auth()->id(),
                    'requires_additional_payment' => true,
                ]);
            }

            DB::commit();
            return redirect()->route('orders.show', $order->id)->with('success', __('messages.order_repriced_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error repricing order: ' . $e->getMessage());
            return back()->withErrors(['message' => __('messages.order_error_try_again')]);
        }
    }
```

- [ ] **Step 7: Run the tests to verify they pass**

Run: `php artisan test --filter=OrderRepricingTest`
Expected: Both PASS.

- [ ] **Step 8: Add the additional-payment collection endpoint (only for the "requires more money" branch)**

Add to `OrdersController`:
```php
    public function payRepriceDelta(Order $order)
    {
        if (!$order->requires_additional_payment || !$order->repriced_amount) {
            return back()->withErrors(['message' => __('messages.no_additional_payment_due')]);
        }

        $delta = (float) $order->repriced_amount - (float) $order->sum_price;

        $result = $this->knetService->createOrderPayment($delta, $order->user_id, $order->id);

        if ($result['status'] !== 'success') {
            return back()->withErrors(['message' => __('messages.knet_payment_initiation_failed')]);
        }

        return redirect($result['payment_uri']);
    }
```
Route:
```php
Route::get('orders/{order}/reprice/pay', [OrdersController::class, 'payRepriceDelta'])->name('orders.reprice.pay');
```
> Wiring the KNET callback to finalize `sum_price = repriced_amount` and `requires_additional_payment = false` on success reuses the existing `type === 'order'` branch in `KnetService::handleCallback()` — that branch currently only sets `is_paid = true`; extending it to also resolve a pending reprice is a natural follow-up but is **not required for this task's tests to pass** and is explicitly deferred to keep this task's diff reviewable. Leave a `// TODO(reprice): resolve requires_additional_payment on this order if repriced_amount is set` comment at the top of that branch in `KnetService::handleCallback()` so it isn't lost.

- [ ] **Step 9: Add the two translation keys used above**

Add `reprice_only_at_facility`, `order_repriced_successfully`, `no_additional_payment_due` to both `resources/lang/en/messages.php` and `resources/lang/ar/messages.php` (read existing key style first).

- [ ] **Step 10: Commit**

```bash
git add database/migrations/2026_07_15_100000_add_repricing_fields_to_orders_table.php \
        app/Models/Order.php app/Http/Controllers/Order/OrdersController.php routes/web.php \
        resources/lang/en/messages.php resources/lang/ar/messages.php tests/Feature/OrderRepricingTest.php
git commit -m "feat: add facility re-weigh/re-price flow with re-authorization for price increases"
```

---

## Phase 5 — P4: Infrastructure / performance cleanup

Independent of Phases 3-4; can be done any time after Phase 1 (notifications touch the same call sites Task 2 already modified, so do this after Task 2 at minimum to avoid merge conflicts).

### Task 12: Move WhatsApp notifications off the request thread and out of open transactions

**Files:**
- Create: `app/Jobs/SendTransactionNotificationJob.php`
- Modify: `app/Services/NotificationService.php`
- Modify: `config/queue.php` (no code change needed — already configurable; just document `.env` requirement)
- Test: `tests/Feature/NotificationQueuedTest.php`

**Interfaces:**
- Produces: `App\Jobs\SendTransactionNotificationJob implements ShouldQueue`, constructor `(int $userId, string $messageKey, array $replace = [])`, `handle(NotificationService $notificationService)` re-fetches the `User` and calls the existing synchronous send logic.
- Produces: `NotificationService::sendTransactionNotification()` keeps its exact current signature and synchronous behavior (other code may still call it directly if ever needed for a truly-must-block case), but every one of its 5 call sites (Context Snapshot: `OrdersController::store/update/destroy`, `DriverController::updateOrderStatus`, `PaymentController::callback`) switches to `SendTransactionNotificationJob::dispatch(...)` instead of calling the service directly, and — critically — every dispatch call is moved to **after** the enclosing `DB::commit()`, not before.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionNotificationJob;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationQueuedTest extends TestCase
{
    use RefreshDatabase;

    public function test_placing_an_order_queues_a_notification_job_instead_of_sending_inline(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000120', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000121', 'balance' => 100]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $this->actingAs($admin, 'admin')->post(route('orders.store'), [
            'user_id' => $client->id,
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'money',
        ]);

        Queue::assertPushed(SendTransactionNotificationJob::class, function ($job) use ($client) {
            return $job->userId === $client->id && $job->messageKey === 'order_placed_balance';
        });
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=NotificationQueuedTest`
Expected: FAIL — `SendTransactionNotificationJob` doesn't exist.

- [ ] **Step 3: Create the job**

```php
<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTransactionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public int $userId,
        public string $messageKey,
        public array $replace = []
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $notificationService->sendTransactionNotification($user, $this->messageKey, $this->replace);
    }
}
```

- [ ] **Step 4: Replace the 5 direct call sites**

In `app/Http/Controllers/Order/OrdersController.php` — `store()`: the two `$this->notificationService->sendTransactionNotification($user, 'order_placed_balance', ['balance' => $user->balance]);` calls are currently **inside** the open transaction, before `DB::commit()`. Move each call to **after** `DB::commit();` (the very last line before `return redirect()...`), and change it to:
```php
\App\Jobs\SendTransactionNotificationJob::dispatch($user->id, 'order_placed_balance', ['balance' => $user->balance]);
```
Same pattern for `update()`'s `sendTransactionNotification($user, 'order_update_balance', ...)` call (move after `DB::commit()`, dispatch the job) and `destroy()`'s `sendTransactionNotification($user, 'order_deleted_balance', ...)` (move after `DB::commit()`, dispatch the job).

In `app/Http/Controllers/Driver/DriverController.php` — `updateOrderStatus()` (already restructured in Task 10 to use `OrderWorkflowService::transition()`, which has its own internal `DB::transaction()`): move the `sendTransactionNotification($driver, 'driver_delivery_completed', ...)` call to after the `$order = app(OrderWorkflowService::class)->transition(...)` call completes (it already is, structurally, since `transition()` commits internally before returning) and switch it to `\App\Jobs\SendTransactionNotificationJob::dispatch($driver->id, 'driver_delivery_completed', [...]);`.

In `app/Http/Controllers/Client/PaymentController.php` — `callback()`: the `sendTransactionNotification($payment->user, 'payment_completed', ...)` call has no enclosing transaction to worry about (it's after `KnetService::handleCallback()` returns, which manages its own persistence) — switch it to `\App\Jobs\SendTransactionNotificationJob::dispatch($payment->user->id, 'payment_completed', [...]);` directly, no reordering needed.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=NotificationQueuedTest`
Expected: PASS.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: All PASS — `QUEUE_CONNECTION=sync` in the test environment (Global Constraints) means jobs still actually run during other tests that don't `Queue::fake()`, so no other test's assertions about notification side effects should break; if any test elsewhere asserted directly on `WhatsAppService` calls, they'll still see them happen synchronously under `sync`.

- [ ] **Step 7: Document the production queue worker requirement**

Add a note to `.env.example` near `QUEUE_CONNECTION`: `# Run "php artisan queue:work" (or configure Supervisor/Horizon) in production — SendTransactionNotificationJob and other queued work will not run otherwise.`

- [ ] **Step 8: Commit**

```bash
git add app/Jobs/SendTransactionNotificationJob.php app/Http/Controllers/Order/OrdersController.php \
        app/Http/Controllers/Driver/DriverController.php app/Http/Controllers/Client/PaymentController.php \
        .env.example tests/Feature/NotificationQueuedTest.php
git commit -m "perf: queue transaction notifications instead of sending them synchronously inside open DB transactions"
```

---

### Task 13: Fix N+1 `ProductServicePrice` lookups in `OrdersController::show()` and `pay()`

**Files:**
- Modify: `app/Http/Controllers/Order/OrdersController.php`
- Test: `tests/Feature/OrderShowQueryCountTest.php`

**Interfaces:**
- Produces: both methods replace the per-line `ProductServicePrice::where(...)->where(...)->first()` loop with a single upfront query keyed by `product_id`/`product_service_id` pairs, looked up from an in-memory map.

- [ ] **Step 1: Write the failing query-count test**

```php
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderShowQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_does_not_issue_one_product_service_price_query_per_line(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000130', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000131', 'balance' => 0]);
        $order = Order::create(['user_id' => $client->id, 'sum_price' => 20, 'status' => OrderStatus::PLACED, 'is_paid' => false]);

        foreach (range(1, 5) as $i) {
            $product = Product::create(['name' => "Item {$i}"]);
            $service = ProductService::create(['name' => "Service {$i}"]);
            ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5, 'points_price' => 10]);
            $order->orderProductServices()->create([
                'product_id' => $product->id, 'product_service_id' => $service->id,
                'quantity' => 1, 'price_at_order' => 5,
            ]);
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) { $queryCount++; });

        $this->actingAs($admin, 'admin')->get(route('orders.show', $order));

        // Before the fix: 5 lines => 5 separate ProductServicePrice queries (plus other
        // fixed overhead queries for the page). After the fix: exactly 1 batched query
        // for all ProductServicePrice rows regardless of line count. Assert the
        // ProductServicePrice-specific query count directly rather than a fragile total.
        $this->assertLessThanOrEqual(1, $queryCount === 0 ? 0 : $this->countProductServicePriceQueries());
    }

    private array $sqlLog = [];

    protected function setUp(): void
    {
        parent::setUp();
        DB::listen(function ($query) {
            $this->sqlLog[] = $query->sql;
        });
    }

    private function countProductServicePriceQueries(): int
    {
        return count(array_filter($this->sqlLog, fn ($sql) => str_contains($sql, 'product_service_prices')));
    }
}
```
> Simplify if the double-`DB::listen` setup above is awkward — the essential assertion is: **at most 1 query against `product_service_prices` fires for a 5-line order**, however that's measured. Rewrite the test body cleanly once the exact query-log mechanics are confirmed locally; the assertion intent is what matters, not this exact scaffolding.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=OrderShowQueryCountTest`
Expected: FAIL — 5 separate `product_service_prices` queries fire today (one per line).

- [ ] **Step 3: Fix `show()`**

Replace the `$requiredPoints` computation loop:
```php
        $requiredPoints = null;
        if (!$order->is_paid) {
            $pairs = $order->orderProductServices->map(fn ($line) => [$line->product_id, $line->product_service_id]);
            $prices = ProductServicePrice::query()
                ->where(function ($q) use ($pairs) {
                    foreach ($pairs as [$productId, $serviceId]) {
                        $q->orWhere(function ($q2) use ($productId, $serviceId) {
                            $q2->where('product_id', $productId)->where('product_service_id', $serviceId);
                        });
                    }
                })
                ->get()
                ->keyBy(fn ($p) => $p->product_id . ':' . $p->product_service_id);

            $requiredPoints = 0;
            foreach ($order->orderProductServices as $line) {
                $servicePrice = $prices->get($line->product_id . ':' . $line->product_service_id);
                if (!$servicePrice || $servicePrice->points_price === null) {
                    $requiredPoints = null;
                    break;
                }
                $requiredPoints += (float) $servicePrice->points_price * $line->quantity;
            }
        }
```

- [ ] **Step 4: Fix `pay()`'s points branch with the same batched-lookup pattern**

Replace the `foreach ($order->orderProductServices as $line) { $servicePrice = ProductServicePrice::where(...)->first(); ... }` loop in `pay()` with the identical batched-`$prices` lookup from Step 3 (extract to a small private method to avoid duplicating the batching logic twice in the same controller):
```php
    private function batchedProductServicePrices($orderProductServices)
    {
        $pairs = $orderProductServices->map(fn ($line) => [$line->product_id, $line->product_service_id]);
        return ProductServicePrice::query()
            ->where(function ($q) use ($pairs) {
                foreach ($pairs as [$productId, $serviceId]) {
                    $q->orWhere(function ($q2) use ($productId, $serviceId) {
                        $q2->where('product_id', $productId)->where('product_service_id', $serviceId);
                    });
                }
            })
            ->get()
            ->keyBy(fn ($p) => $p->product_id . ':' . $p->product_service_id);
    }
```
Use `$this->batchedProductServicePrices($order->orderProductServices)` in both `show()` (Step 3) and `pay()`, replacing each per-line `->first()` call with `$prices->get($line->product_id . ':' . $line->product_service_id)`.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=OrderShowQueryCountTest`
Expected: PASS.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: All PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Order/OrdersController.php tests/Feature/OrderShowQueryCountTest.php
git commit -m "perf: batch ProductServicePrice lookups in OrdersController::show/pay to eliminate N+1 queries"
```

---

## Execution order summary

1. **Phase 1** (Tasks 1-4) — ship ASAP, independent of each other, can be parallelized across 4 subagents/reviewers.
2. **Phase 2** (Tasks 5-8) — additive schema, safe to parallelize across 4 subagents; Task 6 pairs conceptually with Task 4 but has no hard code dependency ordering.
3. **Phase 3** (Tasks 9-10) — must be sequential (10 depends on 9), and Task 9 depends on Task 7's `OrderStatusHistory` table existing.
4. **Phase 4** (Task 11) — depends on Phase 3 (`OrderWorkflowService`, `AT_FACILITY`/`SORTING` states).
5. **Phase 5** (Tasks 12-13) — independent of Phases 3-4; only real dependency is Task 2 (Phase 1) landing first to avoid touching the same lines twice.
