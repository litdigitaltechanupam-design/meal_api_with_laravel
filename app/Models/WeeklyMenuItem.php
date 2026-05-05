<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyMenuItem extends Model
{
    protected $fillable = [
        'day_of_week',
        'meal_time',
        'meal_package_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function mealPackage(): BelongsTo
    {
        return $this->belongsTo(MealPackage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
