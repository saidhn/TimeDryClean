<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('set null')->after('user_id');
            $table->string('street')->nullable()->after('address_id')->comment('The street of delivery');
            $table->string('building')->nullable()->after('street')->comment('The building of delivery');
            $table->integer('floor')->nullable()->after('building')->comment('The floor of delivery');
            $table->string('apartment_number')->nullable()->after('floor')->comment('The apartment number of delivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropColumn(['address_id', 'street', 'building', 'floor', 'apartment_number']);
        });
    }
};
