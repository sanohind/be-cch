<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Menyederhanakan tabel m_occurrence_causes menjadi m_causes
 * dan mengubah field di t_cch_causes sesuai permintaan:
 * - t_cch_causes: tambah primary_factor, ubah master_cause_id -> cause_id (FK ke m_causes).
 * - m_causes: hanya id, type(outflow/occurrence), cause_name, description, is_active.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        // 1. Drop FK lama
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

        $dropFkIfExists('t_cch_causes', 't_cch_causes_master_cause_id_foreign');

        // 2. Kolom master_cause_id didrop dari t_cch_causes karena akan diganti jadi cause_id
        if (Schema::hasColumn('t_cch_causes', 'master_cause_id')) {
            Schema::table('t_cch_causes', function (Blueprint $table) {
                $table->dropColumn('master_cause_id');
            });
        }

        // 3. Drop master table lama
        Schema::dropIfExists('m_occurrence_causes');

        // 4. Buat master table baru m_causes
        if (!Schema::hasTable('m_causes')) {
            Schema::create('m_causes', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['occurrence', 'outflow'])->comment('Untuk filter di dropdown');
                $table->string('cause_name', 200);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
            });
        }

        // 5. Sesuaikan t_cch_causes
        Schema::table('t_cch_causes', function (Blueprint $table) {
            if (!Schema::hasColumn('t_cch_causes', 'primary_factor')) {
                $table->enum('primary_factor', ['Man', 'Method', 'Machine', 'Material', 'Design'])
                      ->nullable()
                      ->after('cause_type');
            }

            if (!Schema::hasColumn('t_cch_causes', 'cause_id')) {
                $table->unsignedBigInteger('cause_id')->nullable()->after('primary_factor')
                      ->comment('Pilih cause dari m_causes DB');
                $table->foreign('cause_id')->references('id')->on('m_causes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_causes');
    }
};
