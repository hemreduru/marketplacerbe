<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
     * Get the marketplace credentials for this user.
     */
    public function marketplaceCredentials(): HasMany
    {
        return $this->hasMany(UserMarketplaceCredential::class);
    }

    /**
     * Get the products this user has across all of their marketplace credentials.
     *
     * Products belong to a credential rather than directly to a user, so this
     * resolves them through the user_marketplace_credentials table.
     */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            UserMarketplaceCredential::class,
            'user_id',
            'user_marketplace_credential_id',
        );
    }

    /**
     * Get the orders placed against this user's marketplaces.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the marketplace sync logs across this user's credentials.
     */
    public function syncLogs(): HasManyThrough
    {
        return $this->hasManyThrough(
            MarketplaceSyncLog::class,
            UserMarketplaceCredential::class,
            'user_id',
            'user_marketplace_credential_id',
        );
    }

    /**
     * Get the user settings.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }
}
