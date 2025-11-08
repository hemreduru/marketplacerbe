<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceBrand extends Model
{
    protected $fillable = [
        'marketplace_id',
        'marketplace_brand_id',
        'name',
        'marketplace_raw_data',
    ];

    protected function casts(): array
    {
        return [
            'marketplace_raw_data' => 'array',
        ];
    }

    /**
     * Get the marketplace that owns the brand.
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }
}
