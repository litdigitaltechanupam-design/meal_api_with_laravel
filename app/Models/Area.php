<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'city',
        'zone',
        'status',
    ];

    public function userAddresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function deliverymanAreas(): HasMany
    {
        return $this->hasMany(DeliverymanArea::class);
    }

    public function subareas(): HasMany
    {
        return $this->hasMany(Subarea::class);
    }
}
