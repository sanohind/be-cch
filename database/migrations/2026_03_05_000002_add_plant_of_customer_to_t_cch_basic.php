<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan kolom plant_of_customer (FK → m_plants.plant_id) ke t_cch_basic.
 * Kolom ini menggantikan customer_plant_id yang sudah di-drop sebelumnya.
 * Nullable karena plant tidak selalu diketahui saat entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_cch_basic', function (Blueprint $table) {
            if (!Schema::hasColumn('t_cch_basic', 'plant_of_customer')) {
                $table->unsignedBigInteger('plant_of_customer')->nullable()->after('customer_id');
                $table->foreign('plant_of_customer')
                      ->references('plant_id')
                      ->on('m_plants')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('t_cch_basic', function (Blueprint $table) {
            $table->dropForeign(['plant_of_customer']);
            $table->dropColumn('plant_of_customer');
        });
    }
};
