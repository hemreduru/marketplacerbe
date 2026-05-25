<?php

namespace App\Models;

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

    protected $fillable = [
        'name',
        'username',
        'email',
        'avatar',
        'password',
        'settings_id',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function marketplaceCredentials(): HasMany
    {
        return $this->hasMany(UserMarketplaceCredential::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            UserMarketplaceCredential::class,
            'user_id',
            'user_marketplace_credential_id',
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function syncLogs(): HasManyThrough
    {
        return $this->hasManyThrough(
            MarketplaceSyncLog::class,
            UserMarketplaceCredential::class,
            'user_id',
            'user_marketplace_credential_id',
        );
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    /**
     * Returns the most recent subscription that is currently valid (trial, active, or grace period).
     * The related plan is eager-loaded to avoid N+1 queries.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->with('plan')
            ->get()
            ->first(fn (Subscription $s) => $s->valid());
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function canUseFeature(string $feature): bool
    {
        $subscription = $this->activeSubscription();

        if (! $subscription) {
            return false;
        }

        return $subscription->plan->hasFeature($feature);
    }

    /**
     * Returns the resource limit from the active plan. -1 means unlimited.
     *
     * @param  string  $resource  marketplaces|orders|products
     */
    public function getSubscriptionLimit(string $resource): int
    {
        $subscription = $this->activeSubscription();

        if (! $subscription) {
            return 0;
        }

        return $subscription->plan->getLimit($resource);
    }

    public function hasUsedTrial(): bool
    {
        return $this->subscriptions()->whereNotNull('trial_ends_at')->exists();
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
