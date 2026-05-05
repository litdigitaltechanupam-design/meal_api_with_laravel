<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealPackage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function weeklyMenuItems(): HasMany
    {
        return $this->hasMany(WeeklyMenuItem::class);
    }

    public function weeklySchedules(): HasMany
    {
        return $this->hasMany(UserWeeklyMealSchedule::class);
    }

    public function calendarOverrides(): HasMany
    {
        return $this->hasMany(UserMealCalendarOverride::class);
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
