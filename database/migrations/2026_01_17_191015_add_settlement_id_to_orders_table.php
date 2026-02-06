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
              $table->unsignedBigInteger('settlement_id')->nullable()->after('id');

            $table->foreign('settlement_id', 'fk_orders_settlement')
                  ->references('id')
                  ->on('settlements')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
              $table->dropForeign('fk_orders_settlement');
            $table->dropColumn('settlement_id');
        });
    }
};
