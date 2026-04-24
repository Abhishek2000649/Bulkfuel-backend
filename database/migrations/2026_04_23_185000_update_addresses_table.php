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
         Schema::table('addresses', function (Blueprint $table) {

            // 🔹 Remove unique constraint from user_id
            // (only if it was unique before)

            // 🔹 Add new columns
            $table->string('phone_number')->after('user_id');
            $table->string('alternate_phone')->nullable()->after('phone_number');

            $table->string('house_no')->after('alternate_phone');
            $table->string('building_name')->nullable()->after('house_no');
            $table->string('street')->nullable()->after('building_name');
            $table->string('area')->after('street');
            $table->string('landmark')->nullable()->after('area');

            $table->decimal('latitude', 10, 7)->nullable()->after('landmark');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            $table->boolean('is_current')->default(0)->after('longitude');

            $table->text('delivery_instructions')->nullable()->after('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('addresses', function (Blueprint $table) {

            // 🔹 Add unique back (rollback case)

            // 🔹 Drop added columns
            $table->dropColumn([
                'phone_number',
                'alternate_phone',
                'house_no',
                'building_name',
                'street',
                'area',
                'landmark',
                'latitude',
                'longitude',
                'is_current',
                'delivery_instructions'
            ]);
        });
    
    }
};
