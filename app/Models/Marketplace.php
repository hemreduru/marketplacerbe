<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Get the marketplace products for this marketplace.
     */
    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    /**
     * Get the sync logs for this marketplace.
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(MarketplaceSyncLog::class);
    }

    /**
     * Get the orders for this marketplace.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class);
    }
}
