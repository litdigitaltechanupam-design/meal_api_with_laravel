<?php

namespace App\Services;

use App\Models\DeliverymanSubarea;
use App\Models\UserAddress;

class DeliveryAssignmentService
{
    public function resolveDeliverymanId(UserAddress $address): ?int
    {
        if (! $address->subarea_id) {
            return null;
        }

        return DeliverymanSubarea::query()
            ->where('subarea_id', $address->subarea_id)
            ->where('status', 'active')
            ->orderBy('id')
            ->value('deliveryman_id');
    }
}
