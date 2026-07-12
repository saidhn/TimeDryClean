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
