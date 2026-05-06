<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCalendarOverrideItem extends Model
{
    protected $fillable = [
        'user_calendar_override_id',
        'meal_package_id',
        'quantity',
    ];

    public function userCalendarOverride(): BelongsTo
    {
        return $this->belongsTo(UserCalendarOverride::class);
    }

    public function mealPackage(): BelongsTo
    {
        return $this->belongsTo(MealPackage::class);
    }
}
