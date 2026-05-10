<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Modina Market', 'city' => 'Sylhet', 'zone' => 'South', 'status' => 'active'],
            ['name' => 'Korer Para', 'city' => 'Sylhet', 'zone' => 'South', 'status' => 'active'],
            ['name' => 'Housing Estate', 'city' => 'Sylhet', 'zone' => 'West', 'status' => 'active'],
            ['name' => 'Shibgonj', 'city' => 'Sylhet', 'zone' => 'East', 'status' => 'active'],
            ['name' => 'Ambarkhana', 'city' => 'Sylhet', 'zone' => 'Central', 'status' => 'active'],
        ];

        foreach ($areas as $area) {
            Area::updateOrCreate(
                ['name' => $area['name']],
                [
                    'slug' => Str::slug($area['name']),
                    'city' => $area['city'],
                    'zone' => $area['zone'],
                    'status' => $area['status'],
                ]
            );
        }
    }
}
