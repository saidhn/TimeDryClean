<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\KnetService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        protected KnetService $knetService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Show payment form
     */
    public function create()
    {
        $client = Auth::user();
        $amountDue = abs($client->balance < 0 ? $client->balance : 0);

        return view('client.payment.create', compact('client', 'amountDue'));
    }

    /**
     * Initiate payment and redirect to KNET
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.001',
        ]);

        $client = Auth::user();
        $amount = (float) $request->amount;

        // Create payment and get redirect URL
        $result = $this->knetService->createPayment($amount, $client->id);

        if ($result['status'] === 'success') {
            // Redirect to KNET gateway
            return redirect($result['payment_uri']);
        }

        return back()->withErrors(['error' => 'Failed to initiate payment. Please try again.']);
    }

    /**
     * Test gateway (simulates KNET for testing)
     */
    public function testGateway(Request $request)
    {
        $trackingId = $request->query('tracking_id');
        $payment = $this->knetService->getPaymentByTrackingId($trackingId);

        if (!$payment) {
            abort(404, 'Payment not found');
        }

        return view('client.payment.test-gateway', compact('payment', 'trackingId'));
    }

    /**
     * Handle KNET callback (listen URI)
     */
    public function callback(Request $request)
    {
        $data = $request->all();
        $result = $this->knetService->handleCallback($data);

        if (($result['status'] ?? '') === 'success' && ($result['payment_status'] ?? '') === 'completed') {
            $payment = $this->knetService->getPaymentByTrackingId($result['tracking_id']);
            if ($payment) {
                $payment->user->refresh();
                $this->notificationService->sendTransactionNotification(
                    $payment->user,
                    'payment_completed',
                    ['amount' => $payment->amount, 'balance' => $payment->user->balance]
                );
            }
        }

        $trackingId = $result['tracking_id'] ?? ($data['tracking_id'] ?? '');

        // In test/debug mode, redirect directly
        if (config('services.knet.debug')) {
            return redirect()->route('client.payment.complete', ['tracking_id' => $trackingId]);
        }

        // In production, KNET expects "REDIRECT=<url>" as the response body
        return 'REDIRECT=' . route('client.payment.complete', ['tracking_id' => $trackingId]);
    }

    /**
     * Payment complete page (success/failure)
     */
    public function complete(Request $request)
    {
        $trackingId = $request->query('tracking_id');
        $payment = $this->knetService->getPaymentByTrackingId($trackingId);

        if (!$payment) {
            return redirect()->route('client.bills.index')->with('error', 'Payment not found.');
        }

        return view('client.payment.complete', compact('payment'));
    }
}
