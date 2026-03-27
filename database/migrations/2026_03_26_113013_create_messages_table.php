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
        Schema::create('messages', function (Blueprint $table) {
              $table->id();

            // Conversation reference
            $table->unsignedBigInteger('conversation_id');

            // Sender info
            $table->unsignedBigInteger('sender_id');
            $table->enum('sender_type', ['USER', 'ADMIN', 'delivery_agent']);

            // Message content
            $table->text('message')->nullable();

            // Optional file/image
            $table->string('file')->nullable();

            // Message type
            $table->enum('type', ['text', 'image', 'file'])->default('text');

            // Seen status
            $table->boolean('is_seen')->default(false);

            $table->timestamps();

            // Foreign Keys
            $table->foreign('conversation_id')
                ->references('id')
                ->on('conversations')
                ->onDelete('cascade');

            $table->foreign('sender_id')
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
        Schema::dropIfExists('messages');
    }
};
