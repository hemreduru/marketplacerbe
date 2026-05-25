<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Marketplace extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'api_base_url',
        'logo',
        'is_active',
        'config',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    /**
     * Get the user credentials for this marketplace.
     */
    public function userCredentials(): HasMany
    {
        return $this->hasMany(UserMarketplaceCredential::class);
    }

    /**
     * Get the orders for this marketplace.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the sync logs recorded across this marketplace's credentials.
     */
    public function syncLogs(): HasManyThrough
    {
        return $this->hasManyThrough(
            MarketplaceSyncLog::class,
            UserMarketplaceCredential::class,
            'marketplace_id',
            'user_marketplace_credential_id',
        );
    }
}
