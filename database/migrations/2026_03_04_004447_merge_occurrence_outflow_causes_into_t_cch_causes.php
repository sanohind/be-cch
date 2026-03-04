<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Menggabungkan t_cch_occurrence_causes dan t_cch_outflow_causes
 * menjadi satu tabel t_cch_causes dengan kolom cause_type untuk membedakannya.
 *
 * Perubahan dari struktur lama:
 *   - Dihapus: primary_factor (tidak diperlukan)
 *   - Dihapus: outflow_type  (digantikan oleh cause_type)
 *   - Ditambah: cause_type ENUM('occurrence', 'outflow') untuk membedakan
 *
 * Struktur tabel baru:
 *   cause_id     → PK auto increment
 *   cch_id       → FK ke t_cch
 *   cause_type   → ENUM: 'occurrence' atau 'outflow'
 *   master_cause_id → FK ke m_occurrence_causes (nullable, pilihan dari master)
 *   cause_description → deskripsi bebas
 *   sort_order   → urutan tampil
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

        // Drop FK dan tabel lama
        $dropFkIfExists('t_cch_occurrence_causes', 't_cch_occurrence_causes_cch_id_foreign');
        $dropFkIfExists('t_cch_occurrence_causes', 't_cch_occurrence_causes_cause_id_foreign');
        $dropFkIfExists('t_cch_outflow_causes', 't_cch_outflow_causes_cch_id_foreign');
        $dropFkIfExists('t_cch_outflow_causes', 't_cch_outflow_causes_cause_id_foreign');

        Schema::dropIfExists('t_cch_occurrence_causes');
        Schema::dropIfExists('t_cch_outflow_causes');

        // Buat tabel baru gabungan
        Schema::create('t_cch_causes', function (Blueprint $table) {
            $table->id('cause_id');
            $table->unsignedBigInteger('cch_id');
            $table->enum('cause_type', ['occurrence', 'outflow'])
                  ->comment('occurrence = Block 8, outflow = Block 9');
            $table->unsignedBigInteger('master_cause_id')->nullable()
                  ->comment('FK ke m_occurrence_causes (nullable, boleh isi bebas)');
            $table->text('cause_description');
            $table->integer('sort_order')->default(1);

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('master_cause_id')->references('cause_id')->on('m_occurrence_causes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_causes');
    }
};
