<?php

namespace App\Console\Commands;

use App\Models\ClientSubscription;
use App\Models\Payment;
use App\Services\KnetService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge each due client subscription for its renewal period. '
        . 'Attempts wallet payment first; on failure sends a KNET payment link to the client. '
        . 'Suspends the subscription after ' . ClientSubscription::MAX_CONSECUTIVE_FAILURES . ' consecutive failures.';

    public function __construct(
        protected NotificationService $notificationService,
        protected KnetService $knetService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $due = ClientSubscription::dueForBilling()
            ->with(['user', 'subscription'])
            ->get();

        $this->info("Found {$due->count()} subscription(s) due for renewal.");

        foreach ($due as $clientSubscription) {
            try {
                $this->renew($clientSubscription);
            } catch (\Throwable $e) {
                Log::error('Subscription renewal failed unexpectedly', [
                    'client_subscription_id' => $clientSubscription->id,
                    'error'                  => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    protected function renew(ClientSubscription $clientSubscription): void
    {
        $user         = $clientSubscription->user;
        $subscription = $clientSubscription->subscription;

        if (!$user || !$subscription) {
            Log::warning('Skipping subscription renewal with missing user or plan', [
                'client_subscription_id' => $clientSubscription->id,
            ]);
            return;
        }

        DB::transaction(function () use ($clientSubscription, $user, $subscription) {
            $amountDue = (float) $subscription->paid;
            // Lock the user row to prevent concurrent balance modifications.
            $user      = $user->lockForUpdate()->find($user->id) ?? $user;
            $succeeded = $amountDue <= 0 || (float) $user->balance >= $amountDue;

            // Record the wallet-level attempt.
            Payment::create([
                'user_id'        => $user->id,
                'amount'         => $amountDue,
                'payment_method' => 'Wallet',
                'status'         => $succeeded ? 'completed' : 'failed',
                'payment_date'   => now(),
                'details'        => json_encode([
                    'type'                   => 'subscription_renewal',
                    'client_subscription_id' => $clientSubscription->id,
                ]),
            ]);

            if ($succeeded) {
                // ── Success: deduct cost, credit benefit, reset failure counter ──
                if ($amountDue > 0) {
                    $user->decrement('balance', $amountDue);
                }
                $user->increment('balance', $subscription->benefit);
                $user->refresh();

                $clientSubscription->consecutive_failures = 0;
                $clientSubscription->last_payment_status  = 'success';
                $clientSubscription->last_billed_at       = now();
                $clientSubscription->next_billing_at      = $subscription->getPeriodEndFrom(now());
                $clientSubscription->pending_payment_id   = null;
                $clientSubscription->save();

                $this->notificationService->sendTransactionNotification(
                    $user,
                    'subscription_renewal_success',
                    ['balance' => $user->balance, 'amount' => $amountDue]
                );
            } else {
                // ── Failure: increment counter and decide next action ──
                $clientSubscription->consecutive_failures += 1;
                $clientSubscription->last_payment_status  = 'failed';
                $clientSubscription->last_billed_at       = now();
                // Do NOT advance next_billing_at — we want to retry the same period
                // via a KNET payment link.
                $clientSubscription->save();

                if ($clientSubscription->consecutive_failures >= ClientSubscription::MAX_CONSECUTIVE_FAILURES) {
                    // ── Suspend after too many failures ──
                    $clientSubscription->suspend();
                    $this->notificationService->sendTransactionNotification(
                        $user,
                        'subscription_suspended',
                        ['amount' => $amountDue]
                    );
                    Log::info('Subscription suspended after repeated failures', [
                        'client_subscription_id' => $clientSubscription->id,
                        'user_id'                => $user->id,
                    ]);
                } else {
                    // ── Send KNET payment link so the client can pay manually ──
                    try {
                        $result = $this->knetService->createSubscriptionRenewalPayment(
                            $amountDue,
                            $user->id,
                            $clientSubscription->id
                        );

                        if (($result['status'] ?? '') === 'success') {
                            // Link the pending payment so the cron skips this sub next run.
                            $clientSubscription->pending_payment_id = $result['payment_id'];
                            $clientSubscription->save();

                            $this->notificationService->sendTransactionNotification(
                                $user,
                                'subscription_renewal_payment_link',
                                ['amount' => $amountDue, 'payment_url' => $result['payment_uri']]
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::error('Failed to create KNET renewal payment link', [
                            'client_subscription_id' => $clientSubscription->id,
                            'error'                  => $e->getMessage(),
                        ]);

                        // Fall back to old notification (balance reminder without link)
                        $this->notificationService->sendTransactionNotification(
                            $user,
                            'subscription_renewal_failed',
                            ['balance' => $user->balance, 'amount' => $amountDue]
                        );
                    }
                }
            }
        });
    }
}
