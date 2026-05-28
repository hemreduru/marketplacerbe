<?php

namespace App\Models;

use App\Support\Enums\PriceEventType;
use App\Support\Enums\StockEventSource;
use Database\Factories\PriceEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only fiyat hareketi. Tek truth source — master_products.current_price
 * bunun projeksiyonudur.
 *
 * @property int $id
 * @property string $event_uuid
 * @property int $master_product_id
 * @property int|null $marketplace_listing_id
 * @property PriceEventType $event_type
 * @property StockEventSource $source
 * @property string|null $source_reference
 * @property string $new_price
 * @property string|null $previous_price
 * @property Carbon $occurred_at
 * @property Carbon|null $processed_at
 */
class PriceEvent extends Model
{
    /** @use HasFactory<PriceEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_uuid',
        'master_product_id',
        'marketplace_listing_id',
        'event_type',
        'source',
        'source_reference',
        'new_price',
        'previous_price',
        'occurred_at',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => PriceEventType::class,
            'source' => StockEventSource::class,
            'new_price' => 'decimal:4',
            'previous_price' => 'decimal:4',
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MasterProduct, $this>
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    /**
     * @return BelongsTo<MarketplaceListing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'marketplace_listing_id');
    }
}
