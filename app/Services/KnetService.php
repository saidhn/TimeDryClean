<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
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
     * Create a payment and get redirect URL
     */
    public function createPayment(float $amount, int $userId): array
    {
        $trackingId = 'TRK-' . time() . '-' . Str::random(8);

        // Create pending payment record
        $payment = Payment::create([
            'user_id' => $userId,
            'amount' => $amount,
            'payment_method' => 'KNET',
            'transaction_id' => $trackingId,
            'status' => 'pending',
            'details' => json_encode(['tracking_id' => $trackingId]),
        ]);

        // In debug/test mode, use local test gateway
        if ($this->config['debug']) {
            $paymentUri = route('client.payment.test-gateway', ['tracking_id' => $trackingId]);
        } else {
            // In production, integrate with actual KNET SDK
            // This requires the KNET PHP SDK and resource files
            $paymentUri = $this->getKnetPaymentUrl($amount, $trackingId);
        }

        return [
            'status' => 'success',
            'tracking_id' => $trackingId,
            'payment_uri' => $paymentUri,
            'payment_id' => $payment->id,
        ];
    }

    /**
     * Get KNET payment URL (production mode)
     * This requires KNET PHP SDK integration
     */
    protected function getKnetPaymentUrl(float $amount, string $trackingId): string
    {
        // TODO: Integrate with KNET PHP SDK
        // For now, return test URL
        // When KNET SDK is available:
        // $knet = new KNET($this->config);
        // $knet->init();
        // $result = $knet->create(['AMOUNT' => number_format($amount, 3)]);
        // return $result['PAYMENT_URI'];
        
        return route('client.payment.test-gateway', ['tracking_id' => $trackingId]);
    }

    /**
     * Handle KNET callback (listen URI)
     */
    public function handleCallback(array $data): array
    {
        $trackingId = $data['tracking_id'] ?? null;
        $result = $data['result'] ?? 'FAILED';

        if (!$trackingId) {
            return [
                'status' => 'error',
                'description' => 'Invalid tracking ID',
            ];
        }

        $payment = Payment::where('transaction_id', $trackingId)->first();

        if (!$payment) {
            return [
                'status' => 'error',
                'description' => 'Payment not found',
            ];
        }

        if ($payment->status !== 'pending') {
            // Idempotency: a retried/duplicated webhook must not re-apply side effects.
            return [
                'status' => 'error',
                'description' => 'Payment already processed',
            ];
        }

        $paymentStatus = ($result === 'CAPTURED' || $result === 'successful') ? 'completed' : 'failed';

        $payment->update([
            'status' => $paymentStatus,
            'payment_date' => now(),
            'details' => json_encode(array_merge(
                json_decode($payment->details, true) ?? [],
                $data
            )),
        ]);

        // If payment successful, credit the appropriate balance
        if ($paymentStatus === 'completed') {
            $details = json_decode($payment->details, true) ?? [];
            $type = $details['type'] ?? null;

            if ($type === 'points_package' && isset($details['purchase_id'])) {
                $purchase = \App\Models\UserPointsPackage::find($details['purchase_id']);
                if ($purchase) {
                    app(\App\Http\Controllers\Points\ClientPointsController::class)->completePurchase($purchase);
                }
            } elseif ($type === 'order') {
                // Mark the order as Completed now that payment is confirmed.
                if (!empty($details['order_id'])) {
                    Order::where('id', $details['order_id'])
                        ->update(['status' => \App\Enums\OrderStatus::COMPLETED, 'is_paid' => true]);
                }
            } else {
                User::adjustBalance($payment->user_id, $payment->amount);
            }
        }

        return [
            'status' => 'success',
            'payment_status' => $paymentStatus,
            'tracking_id' => $trackingId,
            'redirect_url' => route('client.payment.complete', ['tracking_id' => $trackingId]),
        ];
    }

    /**
     * Create a payment for an order and get redirect URL.
     * Set $isPublicLink = true when generating a shareable customer payment link
     * so the callback redirects to a page that does not require authentication.
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
            // Public link payments use the no-auth test gateway so unauthenticated customers can access it
            $gatewayRoute = $isPublicLink ? 'client.payment.public-test-gateway' : 'client.payment.test-gateway';
            $paymentUri = route($gatewayRoute, ['tracking_id' => $trackingId]);
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
     * Create a points package payment and get redirect URL
     */
    public function createPointsPackagePayment(float $amount, int $userId, int $purchaseId): array
    {
        $trackingId = 'TRK-PTS-' . time() . '-' . Str::random(8);

        $payment = Payment::create([
            'user_id' => $userId,
            'amount' => $amount,
            'payment_method' => 'KNET',
            'transaction_id' => $trackingId,
            'status' => 'pending',
            'details' => json_encode([
                'tracking_id' => $trackingId,
                'type' => 'points_package',
                'purchase_id' => $purchaseId,
            ]),
        ]);

        if ($this->config['debug']) {
            $paymentUri = route('client.payment.test-gateway', ['tracking_id' => $trackingId]);
        } else {
            $paymentUri = $this->getKnetPaymentUrl($amount, $trackingId);
        }

        return [
            'status' => 'success',
            'tracking_id' => $trackingId,
            'payment_uri' => $paymentUri,
            'payment_id' => $payment->id,
        ];
    }

    /**
     * Get payment by tracking ID
     */
    public function getPaymentByTrackingId(string $trackingId): ?Payment
    {
        return Payment::where('transaction_id', $trackingId)->first();
    }
}
