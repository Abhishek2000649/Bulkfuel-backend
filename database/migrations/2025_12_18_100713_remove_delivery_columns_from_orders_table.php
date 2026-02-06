<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropColumn([
                'delivery_agent_id',
                'delivery_status',
                'packed_at',
                'shipped_at',
                'out_for_delivery_at',
                'delivered_at',
                'cancelled_at',
                'cancel_reason',
            ]);

        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->unsignedBigInteger('delivery_agent_id')->nullable();
            $table->enum('delivery_status', ['ACCEPTED','REJECTED'])->nullable();

            $table->timestamp('packed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('cancel_reason')->nullable();
        });
    }
};
