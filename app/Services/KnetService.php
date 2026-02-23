<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Str;

class KnetService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('services.knet');
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

        $paymentStatus = ($result === 'CAPTURED' || $result === 'successful') ? 'completed' : 'failed';

        $payment->update([
            'status' => $paymentStatus,
            'payment_date' => now(),
            'details' => json_encode(array_merge(
                json_decode($payment->details, true) ?? [],
                $data
            )),
        ]);

        // If payment successful, add to user balance
        if ($paymentStatus === 'completed') {
            $payment->user->increment('balance', $payment->amount);
        }

        return [
            'status' => 'success',
            'payment_status' => $paymentStatus,
            'tracking_id' => $trackingId,
            'redirect_url' => route('client.payment.complete', ['tracking_id' => $trackingId]),
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
