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

        $order = Order::create(['user_id' => $client->id, 'sum_price' => 10, 'status' => OrderStatus::PLACED]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => OrderStatus::PLACED,
            'to_status' => OrderStatus::DELIVERED,
            'changed_by_type' => 'admin',
            'changed_by_id' => $admin->id,
            'note' => 'Manual completion for testing',
        ]);

        $this->assertCount(1, $order->fresh()->statusHistories);
        $this->assertSame(OrderStatus::DELIVERED, $order->statusHistories->first()->to_status);
    }
}
