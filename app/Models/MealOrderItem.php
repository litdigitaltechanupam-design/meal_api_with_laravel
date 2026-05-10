<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealOrderItem extends Model
{
    protected $fillable = [
        'meal_order_id',
        'meal_package_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function mealOrder(): BelongsTo
    {
        return $this->belongsTo(MealOrder::class);
    }

    public function mealPackage(): BelongsTo
    {
        return $this->belongsTo(MealPackage::class);
    }
}
