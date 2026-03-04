<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('m_product_families')->truncate();
        DB::table('m_product_categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ─── Categories ───────────────────────────────────────────────────────
        DB::table('m_product_categories')->insert([
            ['category_code' => 'CAT-001', 'category_name' => 'Radiator',    'is_active' => 1],
            ['category_code' => 'CAT-002', 'category_name' => 'Oil Cooler',  'is_active' => 1],
            ['category_code' => 'CAT-003', 'category_name' => 'Heater Core', 'is_active' => 1],
            ['category_code' => 'CAT-004', 'category_name' => 'Condenser',   'is_active' => 1],
            ['category_code' => 'CAT-005', 'category_name' => 'Evaporator',  'is_active' => 1],
            ['category_code' => 'CAT-006', 'category_name' => 'Intercooler', 'is_active' => 1],
        ]);

        // ─── Families ─────────────────────────────────────────────────────────
        // Ambil category IDs setelah insert
        $radiator    = DB::table('m_product_categories')->where('category_code', 'CAT-001')->value('category_id');
        $oilCooler   = DB::table('m_product_categories')->where('category_code', 'CAT-002')->value('category_id');
        $heaterCore  = DB::table('m_product_categories')->where('category_code', 'CAT-003')->value('category_id');
        $condenser   = DB::table('m_product_categories')->where('category_code', 'CAT-004')->value('category_id');
        $intercooler = DB::table('m_product_categories')->where('category_code', 'CAT-006')->value('category_id');

        DB::table('m_product_families')->insert([
            // Radiator
            ['family_code' => 'FAM-001', 'category_id' => $radiator,    'family_name' => 'Radiator Assy - Toyota',    'is_active' => 1],
            ['family_code' => 'FAM-002', 'category_id' => $radiator,    'family_name' => 'Radiator Assy - Honda',     'is_active' => 1],
            ['family_code' => 'FAM-003', 'category_id' => $radiator,    'family_name' => 'Radiator Core Only',        'is_active' => 1],
            // Oil Cooler
            ['family_code' => 'FAM-004', 'category_id' => $oilCooler,   'family_name' => 'Oil Cooler - ATF',          'is_active' => 1],
            ['family_code' => 'FAM-005', 'category_id' => $oilCooler,   'family_name' => 'Oil Cooler - Engine',       'is_active' => 1],
            // Heater Core
            ['family_code' => 'FAM-006', 'category_id' => $heaterCore,  'family_name' => 'Heater Core - Toyota',      'is_active' => 1],
            // Condenser
            ['family_code' => 'FAM-007', 'category_id' => $condenser,   'family_name' => 'Condenser - Suzuki',        'is_active' => 1],
            ['family_code' => 'FAM-008', 'category_id' => $condenser,   'family_name' => 'Condenser - Daihatsu',      'is_active' => 1],
            // Intercooler
            ['family_code' => 'FAM-009', 'category_id' => $intercooler, 'family_name' => 'Intercooler - Mitsubishi',  'is_active' => 1],
        ]);
    }
}
