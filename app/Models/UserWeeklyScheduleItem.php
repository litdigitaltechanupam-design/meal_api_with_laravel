<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWeeklyScheduleItem extends Model
{
    protected $fillable = [
        'user_weekly_schedule_id',
        'meal_package_id',
        'quantity',
    ];

    public function userWeeklySchedule(): BelongsTo
    {
        return $this->belongsTo(UserWeeklySchedule::class);
    }

    public function mealPackage(): BelongsTo
    {
        return $this->belongsTo(MealPackage::class);
    }
}
