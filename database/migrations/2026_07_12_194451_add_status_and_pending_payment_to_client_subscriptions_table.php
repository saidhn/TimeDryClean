<?php

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
            // 'active'          — subscription is paid and in good standing
            // 'pending_payment' — awaiting KNET payment (initial sign-up or renewal)
            // 'suspended'       — cancelled/suspended due to 3+ consecutive billing failures
            $table->string('status')->default('active')->after('last_payment_status')
                ->comment('active | pending_payment | suspended');

            // Track the in-flight KNET Payment record so we do not send duplicate
            // payment links while one is already outstanding.
            $table->foreignId('pending_payment_id')
                ->nullable()
                ->after('status')
                ->constrained('payments')
                ->nullOnDelete()
                ->comment('KNET payment record awaiting confirmation for this subscription');
        });

        // Backfill: all existing subscriptions are already active.
        DB::table('client_subscriptions')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['pending_payment_id']);
            $table->dropColumn(['status', 'pending_payment_id']);
        });
    }
};
