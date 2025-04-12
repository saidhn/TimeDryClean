<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('deleted_by_user')->default(false)->after('isReplied'); // or $table->timestamp('user_deleted_at')->nullable()->after('isReplied');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('deleted_by_user'); // or $table->dropColumn('user_deleted_at');
        });
    }
};
