<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            $table->timestamp('next_billing_at')->nullable()->after('activated_at');
            $table->timestamp('last_billed_at')->nullable()->after('next_billing_at');
            $table->unsignedTinyInteger('consecutive_failures')->default(0)->after('last_billed_at');
            $table->string('last_payment_status')->nullable()->after('consecutive_failures');
        });

        // Backfill existing rows so the renewal job has a due date to work from.
        DB::table('client_subscriptions')
            ->join('subscriptions', 'subscriptions.id', '=', 'client_subscriptions.subscription_id')
            ->whereNull('client_subscriptions.next_billing_at')
            ->select('client_subscriptions.id', 'client_subscriptions.activated_at', 'client_subscriptions.created_at', 'subscriptions.period_duration', 'subscriptions.period_unit')
            ->orderBy('client_subscriptions.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $from = Carbon::parse($row->activated_at ?? $row->created_at);
                    $duration = (int) ($row->period_duration ?? 1);
                    $nextBillingAt = match ($row->period_unit) {
                        'day' => $from->copy()->addDays($duration),
                        'week' => $from->copy()->addWeeks($duration),
                        'year' => $from->copy()->addYears($duration),
                        default => $from->copy()->addMonths($duration),
                    };

                    DB::table('client_subscriptions')
                        ->where('id', $row->id)
                        ->update(['next_billing_at' => $nextBillingAt]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['next_billing_at', 'last_billed_at', 'consecutive_failures', 'last_payment_status']);
        });
    }
};
