<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FailureModeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('m_failure_modes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('m_failure_modes')->insert([
            ['failure_mode_code' => 'FM-001', 'failure_mode_name' => 'Leak',           'is_active' => 1],
            ['failure_mode_code' => 'FM-002', 'failure_mode_name' => 'Crack',          'is_active' => 1],
            ['failure_mode_code' => 'FM-003', 'failure_mode_name' => 'Dent',           'is_active' => 1],
            ['failure_mode_code' => 'FM-004', 'failure_mode_name' => 'Scratch',        'is_active' => 1],
            ['failure_mode_code' => 'FM-005', 'failure_mode_name' => 'Dimension NG',   'is_active' => 1],
            ['failure_mode_code' => 'FM-006', 'failure_mode_name' => 'Missing Part',   'is_active' => 1],
            ['failure_mode_code' => 'FM-007', 'failure_mode_name' => 'Wrong Part',     'is_active' => 1],
            ['failure_mode_code' => 'FM-008', 'failure_mode_name' => 'Contamination',  'is_active' => 1],
            ['failure_mode_code' => 'FM-009', 'failure_mode_name' => 'Surface Defect', 'is_active' => 1],
            ['failure_mode_code' => 'FM-010', 'failure_mode_name' => 'Other',          'is_active' => 1],
        ]);
    }
}
