<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AreaSeeder::class,
            SubareaSeeder::class,
            SettingSeeder::class,
            MealModuleSeeder::class,
            DeliverymanAreaSeeder::class,
            DeliverymanSubareaSeeder::class,
            UserWeeklyScheduleSeeder::class,
            WalletSeeder::class,
        ]);
    }
}
