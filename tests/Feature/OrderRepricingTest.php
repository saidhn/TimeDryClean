<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepricingTest extends TestCase
{
    use RefreshDatabase;

    private function orderAtFacility(User $client, Product $product, ProductService $service): Order
    {
        $order = Order::create([
            'user_id' => $client->id, 'sum_price' => 20, 'status' => OrderStatus::PLACED,
            'is_paid' => true, 'payment_method' => 'money',
        ]);
        $order->orderProductServices()->create([
            'product_id' => $product->id, 'product_service_id' => $service->id,
            'quantity' => 4, 'price_at_order' => 5,
        ]);
        app(OrderWorkflowService::class)->transition($order, OrderStatus::PICKUP_SCHEDULED, 'admin', $client->id);
        app(OrderWorkflowService::class)->transition($order, OrderStatus::AT_FACILITY, 'admin', $client->id);
        return $order;
    }

    public function test_reweighing_to_a_lower_total_refunds_the_difference_immediately(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000110', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000111', 'balance' => 0]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $order = $this->orderAtFacility($client, $product, $service);

        $this->actingAs($admin, 'admin')->put(route('orders.reprice', $order), [
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 2],
            ],
        ]);

        $order->refresh();
        $client->refresh();

        $this->assertEquals(10.0, (float) $order->sum_price);
        $this->assertEquals(10.0, (float) $client->balance); // refunded the 10 difference
        $this->assertFalse($order->requires_additional_payment);
    }

    public function test_reweighing_to_a_higher_total_requires_additional_payment_and_does_not_auto_charge(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000112', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000113', 'balance' => 0]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $order = $this->orderAtFacility($client, $product, $service);

        $this->actingAs($admin, 'admin')->put(route('orders.reprice', $order), [
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 8],
            ],
        ]);

        $order->refresh();
        $client->refresh();

        $this->assertEquals(20.0, (float) $order->sum_price); // unchanged — not silently charged
        $this->assertEquals(0.0, (float) $client->balance); // not debited automatically
        $this->assertTrue($order->requires_additional_payment);
        $this->assertEquals(40.0, (float) $order->repriced_amount);
    }
}
