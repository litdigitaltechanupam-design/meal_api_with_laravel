<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MealOrder extends Model
{
    protected $fillable = [
        'user_id',
        'address_id',
        'schedule_date',
        'meal_time',
        'status',
        'subtotal',
        'delivery_charge',
        'total_amount',
        'wallet_transaction_id',
        'is_wallet_deducted',
        'deducted_at',
        'note',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'subtotal' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_wallet_deducted' => 'boolean',
        'deducted_at' => 'datetime',
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
        return $this->hasMany(MealOrderItem::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
