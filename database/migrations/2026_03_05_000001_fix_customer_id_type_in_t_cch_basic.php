<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Ubah tipe kolom customer_id di t_cch_basic dari unsignedBigInteger → varchar(50)
 * agar bisa menyimpan bp_code dari ERP (misal: 'CLGOMETIN', 'TMN', dsb.)
 *
 * Latar belakang: awalnya customer_id FK ke m_customers.customer_id (int),
 * tapi setelah migrasi ke ERP, referensi berubah ke business_partner.bp_code (string).
 */
return new class extends Migration
{
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        // 1. Drop FK t_cch_basic_customer_id_foreign jika masih ada
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 't_cch_basic'
              AND CONSTRAINT_NAME = 't_cch_basic_customer_id_foreign'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$dbName]);

        if (!empty($fkExists)) {
            DB::statement("ALTER TABLE `t_cch_basic` DROP FOREIGN KEY `t_cch_basic_customer_id_foreign`");
        }

        // 2. Ubah kolom customer_id menjadi varchar(50) nullable
        //    Tidak bisa pakai Blueprint::change() untuk mengubah type secara radikal di semua driver,
        //    jadi gunakan raw statement agar aman di MySQL.
        DB::statement("ALTER TABLE `t_cch_basic` MODIFY `customer_id` VARCHAR(50) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        // Kembalikan ke unsignedBigInteger nullable (tidak re-add FK ke tabel yang mungkin sudah drop)
        DB::statement("ALTER TABLE `t_cch_basic` MODIFY `customer_id` BIGINT UNSIGNED NULL DEFAULT NULL");
    }
};
