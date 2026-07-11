<?php

namespace App\Console\Commands;

use App\Models\ClientSubscription;
use App\Models\Payment;
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
    protected $description = 'Charge each due client subscription for its renewal period, crediting the benefit on success or flagging the failure otherwise.';

    public function __construct(protected NotificationService $notificationService)
    {
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
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    protected function renew(ClientSubscription $clientSubscription): void
    {
        $user = $clientSubscription->user;
        $subscription = $clientSubscription->subscription;

        if (!$user || !$subscription) {
            Log::warning('Skipping subscription renewal with missing user or plan', [
                'client_subscription_id' => $clientSubscription->id,
            ]);
            return;
        }

        DB::transaction(function () use ($clientSubscription, $user, $subscription) {
            $amountDue = (float) $subscription->paid;
            $user = $user->lockForUpdate()->find($user->id) ?? $user;
            $succeeded = $amountDue <= 0 || (float) $user->balance >= $amountDue;

            Payment::create([
                'user_id' => $user->id,
                'amount' => $amountDue,
                'payment_method' => 'Wallet',
                'status' => $succeeded ? 'completed' : 'failed',
                'payment_date' => now(),
                'details' => json_encode([
                    'type' => 'subscription_renewal',
                    'client_subscription_id' => $clientSubscription->id,
                ]),
            ]);

            if ($succeeded) {
                if ($amountDue > 0) {
                    $user->decrement('balance', $amountDue);
                }
                $user->increment('balance', $subscription->benefit);
                $user->refresh();

                $clientSubscription->consecutive_failures = 0;
                $clientSubscription->last_payment_status = 'success';

                $this->notificationService->sendTransactionNotification(
                    $user,
                    'subscription_renewal_success',
                    ['balance' => $user->balance, 'amount' => $amountDue]
                );
            } else {
                $clientSubscription->consecutive_failures += 1;
                $clientSubscription->last_payment_status = 'failed';

                $this->notificationService->sendTransactionNotification(
                    $user,
                    'subscription_renewal_failed',
                    ['balance' => $user->balance, 'amount' => $amountDue]
                );
            }

            $clientSubscription->last_billed_at = now();
            $clientSubscription->next_billing_at = $subscription->getPeriodEndFrom(now());
            $clientSubscription->save();
        });
    }
}
