<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * activated_at = when the client subscribed; used to calculate period end (activated_at + period).
     */
    public function up(): void
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('subscription_id')
                ->comment('When the client subscribed; period validity starts from this date');
        });

        // Set activated_at = created_at for existing records
        \DB::table('client_subscriptions')->whereNull('activated_at')->update([
            'activated_at' => \DB::raw('created_at')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            $table->dropColumn('activated_at');
        });
    }
};
