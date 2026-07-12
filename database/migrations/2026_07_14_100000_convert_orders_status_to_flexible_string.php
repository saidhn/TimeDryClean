<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // No doctrine/dbal installed — use raw SQL, MySQL-specific (this codebase
        // already assumes MySQL elsewhere, e.g. DATE_FORMAT() in EmployeeDashboardController).
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'placed'");

        // One-time backfill of historical data. See Task 9 interface notes in the
        // implementation plan for why 'Completed' maps to 'delivered': historically
        // 'Completed' was set the instant payment cleared, not when the order was
        // actually delivered, so true fulfillment state for old rows is unrecoverable —
        // this is a best-effort approximation, not a precise migration.
        DB::table('orders')->where('status', 'Pending')->update(['status' => 'placed']);
        DB::table('orders')->where('status', 'Processing')->update(['status' => 'at_facility']);
        DB::table('orders')->where('status', 'Shipped')->update(['status' => 'out_for_delivery']);
        DB::table('orders')->where('status', 'Completed')->update(['status' => 'delivered']);
        DB::table('orders')->where('status', 'Cancelled')->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'placed')->update(['status' => 'Pending']);
        DB::table('orders')->where('status', 'pickup_scheduled')->update(['status' => 'Pending']);
        DB::table('orders')->where('status', 'at_facility')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'sorting')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'washing')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'ready_for_delivery')->update(['status' => 'Processing']);
        DB::table('orders')->where('status', 'out_for_delivery')->update(['status' => 'Shipped']);
        DB::table('orders')->where('status', 'delivered')->update(['status' => 'Completed']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'Cancelled']);

        DB::statement("ALTER TABLE orders MODIFY status ENUM('Pending','Processing','Shipped','Completed','Cancelled') NOT NULL DEFAULT 'Pending'");
    }
};
