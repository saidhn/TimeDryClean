<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('province_id')->constrained('provinces');
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            $table->foreignId('province_id')->nullable()->constrained('provinces'); // Optional, but useful
            $table->string('street')->nullable();
            $table->string('building')->nullable();
            $table->integer('floor')->nullable();
            $table->string('apartment_number')->nullable();
            $table->timestamps();
        });

        // Modify the users table (or create it if it doesn't exist)
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type')->default('client'); // Add a user type column
            $table->foreignId('address_id')->nullable()->constrained('addresses'); // Add address relationship
        });


        // Create separate password resets tables to keep them separate
        Schema::create('client_password_resets', function (Blueprint $table) {
            $table->string('mobile')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('driver_password_resets', function (Blueprint $table) {
            $table->string('mobile')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('employee_password_resets', function (Blueprint $table) {
            $table->string('mobile')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('admin_password_resets', function (Blueprint $table) {
            $table->string('mobile')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['address_id']); // Drop the foreign key constraint first
            $table->dropColumn('address_id');
            $table->dropColumn('user_type');
        });
        Schema::dropIfExists('client_password_resets');
        Schema::dropIfExists('driver_password_resets');
        Schema::dropIfExists('employee_password_resets');
        Schema::dropIfExists('admin_password_resets');
        Schema::dropIfExists('addresses'); // Then drop addresses
        Schema::dropIfExists('cities');    // Then drop cities
        Schema::dropIfExists('provinces'); // Finally drop provinces
    }
};
