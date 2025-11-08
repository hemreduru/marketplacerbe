<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'avatar',
        'password',
        'settings_id',
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

    /**
     * Get the products owned by this user.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the marketplace credentials for this user.
     */
    public function marketplaceCredentials(): HasMany
    {
        return $this->hasMany(UserMarketplaceCredential::class);
    }

    /**
     * Get the marketplace products owned by this user.
     */
    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    /**
     * Get the sync logs for this user.
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(MarketplaceSyncLog::class);
    }

    /**
     * Get the marketplace orders for this user.
     */
    public function marketplaceOrders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class);
    }
}
