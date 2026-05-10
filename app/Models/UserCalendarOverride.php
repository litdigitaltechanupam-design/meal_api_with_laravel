<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCalendarOverride extends Model
{
    protected $fillable = [
        'user_id',
        'address_id',
        'schedule_date',
        'meal_time',
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

    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UserCalendarOverrideItem::class);
    }
}
