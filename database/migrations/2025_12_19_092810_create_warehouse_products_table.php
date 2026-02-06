<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('warehouse_products', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('warehouse_id');
        $table->unsignedBigInteger('product_id');
        $table->integer('stock_quantity')->default(0);
        $table->timestamps();

        $table->foreign('warehouse_id')
              ->references('id')->on('warehouses')
              ->onDelete('cascade');

        $table->foreign('product_id')
              ->references('id')->on('products')
              ->onDelete('cascade');

        $table->unique(['warehouse_id', 'product_id']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_products');
    }
};
