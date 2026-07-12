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
