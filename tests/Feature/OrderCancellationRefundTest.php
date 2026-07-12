<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SendTransactionNotificationJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

    public function test_editing_price_and_cancelling_in_the_same_request_refunds_exactly_the_original_amount(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000104', 'balance' => 0]);
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000105', 'balance' => 0]);

        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        $priceRow = ProductServicePrice::create([
            'product_id' => $product->id,
            'product_service_id' => $service->id,
            'price' => 25,
        ]);

        // Original paid order: sum_price 40, fully paid via money.
        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 40,
            'status' => OrderStatus::PLACED,
            'is_paid' => true,
            'payment_method' => 'money',
        ]);

        // Submit an edit that BOTH changes the price/line-items (new sum would be
        // 2 x 25 = 50, different from the original 40) AND cancels the order in the
        // same request — the combined scenario that used to risk a double (or zero)
        // refund.
        $response = $this->actingAs($admin, 'admin')->put(route('orders.update', $order->id), [
            'user_id' => $client->id,
            'order_status' => OrderStatus::CANCELLED,
            'payment_method' => 'money',
            'order_product_services' => [
                [
                    'product_id' => $product->id,
                    'product_service_id' => $service->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $client->refresh();
        $order->refresh();

        // Exactly one refund of the ORIGINAL pre-edit amount (40) — not zero, not the
        // new post-edit price (50), and not double-refunded (80).
        $this->assertEquals(40.0, (float) $client->balance);
        $this->assertSame(OrderStatus::CANCELLED, $order->status);
        $this->assertNotNull($order->refunded_at);
    }

    public function test_resaving_an_already_cancelled_and_refunded_order_does_not_leak_a_second_refund(): void
    {
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000106', 'balance' => 40]);
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000107', 'balance' => 0]);

        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create([
            'product_id' => $product->id,
            'product_service_id' => $service->id,
            'price' => 40,
        ]);

        // Order is ALREADY cancelled and ALREADY refunded (e.g. from a prior
        // cancellation request) — simulating the state right after the first,
        // legitimate cancellation refund has already been applied.
        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 40,
            'status' => OrderStatus::CANCELLED,
            'is_paid' => true,
            'payment_method' => 'money',
            'refunded_at' => now(),
        ]);

        // Admin opens the edit form (which pre-selects the current status) and
        // saves without actually changing the status — order_status=CANCELLED is
        // resubmitted even though the order was already CANCELLED.
        $response = $this->actingAs($admin, 'admin')->put(route('orders.update', $order->id), [
            'user_id' => $client->id,
            'order_status' => OrderStatus::CANCELLED,
            'payment_method' => 'money',
            'order_product_services' => [
                [
                    'product_id' => $product->id,
                    'product_service_id' => $service->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $client->refresh();
        $order->refresh();

        // No actual status transition occurred, so no refund should be applied —
        // the customer's balance must be unchanged from before this request.
        $this->assertEquals(40.0, (float) $client->balance);
        $this->assertSame(OrderStatus::CANCELLED, $order->status);
    }

    public function test_cancelling_a_paid_order_queues_a_refund_notification(): void
    {
        Queue::fake();

        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000108', 'balance' => 0]);
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000109', 'balance' => 0]);

        $order = Order::create([
            'user_id' => $client->id,
            'sum_price' => 40,
            'status' => OrderStatus::PLACED,
            'is_paid' => true,
            'payment_method' => 'money',
        ]);

        app(OrderWorkflowService::class)->transition($order, OrderStatus::CANCELLED, 'admin', $admin->id, 'Customer requested cancellation');

        Queue::assertPushed(SendTransactionNotificationJob::class, function ($job) use ($client) {
            return $job->userId === $client->id && (float) $job->replace['balance'] === 40.0;
        });
    }
}
