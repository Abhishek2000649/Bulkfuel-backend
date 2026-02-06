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
                // Address details
            $table->text('address')->after('status');
            $table->string('city')->after('address');
            $table->string('state')->after('city');
            $table->string('pincode')->after('state');

            // Payment
            $table->string('payment_method')->after('pincode');
             $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            // OPTIONAL: status enum update (placed add karna)
            $table->enum('status', [
                'PLACED',
                'PENDING',
                'CONFIRMED',
                'PACKED',
                'SHIPPED',
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'CANCELLED'
            ])->default('placed')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'city',
                'state',
                'pincode',
                'payment_method'
            ]);
        });

        });
    }
};
