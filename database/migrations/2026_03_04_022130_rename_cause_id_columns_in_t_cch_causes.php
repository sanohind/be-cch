<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Rename kolom di t_cch_causes:
 *  - PK  : cause_id  → id
 *  - FK  : cause_id  → master_cause_id (FK ke m_causes)
 *
 * Ini menghindari konflik nama antara PK dan FK yang sama-sama bernama cause_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        // Drop FK lama jika ada
        $fks = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 't_cch_causes'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$dbName]);

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `t_cch_causes` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Rename PK: cause_id → id
        if (Schema::hasColumn('t_cch_causes', 'cause_id') && !Schema::hasColumn('t_cch_causes', 'id')) {
            DB::statement('ALTER TABLE `t_cch_causes` CHANGE `cause_id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        // Rename FK column: cause_id → master_cause_id (kolom FK ke m_causes)
        // Saat ini kolom FK bernama 'cause_id' → harus diganti agar tidak konflik dengan PK 'id' baru
        if (Schema::hasColumn('t_cch_causes', 'cause_id') && !Schema::hasColumn('t_cch_causes', 'master_cause_id')) {
            DB::statement('ALTER TABLE `t_cch_causes` CHANGE `cause_id` `master_cause_id` BIGINT UNSIGNED NULL');
        }

        // Re-add FK ke m_causes
        if (Schema::hasColumn('t_cch_causes', 'master_cause_id')) {
            Schema::table('t_cch_causes', function (Blueprint $table) {
                $table->foreign('master_cause_id')->references('id')->on('m_causes')->nullOnDelete();
            });
        }

        // Re-add FK ke t_cch
        Schema::table('t_cch_causes', function (Blueprint $table) {
            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Tidak diimplementasikan karena perubahan struktural yang kompleks
    }
};
