<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Replaces start_date and end_date with period_duration and period_unit.
     * The period defines how long a user can benefit from the subscription (e.g., 1 month, 2 months).
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('period_duration')->default(1)->after('benefit')
                ->comment('The duration value (e.g., 1, 2, 3)');
            $table->string('period_unit', 20)->default('month')->after('period_duration')
                ->comment('The unit: day, week, month, year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['period_duration', 'period_unit']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->date('start_date')->nullable()->comment('The start date of the subscription');
            $table->date('end_date')->nullable()->comment('The end date of the subscription');
        });
    }
};
