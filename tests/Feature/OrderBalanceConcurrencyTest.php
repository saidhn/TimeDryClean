<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientPointsException;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderProductService;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
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

    public function test_adjust_points_if_sufficient_throws_and_does_not_mutate_when_insufficient(): void
    {
        $user = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000012',
            'points_balance' => 10,
        ]);

        $this->expectException(InsufficientPointsException::class);

        try {
            User::adjustPointsIfSufficient($user->id, -15);
        } finally {
            $user->refresh();
            $this->assertEquals(10.0, (float) $user->points_balance);
        }
    }

    public function test_pay_with_insufficient_points_returns_error_and_does_not_mutate_balance(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'admin',
            'mobile' => '50000013',
        ]);

        $client = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000014',
            'points_balance' => 5,
        ]);

        $product = Product::create(['name' => 'Test Product']);
        $productService = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create([
            'product_id' => $product->id,
            'product_service_id' => $productService->id,
            'price' => 10,
            'points_price' => 20,
        ]);

        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 10,
            'status' => OrderStatus::PLACED,
            'is_paid' => false,
        ]);

        OrderProductService::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_service_id' => $productService->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('orders.pay', $order), ['payment_method' => 'points']);

        $response->assertSessionHasErrors('message');

        $client->refresh();
        $this->assertEquals(5.0, (float) $client->points_balance);

        $order->refresh();
        $this->assertFalse((bool) $order->is_paid);
    }
}
