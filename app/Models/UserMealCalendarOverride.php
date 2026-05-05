<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMealCalendarOverride extends Model
{
    protected $fillable = [
        'user_id',
        'schedule_date',
        'meal_time',
        'meal_package_id',
        'is_off',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'is_off' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mealPackage(): BelongsTo
    {
        return $this->belongsTo(MealPackage::class);
    }
}
