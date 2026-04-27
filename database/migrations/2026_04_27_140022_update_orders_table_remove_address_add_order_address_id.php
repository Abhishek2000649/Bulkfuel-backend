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
        Schema::table('orders', function (Blueprint $table) {

            // 🔴 Step 1: Remove old columns
            $table->dropColumn([
                'address',
                'city',
                'state',
                'pincode'
            ]);

            // 🟢 Step 2: Add new FK
            $table->foreignId('order_address_id')
                  ->after('user_id')
                  ->constrained('order_addresses')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('orders', function (Blueprint $table) {

            // 🔄 rollback add old columns back
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();

            // 🔄 remove FK
            $table->dropForeign(['order_address_id']);
            $table->dropColumn('order_address_id');
        });
    }
};
