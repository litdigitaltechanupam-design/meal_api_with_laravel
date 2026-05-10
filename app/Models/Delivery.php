<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'meal_order_id',
        'deliveryman_id',
        'status',
        'assigned_at',
        'picked_at',
        'delivered_at',
        'failed_at',
        'note',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function mealOrder(): BelongsTo
    {
        return $this->belongsTo(MealOrder::class);
    }

    public function deliveryman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deliveryman_id');
    }
}
