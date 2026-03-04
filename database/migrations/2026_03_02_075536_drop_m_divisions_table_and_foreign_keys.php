<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys safely using raw SQL (IF EXISTS not supported in MySQL for FK,
        // so we check information_schema first)
        $fks = [
            ['table' => 't_cch',       'fk' => 't_cch_division_id_foreign'],
            ['table' => 'cch_users',   'fk' => 'cch_users_division_id_foreign'],
            ['table' => 't_cch_basic', 'fk' => 't_cch_basic_division_id_foreign'],
        ];

        $dbName = DB::getDatabaseName();

        foreach ($fks as $item) {
            $exists = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$dbName, $item['table'], $item['fk']]);

            if (!empty($exists)) {
                DB::statement("ALTER TABLE `{$item['table']}` DROP FOREIGN KEY `{$item['fk']}`");
            }
        }

        // Now safely drop the m_divisions table
        Schema::dropIfExists('m_divisions');
    }

    public function down(): void
    {
        // Reverting this is not needed in development flow
    }
};
