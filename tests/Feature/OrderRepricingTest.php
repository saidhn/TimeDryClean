<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SendTransactionNotificationJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use App\Services\KnetService;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

    public function test_reweighing_a_points_paid_order_to_a_lower_total_refunds_points_not_money(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000114', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000115', 'balance' => 0, 'points_balance' => 0]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        // Deliberately different price vs points_price so confusing the two currencies is caught.
        ProductServicePrice::create([
            'product_id' => $product->id, 'product_service_id' => $service->id,
            'price' => 5, 'points_price' => 50,
        ]);

        $order = Order::create([
            'user_id' => $client->id, 'sum_price' => 20, 'status' => OrderStatus::PLACED,
            'is_paid' => true, 'payment_method' => 'points', 'points_used' => 200,
        ]);
        $order->orderProductServices()->create([
            'product_id' => $product->id, 'product_service_id' => $service->id,
            'quantity' => 4, 'price_at_order' => 5, 'points_at_order' => 50,
        ]);
        app(OrderWorkflowService::class)->transition($order, OrderStatus::PICKUP_SCHEDULED, 'admin', $client->id);
        app(OrderWorkflowService::class)->transition($order, OrderStatus::AT_FACILITY, 'admin', $client->id);

        $this->actingAs($admin, 'admin')->put(route('orders.reprice', $order), [
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 2],
            ],
        ]);

        $order->refresh();
        $client->refresh();

        // New total: 2 * points_price(50) = 100 points. Original points_used: 200.
        // Correct points refund = 200 - 100 = 100 points.
        $this->assertEquals(100.0, (float) $client->points_balance, 'points refund must be points-denominated (100), not the money delta');

        // Money delta would have been: original sum_price(20) - new money total (2 * price(5) = 10) = 10.
        // If the bug were still present, points_balance would have been bumped by 10, not 100.
        $this->assertNotEquals(10.0, (float) $client->points_balance);

        $this->assertEquals(0.0, (float) $client->balance, 'a points-paid order must never touch the money balance');

        $this->assertEquals(100, (int) $order->points_used);

        $lines = $order->orderProductServices()->get();
        $this->assertCount(1, $lines);
        $this->assertNotNull($lines->first()->points_at_order);
        $this->assertEquals(50.0, (float) $lines->first()->points_at_order);
    }

    public function test_knet_callback_for_reprice_topup_finalizes_the_order(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000116', 'balance' => 0]);

        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 20,
            'status' => OrderStatus::AT_FACILITY,
            'is_paid' => true,
            'payment_method' => 'money',
            'requires_additional_payment' => true,
            'repriced_amount' => 40,
        ]);

        $trackingId = 'TRK-ORD-reprice-topup-1';
        Payment::create([
            'user_id' => $client->id,
            'amount' => 20, // the delta being collected
            'payment_method' => 'KNET',
            'transaction_id' => $trackingId,
            'status' => 'pending',
            'details' => json_encode([
                'tracking_id' => $trackingId,
                'type' => 'order',
                'order_id' => $order->id,
                'public_link' => false,
            ]),
        ]);

        $knetService = app(KnetService::class);
        $result = $knetService->handleCallback([
            'tracking_id' => $trackingId,
            'result' => 'CAPTURED',
        ]);

        $this->assertSame('success', $result['status']);

        $order->refresh();

        $this->assertTrue((bool) $order->is_paid);
        $this->assertEquals(40.0, (float) $order->sum_price);
        $this->assertFalse((bool) $order->requires_additional_payment);
        $this->assertNull($order->repriced_amount);
    }

    public function test_reweighing_to_a_lower_total_queues_a_refund_notification(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000117', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000118', 'balance' => 0]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $order = $this->orderAtFacility($client, $product, $service);

        $this->actingAs($admin, 'admin')->put(route('orders.reprice', $order), [
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 2],
            ],
        ]);

        Queue::assertPushed(SendTransactionNotificationJob::class, function ($job) use ($client) {
            return $job->userId === $client->id;
        });
    }
}
