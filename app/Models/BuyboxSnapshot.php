<?php

namespace App\Models;

use Database\Factories\BuyboxSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Bir listing'in belirli bir andaki buybox durumu (zaman serisi).
 *
 * @property int $id
 * @property int $marketplace_listing_id
 * @property bool $has_buybox
 * @property string $our_price
 * @property string|null $competitor_price
 * @property string|null $competitor_seller
 * @property Carbon $checked_at
 */
class BuyboxSnapshot extends Model
{
    /** @use HasFactory<BuyboxSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'marketplace_listing_id',
        'has_buybox',
        'our_price',
        'competitor_price',
        'competitor_seller',
        'checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_buybox' => 'boolean',
            'our_price' => 'decimal:4',
            'competitor_price' => 'decimal:4',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MarketplaceListing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'marketplace_listing_id');
    }
}
