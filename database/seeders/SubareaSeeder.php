<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Subarea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubareaSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'Modina Market' => ['Modina Market North', 'Modina Market South'],
            'Korer Para' => ['Korer Para Office Zone', 'Korer Para Residential'],
            'Housing Estate' => ['Housing Block A', 'Housing Block B'],
            'Shibgonj' => ['Shibgonj Main Road', 'Shibgonj Inside'],
            'Ambarkhana' => ['Ambarkhana Point', 'Ambarkhana East'],
        ];

        foreach ($map as $areaName => $subareas) {
            $area = Area::query()->where('name', $areaName)->first();
            if (! $area) {
                continue;
            }

            foreach ($subareas as $name) {
                Subarea::updateOrCreate(
                    [
                        'area_id' => $area->id,
                        'name' => $name,
                    ],
                    [
                        'slug' => Str::slug($name.'-'.$area->id),
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
