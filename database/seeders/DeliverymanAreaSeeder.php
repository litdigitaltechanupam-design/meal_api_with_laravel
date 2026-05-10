<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\DeliverymanArea;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeliverymanAreaSeeder extends Seeder
{
    public function run(): void
    {
        $deliveryman = User::query()->where('role', 'deliveryman')->first();

        if (! $deliveryman) {
            return;
        }

        $areaIds = Area::query()
            ->whereIn('name', ['Modina Market', 'Korer Para', 'Housing Estate'])
            ->pluck('id');

        foreach ($areaIds as $areaId) {
            DeliverymanArea::updateOrCreate(
                [
                    'deliveryman_id' => $deliveryman->id,
                    'area_id' => $areaId,
                ],
                [
                    'status' => 'active',
                ]
            );
        }
    }
}
