<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnetWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.knet.secret' => 'test-secret-key']);
    }

    public function test_callback_without_valid_signature_is_rejected(): void
    {
        $client = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000001',
            'balance' => 0,
        ]);

        $payment = Payment::create([
            'user_id' => $client->id,
            'amount' => 25.000,
            'payment_method' => 'KNET',
            'transaction_id' => 'TRK-ORD-forged-1',
            'status' => 'pending',
            'details' => json_encode(['tracking_id' => 'TRK-ORD-forged-1', 'type' => 'balance']),
        ]);

        $response = $this->post('/payment/callback', [
            'tracking_id' => 'TRK-ORD-forged-1',
            'result' => 'CAPTURED',
            // no signature at all
        ]);

        $client->refresh();
        $payment->refresh();

        $this->assertSame(0.0, (float) $client->balance);
        $this->assertSame('pending', $payment->status);
    }

    public function test_callback_with_valid_signature_completes_payment_once(): void
    {
        $client = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000002',
            'balance' => 0,
        ]);

        $payment = Payment::create([
            'user_id' => $client->id,
            'amount' => 25.000,
            'payment_method' => 'KNET',
            'transaction_id' => 'TRK-ORD-valid-1',
            'status' => 'pending',
            'details' => json_encode(['tracking_id' => 'TRK-ORD-valid-1', 'type' => 'balance']),
        ]);

        $signature = hash_hmac('sha256', 'TRK-ORD-valid-1|CAPTURED', 'test-secret-key');

        $this->post('/payment/callback', [
            'tracking_id' => 'TRK-ORD-valid-1',
            'result' => 'CAPTURED',
            'signature' => $signature,
        ]);

        $client->refresh();
        $payment->refresh();

        $this->assertEquals(25.0, (float) $client->balance);
        $this->assertSame('completed', $payment->status);
    }

    public function test_duplicate_valid_callback_does_not_double_credit(): void
    {
        $client = User::factory()->create([
            'user_type' => 'client',
            'mobile' => '50000003',
            'balance' => 0,
        ]);

        Payment::create([
            'user_id' => $client->id,
            'amount' => 25.000,
            'payment_method' => 'KNET',
            'transaction_id' => 'TRK-ORD-dup-1',
            'status' => 'pending',
            'details' => json_encode(['tracking_id' => 'TRK-ORD-dup-1', 'type' => 'balance']),
        ]);

        $signature = hash_hmac('sha256', 'TRK-ORD-dup-1|CAPTURED', 'test-secret-key');
        $payload = ['tracking_id' => 'TRK-ORD-dup-1', 'result' => 'CAPTURED', 'signature' => $signature];

        $this->post('/payment/callback', $payload);
        $this->post('/payment/callback', $payload); // retry, same as a real gateway resend

        $client->refresh();

        $this->assertEquals(25.0, (float) $client->balance); // not 50
    }
}
