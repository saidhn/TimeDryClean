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
