<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();

            // 🔹 Optional reference to user_addresses
            $table->foreignId('user_address_id')
                  ->nullable()
                  ->constrained('addresses')
                  ->nullOnDelete();

            // 🔹 Snapshot fields (copy from addresses)
            $table->string('phone_number');
            $table->string('alternate_phone')->nullable();

            $table->string('house_no');
            $table->string('building_name')->nullable();
            $table->text('address');
            $table->string('street')->nullable();
            $table->string('area');
            $table->string('landmark')->nullable();

            $table->string('city');
            $table->string('state');
            $table->string('pincode');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->text('delivery_instructions')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
