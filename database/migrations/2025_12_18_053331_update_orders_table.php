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

            $table->unsignedBigInteger('delivery_agent_id')->after('id')->nullable();
            $table->enum('delivery_status',['ACCEPTED','REJECTED'])->nullable();
            $table->string('cancel_reason')->nullable();
             $table->timestamp('packed_at')->nullable()->after('status');
            $table->timestamp('shipped_at')->nullable()->after('packed_at');
            $table->timestamp('out_for_delivery_at')->nullable()->after('shipped_at');
            $table->timestamp('delivered_at')->nullable()->after('out_for_delivery_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
