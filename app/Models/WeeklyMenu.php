<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyMenu extends Model
{
    protected $fillable = [
        'day_of_week',
        'meal_time',
        'status',
        'created_by',
        'updated_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(WeeklyMenuItem::class);
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
