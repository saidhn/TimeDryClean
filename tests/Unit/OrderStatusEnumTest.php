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
