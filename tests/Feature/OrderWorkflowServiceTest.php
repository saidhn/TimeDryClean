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
