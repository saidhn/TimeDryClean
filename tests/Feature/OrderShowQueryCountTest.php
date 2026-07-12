<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderShowQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private array $sqlLog = [];

    protected function setUp(): void
    {
        parent::setUp();
        DB::listen(function ($query) {
            $this->sqlLog[] = $query->sql;
        });
    }

    private function countProductServicePriceQueries(): int
    {
        return count(array_filter($this->sqlLog, fn ($sql) => str_contains($sql, 'product_service_prices')));
    }

    public function test_show_does_not_issue_one_product_service_price_query_per_line(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000130', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000131', 'balance' => 0]);
        $order = Order::create(['user_id' => $client->id, 'sum_price' => 20, 'status' => OrderStatus::PLACED, 'is_paid' => false]);

        foreach (range(1, 5) as $i) {
            $product = Product::create(['name' => "Item {$i}"]);
            $service = ProductService::create(['name' => "Service {$i}"]);
            ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5, 'points_price' => 10]);
            $order->orderProductServices()->create([
                'product_id' => $product->id, 'product_service_id' => $service->id,
                'quantity' => 1, 'price_at_order' => 5,
            ]);
        }

        // Reset the log so only queries fired by the request itself are counted.
        $this->sqlLog = [];

        $this->actingAs($admin, 'admin')->get(route('orders.show', $order));

        // Before the fix: 5 lines => 5 separate ProductServicePrice queries (one per
        // line). After the fix: exactly 1 batched query for all ProductServicePrice
        // rows regardless of line count.
        $this->assertLessThanOrEqual(1, $this->countProductServicePriceQueries());
    }
}
