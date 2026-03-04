<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi ini mengubah kolom supplier_id pada t_cch_occurrence dan t_cch_outflow
 * dari integer (FK ke m_suppliers yang sudah dihapus) menjadi varchar (bp_code dari ERP).
 *
 * Sekaligus menghapus FK lama yang sudah tidak valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        $dropFkIfExists = function (string $table, string $fkName) use ($dbName) {
            $exists = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$dbName, $table, $fkName]);

            if (!empty($exists)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
            }
        };

        // ── t_cch_occurrence ──────────────────────────────────────────────────
        $dropFkIfExists('t_cch_occurrence', 't_cch_occurrence_supplier_id_foreign');

        Schema::table('t_cch_occurrence', function (Blueprint $table) {
            $table->dropColumn('supplier_id');
        });

        Schema::table('t_cch_occurrence', function (Blueprint $table) {
            // bp_code dari ERP adalah varchar (contoh: 'C001', 'TOYODA001', dll)
            $table->string('supplier_id', 50)->nullable()->after('responsible_plant_detail')
                  ->comment('bp_code dari ERP business_partner table');
        });

        // ── t_cch_outflow ─────────────────────────────────────────────────────
        $dropFkIfExists('t_cch_outflow', 't_cch_outflow_supplier_id_foreign');

        Schema::table('t_cch_outflow', function (Blueprint $table) {
            $table->dropColumn('supplier_id');
        });

        Schema::table('t_cch_outflow', function (Blueprint $table) {
            $table->string('supplier_id', 50)->nullable()->after('responsible_plant_detail')
                  ->comment('bp_code dari ERP business_partner table');
        });
    }

    public function down(): void
    {
        // Tidak dilakukan karena m_suppliers sudah dihapus
    }
};
