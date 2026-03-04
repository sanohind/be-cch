<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK constraint dulu (jika ada) sebelum ALTER COLUMN
        $dbName = DB::getDatabaseName();
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 't_cch_basic'
            AND CONSTRAINT_NAME = 't_cch_basic_customer_id_foreign' AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$dbName]);

        if (!empty($fkExists)) {
            DB::statement("ALTER TABLE `t_cch_basic` DROP FOREIGN KEY `t_cch_basic_customer_id_foreign`");
        }

        // Make customer_id nullable
        Schema::table('t_cch_basic', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        // Re-add FK constraint with nullOnDelete
        Schema::table('t_cch_basic', function (Blueprint $table) {
            $table->foreign('customer_id')
                  ->references('customer_id')
                  ->on('m_customers')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('t_cch_basic', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });
    }
};
