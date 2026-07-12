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
