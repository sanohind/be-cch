<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Drop tabel-tabel lokal yang sudah tidak diperlukan karena
 * data Customer dan Supplier sekarang langsung dibaca dari
 * ERP (tabel business_partner di database soi107).
 *
 * Tabel yang dihapus:
 *   - m_suppliers          (digantikan ERP business_partner role=S/B)
 *   - m_customer_plants    (tidak dipakai, ERP tidak punya sub-plant)
 *   - m_customers          (digantikan ERP business_partner role=C/B)
 */
return new class extends Migration
{
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        // Helper: cek dan drop FK jika masih ada
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

        // ── 1. Drop FK dari t_cch_basic ─────────────────────────────────────
        $dropFkIfExists('t_cch_basic', 't_cch_basic_customer_id_foreign');
        $dropFkIfExists('t_cch_basic', 't_cch_basic_customer_plant_id_foreign');

        // ── 2. Drop FK dari t_cch_occurrence ────────────────────────────────
        $dropFkIfExists('t_cch_occurrence', 't_cch_occurrence_supplier_id_foreign');

        // ── 3. Drop FK dari t_cch_outflow ───────────────────────────────────
        $dropFkIfExists('t_cch_outflow', 't_cch_outflow_supplier_id_foreign');

        // ── 4. Drop kolom customer_plant_id dari t_cch_basic (tidak dipakai lagi)
        if (Schema::hasColumn('t_cch_basic', 'customer_plant_id')) {
            Schema::table('t_cch_basic', function (Blueprint $table) {
                $table->dropColumn('customer_plant_id');
            });
        }

        // ── 5. Hapus tabel lokal ─────────────────────────────────────────────
        Schema::dropIfExists('m_customer_plants');
        Schema::dropIfExists('m_customers');
        Schema::dropIfExists('m_suppliers');
    }

    public function down(): void
    {
        // Reverting tidak dilakukan — data sudah ada di ERP
    }
};
