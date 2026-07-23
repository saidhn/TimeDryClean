<?php

namespace App\Services;

use App\Models\ClientSubscription;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KnetService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('services.knet');
    }

    /**
     * Sign a callback payload so our own test gateway (and, later, real KNET
     * verification per their SDK docs) can be authenticated by handleCallback().
     */
    public function signCallback(string $trackingId, string $result): string
    {
        return hash_hmac('sha256', "{$trackingId}|{$result}", (string) config('services.knet.secret'));
    }

    /**
     * Create a wallet top-up payment and get redirect URL.
     */
    public function createPayment(float $amount, int $userId): array
    {
        $trackingId = 'TRK-' . time() . '-' . Str::random(8);

        $payment = Payment::create([
            'user_id'        => $userId,
            'amount'         => $amount,
            'payment_method' => 'KNET',
            'transaction_id' => $trackingId,
            'status'         => 'pending',
            'details'        => json_encode(['tracking_id' => $trackingId]),
        ]);

        $paymentUri = $this->config['debug']
            ? route('client.payment.test-gateway', ['tracking_id' => $trackingId])
            : $this->getKnetPaymentUrl($amount, $trackingId);

        return [
            'status'      => 'success',
            'tracking_id' => $trackingId,
            'payment_uri' => $paymentUri,
            'payment_id'  => $payment->id,
        ];
    }

    /**
     * Create an initial subscription payment (client subscribing for the first time).
     * The ClientSubscription record is created in 'pending_payment' status and is only
     * activated once the KNET callback confirms a successful payment.
     */
    public function createSubscriptionPayment(
        float $amount,
        int $userId,
        int $subscriptionId
    ): array {
        $trackingId = 'TRK-SUB-' . time() . '-' . Str::random(8);

        DB::transaction(function () use (
            $amount, $userId, $subscriptionId, $trackingId, &$result
        ) {
            $subscription = Subscription::findOrFail($subscriptionId);

            // Create the subscription record in pending state — it will be activated
            // in handleCallback() once KNET confirms the payment.
            $clientSubscription = ClientSubscription::create([
                'user_id'         => $userId,
                'subscription_id' => $subscriptionId,
                'activated_at'    => null,         // set on activation
                'next_billing_at' => null,         // set on activation
                'status'          => ClientSubscription::STATUS_PENDING_PAYMENT,
            ]);

            $payment = Payment::create([
                'user_id'        => $userId,
                'amount'         => $amount,
                'payment_method' => 'KNET',
                'transaction_id' => $trackingId,
                'status'         => 'pending',
                'details'        => json_encode([
                    'tracking_id'            => $trackingId,
                    'type'                   => 'subscription',
                    'subscription_id'        => $subscriptionId,
                    'client_subscription_id' => $clientSubscription->id,
                ]),
            ]);

            // Link the pending payment to the subscription record so we can prevent
            // duplicate payment links and locate the subscription in the callback.
            $clientSubscription->update(['pending_payment_id' => $payment->id]);

            $paymentUri = $this->config['debug']
                ? route('client.payment.test-gateway', ['tracking_id' => $trackingId])
                : $this->getKnetPaymentUrl($amount, $trackingId);

            $result = [
                'status'                  => 'success',
                'tracking_id'             => $trackingId,
                'payment_uri'             => $paymentUri,
                'payment_id'              => $payment->id,
                'client_subscription_id'  => $clientSubscription->id,
            ];
        });

        return $result;
    }

    /**
     * Create a renewal payment for a subscription that failed its wallet charge.
     * The KNET payment link is sent to the client via WhatsApp so they can pay
     * manually. The next_billing_at is NOT advanced until payment succeeds.
     */
    public function createSubscriptionRenewalPayment(
        float $amount,
        int $userId,
        int $clientSubscriptionId
    ): array {
        $trackingId = 'TRK-REN-' . time() . '-' . Str::random(8);

        $payment = Payment::create([
            'user_id'        => $userId,
            'amount'         => $amount,
            'payment_method' => 'KNET',
            'transaction_id' => $trackingId,
            'status'         => 'pending',
            'details'        => json_encode([
                'tracking_id'            => $trackingId,
                'type'                   => 'subscription_renewal',
                'client_subscription_id' => $clientSubscriptionId,
            ]),
        ]);

        $paymentUri = $this->config['debug']
            ? route('client.payment.test-gateway', ['tracking_id' => $trackingId])
            : $this->getKnetPaymentUrl($amount, $trackingId);

        return [
            'status'      => 'success',
            'tracking_id' => $trackingId,
            'payment_uri' => $paymentUri,
            'payment_id'  => $payment->id,
        ];
    }

    /**
     * Get KNET payment URL (production mode).
     * TODO: Integrate with KNET PHP SDK when available.
     */
    protected function getKnetPaymentUrl(float $amount, string $trackingId): string
    {
        // When KNET SDK is available:
        // $knet = new KNET($this->config);
        // $knet->init();
        // $result = $knet->create(['AMOUNT' => number_format($amount, 3)]);
        // return $result['PAYMENT_URI'];

        return route('client.payment.test-gateway', ['tracking_id' => $trackingId]);
    }

    /**
     * Handle KNET callback (listen URI).
     * Dispatches post-payment side-effects based on the payment `type` stored
     * in the details JSON.
     */
    public function handleCallback(array $data): array
    {
        $trackingId = $data['tracking_id'] ?? null;
        $result     = $data['result'] ?? 'FAILED';

        if (!$trackingId) {
            return ['status' => 'error', 'description' => 'Invalid tracking ID'];
        }

        $payment = Payment::where('transaction_id', $trackingId)->first();

        if (!$payment) {
            return ['status' => 'error', 'description' => 'Payment not found'];
        }

        if ($payment->status !== 'pending') {
            // Idempotency: a retried/duplicated webhook must not re-apply side effects.
            return ['status' => 'error', 'description' => 'Payment already processed'];
        }

        $paymentStatus = ($result === 'CAPTURED' || $result === 'successful') ? 'completed' : 'failed';

        $payment->update([
            'status'       => $paymentStatus,
            'payment_date' => now(),
            'details'      => json_encode(array_merge(
                json_decode($payment->details, true) ?? [],
                $data
            )),
        ]);

        if ($paymentStatus === 'completed') {
            $details = json_decode($payment->details, true) ?? [];
            $type    = $details['type'] ?? null;

            if ($type === 'subscription') {
                $this->handleSubscriptionPaymentSuccess($payment, $details);
            } elseif ($type === 'subscription_renewal') {
                $this->handleSubscriptionRenewalPaymentSuccess($payment, $details);
            } elseif ($type === 'points_package' && isset($details['purchase_id'])) {
                $purchase = \App\Models\UserPointsPackage::find($details['purchase_id']);
                if ($purchase) {
                    app(\App\Http\Controllers\Points\ClientPointsController::class)->completePurchase($purchase);
                }
            } elseif ($type === 'order') {
                if (!empty($details['order_id'])) {
                    $order = Order::find($details['order_id']);
                    if ($order) {
                        $updateData = ['is_paid' => true];
                        if ($order->requires_additional_payment && $order->repriced_amount !== null) {
                            $updateData['sum_price']                   = $order->repriced_amount;
                            $updateData['requires_additional_payment'] = false;
                            $updateData['repriced_amount']             = null;
                        }
                        $order->update($updateData);
                    }
                }
            } else {
                // Generic wallet top-up
                User::adjustBalance($payment->user_id, $payment->amount);
            }
        } else {
            // Payment failed — if this was a pending subscription, clean it up
            $details = json_decode($payment->details, true) ?? [];
            $type    = $details['type'] ?? null;
            if ($type === 'subscription' && !empty($details['client_subscription_id'])) {
                $clientSubscription = ClientSubscription::find($details['client_subscription_id']);
                if ($clientSubscription && $clientSubscription->isPendingPayment()) {
                    // Clear the pending payment link; client can try again
                    $clientSubscription->update(['pending_payment_id' => null]);
                }
            } elseif ($type === 'subscription_renewal' && !empty($details['client_subscription_id'])) {
                $clientSubscription = ClientSubscription::find($details['client_subscription_id']);
                if ($clientSubscription && $clientSubscription->pending_payment_id === $payment->id) {
                    // Clear the pending renewal payment so the cron can retry
                    $clientSubscription->update(['pending_payment_id' => null]);
                }
            }
        }

        return [
            'status'          => 'success',
            'payment_status'  => $paymentStatus,
            'tracking_id'     => $trackingId,
            'redirect_url'    => route('client.payment.complete', ['tracking_id' => $trackingId]),
        ];
    }

    /**
     * Activate a subscription after a successful initial KNET payment.
     */
    protected function handleSubscriptionPaymentSuccess(Payment $payment, array $details): void
    {
        $clientSubscriptionId = $details['client_subscription_id'] ?? null;
        if (!$clientSubscriptionId) {
            Log::error('subscription KNET callback: missing client_subscription_id', $details);
            return;
        }

        DB::transaction(function () use ($payment, $clientSubscriptionId) {
            $clientSubscription = ClientSubscription::lockForUpdate()->find($clientSubscriptionId);
            if (!$clientSubscription || !$clientSubscription->isPendingPayment()) {
                return; // Already activated or removed
            }

            $subscription = $clientSubscription->subscription;
            $activatedAt  = now();

            $clientSubscription->update([
                'status'             => ClientSubscription::STATUS_ACTIVE,
                'activated_at'       => $activatedAt,
                'next_billing_at'    => $subscription->getPeriodEndFrom($activatedAt),
                'pending_payment_id' => null,
            ]);

            // Credit the subscription benefit to the user's balance.
            $user = User::find($payment->user_id);
            if ($user) {
                $user->increment('balance', $subscription->benefit);
                $user->refresh();

                app(NotificationService::class)->sendTransactionNotification(
                    $user,
                    'subscription_balance_added',
                    ['balance' => $user->balance]
                );
            }
        });
    }

    /**
     * Complete a renewal after successful KNET payment (fallback path when wallet was empty).
     */
    protected function handleSubscriptionRenewalPaymentSuccess(Payment $payment, array $details): void
    {
        $clientSubscriptionId = $details['client_subscription_id'] ?? null;
        if (!$clientSubscriptionId) {
            Log::error('subscription_renewal KNET callback: missing client_subscription_id', $details);
            return;
        }

        DB::transaction(function () use ($payment, $clientSubscriptionId) {
            $clientSubscription = ClientSubscription::lockForUpdate()->find($clientSubscriptionId);
            if (!$clientSubscription) {
                return;
            }

            $subscription = $clientSubscription->subscription;

            // Mark success and advance the billing window.
            $clientSubscription->consecutive_failures = 0;
            $clientSubscription->last_payment_status  = 'success';
            $clientSubscription->last_billed_at       = now();
            $clientSubscription->next_billing_at      = $subscription->getPeriodEndFrom(now());
            $clientSubscription->pending_payment_id   = null;
            $clientSubscription->save();

            // Credit benefit.
            $user = User::find($payment->user_id);
            if ($user) {
                $user->increment('balance', $subscription->benefit);
                $user->refresh();

                app(NotificationService::class)->sendTransactionNotification(
                    $user,
                    'subscription_renewal_success',
                    ['balance' => $user->balance, 'amount' => $payment->amount]
                );
            }
        });
    }

    /**
     * Create a payment for an order and get redirect URL.
     * Set $isPublicLink = true when generating a shareable customer payment link.
     */
    public function createOrderPayment(float $amount, int $userId, int $orderId, bool $isPublicLink = false): array
    {
        $trackingId = 'TRK-ORD-' . time() . '-' . Str::random(8);

        $payment = Payment::create([
            'user_id'        => $userId,
            'amount'         => $amount,
            'payment_method' => 'KNET',
            'transaction_id' => $trackingId,
            'status'         => 'pending',
            'details'        => json_encode([
                'tracking_id' => $trackingId,
                'type'        => 'order',
                'order_id'    => $orderId,
                'public_link' => $isPublicLink,
            ]),
        ]);

        if ($this->config['debug']) {
            $gatewayRoute = $isPublicLink ? 'client.payment.public-test-gateway' : 'client.payment.test-gateway';
            $paymentUri   = route($gatewayRoute, ['tracking_id' => $trackingId]);
        } else {
            $paymentUri = $this->getKnetPaymentUrl($amount, $trackingId);
        }

        return [
            'status'      => 'success',
            'tracking_id' => $trackingId,
            'payment_uri' => $paymentUri,
            'payment_id'  => $payment->id,
        ];
    }

    /**
     * Create a points package payment and get redirect URL.
     */
    public function createPointsPackagePayment(float $amount, int $userId, int $purchaseId): array
    {
        $trackingId = 'TRK-PTS-' . time() . '-' . Str::random(8);

        $payment = Payment::create([
            'user_id'        => $userId,
            'amount'         => $amount,
            'payment_method' => 'KNET',
            'transaction_id' => $trackingId,
            'status'         => 'pending',
            'details'        => json_encode([
                'tracking_id' => $trackingId,
                'type'        => 'points_package',
                'purchase_id' => $purchaseId,
            ]),
        ]);

        $paymentUri = $this->config['debug']
            ? route('client.payment.test-gateway', ['tracking_id' => $trackingId])
            : $this->getKnetPaymentUrl($amount, $trackingId);

        return [
            'status'      => 'success',
            'tracking_id' => $trackingId,
            'payment_uri' => $paymentUri,
            'payment_id'  => $payment->id,
        ];
    }

    /**
     * Get payment by tracking ID.
     */
    public function getPaymentByTrackingId(string $trackingId): ?Payment
    {
        return Payment::where('transaction_id', $trackingId)->first();
    }
}
