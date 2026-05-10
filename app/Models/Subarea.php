<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subarea extends Model
{
    protected $fillable = [
        'area_id',
        'name',
        'slug',
        'status',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function userAddresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function deliverymanSubareas(): HasMany
    {
        return $this->hasMany(DeliverymanSubarea::class);
    }
}
