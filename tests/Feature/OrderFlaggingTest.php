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

        $order = Order::create(['user_id' => $client->id, 'sum_price' => 10, 'status' => OrderStatus::PLACED]);

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
