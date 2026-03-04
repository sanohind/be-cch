<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlantAndProcessSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('m_processes')->truncate();
        DB::table('m_plants')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ─── Plants ───────────────────────────────────────────────────────────
        DB::table('m_plants')->insert([
            ['plant_code' => 'PLT-A', 'plant_name' => 'Plant Tangerang',  'is_active' => 1],
            ['plant_code' => 'PLT-B', 'plant_name' => 'Plant Karawang',   'is_active' => 1],
            ['plant_code' => 'PLT-C', 'plant_name' => 'Plant Cikampek',   'is_active' => 1],
        ]);

        // ─── Processes ────────────────────────────────────────────────────────
        DB::table('m_processes')->insert([
            // Plant Tangerang
            ['process_code' => 'PRC-T01', 'plant_id' => 1, 'process_name' => 'Brazing',          'is_active' => 1],
            ['process_code' => 'PRC-T02', 'plant_id' => 1, 'process_name' => 'Welding',          'is_active' => 1],
            ['process_code' => 'PRC-T03', 'plant_id' => 1, 'process_name' => 'Assembly',         'is_active' => 1],
            ['process_code' => 'PRC-T04', 'plant_id' => 1, 'process_name' => 'Final Inspection',  'is_active' => 1],
            ['process_code' => 'PRC-T05', 'plant_id' => 1, 'process_name' => 'Leak Test',        'is_active' => 1],
            // Plant Karawang
            ['process_code' => 'PRC-K01', 'plant_id' => 2, 'process_name' => 'Stamping',         'is_active' => 1],
            ['process_code' => 'PRC-K02', 'plant_id' => 2, 'process_name' => 'Painting',         'is_active' => 1],
            ['process_code' => 'PRC-K03', 'plant_id' => 2, 'process_name' => 'Assembly',         'is_active' => 1],
            ['process_code' => 'PRC-K04', 'plant_id' => 2, 'process_name' => 'Final Inspection',  'is_active' => 1],
            // Plant Cikampek
            ['process_code' => 'PRC-C01', 'plant_id' => 3, 'process_name' => 'Machining',        'is_active' => 1],
            ['process_code' => 'PRC-C02', 'plant_id' => 3, 'process_name' => 'Heat Treatment',   'is_active' => 1],
            ['process_code' => 'PRC-C03', 'plant_id' => 3, 'process_name' => 'Quality Check',    'is_active' => 1],
        ]);
    }
}
