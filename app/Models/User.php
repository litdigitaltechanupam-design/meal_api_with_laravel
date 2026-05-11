<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function walletPaymentRequests(): HasMany
    {
        return $this->hasMany(WalletPaymentRequest::class);
    }

    public function userWeeklySchedules(): HasMany
    {
        return $this->hasMany(UserWeeklySchedule::class);
    }

    public function userCalendarOverrides(): HasMany
    {
        return $this->hasMany(UserCalendarOverride::class);
    }

    public function mealOrders(): HasMany
    {
        return $this->hasMany(MealOrder::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'deliveryman_id');
    }

    public function deliverymanAreas(): HasMany
    {
        return $this->hasMany(DeliverymanArea::class, 'deliveryman_id');
    }

    public function deliverymanSubareas(): HasMany
    {
        return $this->hasMany(DeliverymanSubarea::class, 'deliveryman_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function issueApiToken(string $name = 'default'): array
    {
        $plainTextToken = Str::random(60);

        $token = $this->apiTokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'last_used_at' => now(),
        ]);

        return [
            'plain_text_token' => $plainTextToken,
            'token' => $token,
        ];
    }
}
