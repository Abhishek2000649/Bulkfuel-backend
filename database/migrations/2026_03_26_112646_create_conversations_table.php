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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // User or Delivery Agent
            $table->unsignedBigInteger('user_id');

            // Admin
            $table->unsignedBigInteger('admin_id');

            // Role (USER / DELIVERY_AGENT)
            $table->enum('role', ['USER', 'delivery_agent']);

            // Last message preview (optional)
            $table->text('last_message')->nullable();

            // Last message time
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');

            // Prevent duplicate conversation (one chat per user)
            $table->unique(['user_id', 'admin_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
