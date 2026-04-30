<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('transactions', function (Blueprint $table) {

        if (Schema::hasColumn('transactions', 'created_at')) {
            $table->dropColumn('created_at');
        }

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::table('transactions', function (Blueprint $table) {

        // timestamps remove karo
        $table->dropTimestamps();

        // optional: created_at wapas add kar sakte ho
        $table->timestamp('created_at')->useCurrent();
    });
}
};
