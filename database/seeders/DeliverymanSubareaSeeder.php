<?php

namespace Database\Seeders;

use App\Models\DeliverymanSubarea;
use App\Models\Subarea;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeliverymanSubareaSeeder extends Seeder
{
    public function run(): void
    {
        $deliveryman = User::query()->where('role', 'deliveryman')->first();

        if (! $deliveryman) {
            return;
        }

        $subareaIds = Subarea::query()
            ->whereIn('name', ['Modina Market North', 'Korer Para Office Zone', 'Housing Block A'])
            ->pluck('id');

        foreach ($subareaIds as $subareaId) {
            DeliverymanSubarea::updateOrCreate(
                [
                    'deliveryman_id' => $deliveryman->id,
                    'subarea_id' => $subareaId,
                ],
                [
                    'status' => 'active',
                ]
            );
        }
    }
}
