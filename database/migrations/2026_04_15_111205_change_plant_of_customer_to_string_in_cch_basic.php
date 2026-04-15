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
        // Drop foreign key first (if exists), then change column type
        Schema::table('t_cch_basic', function (Blueprint $table) {
            // Drop FK constraint if any — ignore errors gracefully
            try {
                $table->dropForeign(['plant_of_customer']);
            } catch (\Throwable $e) {}
        });
        Schema::table('t_cch_basic', function (Blueprint $table) {
            $table->string('plant_of_customer', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: cannot fully reverse as data may have been changed to text
        Schema::table('t_cch_basic', function (Blueprint $table) {
            $table->string('plant_of_customer', 255)->nullable()->change();
        });
    }
};
