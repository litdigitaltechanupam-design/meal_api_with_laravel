<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'app_timezone' => 'Asia/Dhaka',
            'delivery_charge_enabled' => '1',
            'delivery_charge_amount' => '20',
            'lunch_cutoff_time' => '10:00',
            'dinner_cutoff_time' => '16:00',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
