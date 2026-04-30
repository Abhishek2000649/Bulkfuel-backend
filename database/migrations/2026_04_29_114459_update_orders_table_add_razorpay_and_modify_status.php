<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropColumn('payment_status');
            $table->string('razorpay_order_id')->nullable()->after('id');
            $table->enum('payment_status', ['PENDING', 'PAID', 'FAILED'])
                ->default('PENDING')
                ->after('razorpay_order_id'); 
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropColumn('payment_status');

            $table->enum('payment_status', ['paid', 'unpaid'])
                ->default('unpaid');

            $table->dropColumn('razorpay_order_id');
        });
    }
};
