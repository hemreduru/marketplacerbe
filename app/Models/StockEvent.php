<?php

namespace App\Models;

use App\Support\Enums\StockEventSource;
use App\Support\Enums\StockEventType;
use Database\Factories\StockEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only stok hareketi. Tek truth source — master_products.current_stock
 * bunun projeksiyonudur.
 *
 * @property int $id
 * @property string $event_uuid
 * @property int $master_product_id
 * @property int|null $marketplace_listing_id
 * @property StockEventType $event_type
 * @property StockEventSource $source
 * @property string|null $source_reference
 * @property int $quantity_delta
 * @property Carbon $occurred_at
 * @property Carbon|null $processed_at
 */
class StockEvent extends Model
{
    /** @use HasFactory<StockEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_uuid',
        'master_product_id',
        'marketplace_listing_id',
        'event_type',
        'source',
        'source_reference',
        'quantity_delta',
        'occurred_at',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => StockEventType::class,
            'source' => StockEventSource::class,
            'quantity_delta' => 'integer',
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
