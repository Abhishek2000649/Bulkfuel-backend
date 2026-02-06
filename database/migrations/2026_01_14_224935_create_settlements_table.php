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
        Schema::create('settlements', function (Blueprint $table) {
  $table->id();

        // Delivery agent reference
        $table->unsignedBigInteger('delivery_agent_id');

        // Total settlement amount
        $table->decimal('total_amount', 10, 2);

        // Settlement period
        $table->date('from_date');
        $table->date('to_date');

        // Settlement details
        $table->enum('settlement_mode', ['CASH', 'BANK', 'UPI']) ->nullable(); 
        $table->date('settlement_date')->nullable();

        // Settlement status
        $table->enum('status', ['PENDING', 'SETTLED'])
              ->default('PENDING');

        // Timestamps
        $table->timestamps();

        // Foreign key (optional but recommended)
        $table->foreign('delivery_agent_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
