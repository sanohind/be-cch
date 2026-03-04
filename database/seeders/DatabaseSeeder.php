<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Jalankan dengan:
     *   php artisan db:seed
     *
     * Atau reset + seed dari awal:
     *   php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            FailureModeSeeder::class,
            PlantAndProcessSeeder::class,
            ProductCategorySeeder::class,
            CurrencySeeder::class,
            CauseSeeder::class,
        ]);
    }
}
