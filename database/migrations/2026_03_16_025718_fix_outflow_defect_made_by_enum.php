<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename enum value 'Sanoh_group' → 'Other_sanoh_plant' in t_cch_outflow.defect_made_by.
 *
 * MySQL tidak mendukung ALTER COLUMN ENUM secara langsung tanpa DROP + ADD,
 * sehingga kita pakai MODIFY COLUMN yang mengandung semua nilai enum baru,
 * lalu UPDATE data lama, kemudian hapus nilai lama dari enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Langkah 1: Perluas enum agar memuat KEDUA nilai (lama & baru) sekaligus
        DB::statement("
            ALTER TABLE t_cch_outflow
            MODIFY COLUMN defect_made_by
                ENUM('Own_plant', 'Sanoh_group', 'Other_sanoh_plant', 'Supplier', 'Unknown')
                NOT NULL
        ");

        // Langkah 2: Migrasi data yang sudah ada
        DB::statement("
            UPDATE t_cch_outflow
            SET defect_made_by = 'Other_sanoh_plant'
            WHERE defect_made_by = 'Sanoh_group'
        ");

        // Langkah 3: Hapus nilai lama dari enum
        DB::statement("
            ALTER TABLE t_cch_outflow
            MODIFY COLUMN defect_made_by
                ENUM('Own_plant', 'Other_sanoh_plant', 'Supplier', 'Unknown')
                NOT NULL
        ");
    }

    public function down(): void
    {
        // Rollback: kembalikan enum ke kondisi semula
        DB::statement("
            ALTER TABLE t_cch_outflow
            MODIFY COLUMN defect_made_by
                ENUM('Own_plant', 'Sanoh_group', 'Other_sanoh_plant', 'Supplier', 'Unknown')
                NOT NULL
        ");

        DB::statement("
            UPDATE t_cch_outflow
            SET defect_made_by = 'Sanoh_group'
            WHERE defect_made_by = 'Other_sanoh_plant'
        ");

        DB::statement("
            ALTER TABLE t_cch_outflow
            MODIFY COLUMN defect_made_by
                ENUM('Own_plant', 'Sanoh_group', 'Supplier', 'Unknown')
                NOT NULL
        ");
    }
};
