<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Province;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NOTE ON COVERAGE LIMITS: this test issues its two assignment requests
 * sequentially within a single PHPUnit process, so it cannot reproduce a
 * true race condition (two requests truly overlapping in time). It mainly
 * guards against a regression of the read-then-write pattern and documents
 * the expected end state (one delivery row, last assignment wins). The real
 * backstop against a duplicate row under genuine concurrency is the
 * combination of this task's `lockForUpdate()` transaction with Task 6's
 * unique index on `order_deliveries.order_id` — the app-level lock alone
 * cannot be proven safe under real parallel requests by a sequential test.
 */
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

        $this->actingAs($admin, 'admin')->post(route('orders.assign'), $payload($driverA));
        $this->actingAs($admin, 'admin')->post(route('orders.assign'), $payload($driverB));

        $this->assertSame(1, $order->fresh()->orderDelivery()->withTrashed()->count());
        $this->assertSame($driverB->id, $order->fresh()->orderDelivery->user_id);
    }
}
