<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'price_monthly',
        'price_yearly',
        'marketplaces_limit',
        'orders_limit',
        'products_limit',
        'features',
        'trial_days',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'marketplaces_limit' => 'integer',
            'orders_limit' => 'integer',
            'products_limit' => 'integer',
            'trial_days' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) ($this->features[$feature] ?? false);
    }

    /**
     * Returns the limit for a resource. -1 means unlimited.
     *
     * @param  string  $resource  marketplaces|orders|products
     */
    public function getLimit(string $resource): int
    {
        $column = "{$resource}_limit";

        return (int) ($this->{$column} ?? 0);
    }

    public function isUnlimited(string $resource): bool
    {
        return $this->getLimit($resource) === -1;
    }
}
