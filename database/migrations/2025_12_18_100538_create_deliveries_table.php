<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {

            $table->id();

            // Relations
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('delivery_agent_id')->nullable();

            // Status handled by delivery boy
            $table->enum('delivery_status', ['ACCEPTED', 'REJECTED'])->nullable();

            // Delivery lifecycle status
            $table->enum('order_status', [
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'CANCELLED'
            ])->nullable();

            // Timeline
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Cancel reason
            $table->string('cancel_reason')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');

            $table->foreign('delivery_agent_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
