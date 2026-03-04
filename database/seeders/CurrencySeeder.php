<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('m_currencies')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('m_currencies')->insert([
            ['currency_id' => 1, 'currency_code' => 'IDR', 'currency_name' => 'Indonesian Rupiah'],
            ['currency_id' => 2, 'currency_code' => 'JPY', 'currency_name' => 'Japanese Yen'],
            ['currency_id' => 3, 'currency_code' => 'USD', 'currency_name' => 'US Dollar'],
            ['currency_id' => 4, 'currency_code' => 'EUR', 'currency_name' => 'Euro'],
            ['currency_id' => 5, 'currency_code' => 'SGD', 'currency_name' => 'Singapore Dollar'],
        ]);
    }
}
