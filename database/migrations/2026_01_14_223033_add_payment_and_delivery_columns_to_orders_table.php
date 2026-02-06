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
            

        // 2. Payment status
        $table->enum('payment_status', ['PAID', 'UNPAID'])
              ->default('UNPAID')
              ->after('payment_method');
        

        // 4. Settlement status (mainly for COD)
        $table->enum('settlement_status', ['PENDING', 'SETTLED','NOT_REQUIRED' ])
              ->nullable()
              ->after('payment_status');

        // 5. Delivery OTP
        $table->string('delivery_otp', 6)
              ->nullable()
              ->after('settlement_status');

        // 6. OTP verified time
        $table->timestamp('otp_verified_at')
              ->nullable()
              ->after('delivery_otp');
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
               $table->dropColumn([
            
            'payment_status',
            
            'settlement_status',
            'delivery_otp',
            'otp_verified_at'
        ]);
        });
    }
};
