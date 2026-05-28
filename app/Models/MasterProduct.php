<?php

namespace App\Models;

use Database\Factories\MasterProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cirotik'in fiziksel ürün modeli. Birden çok pazaryerinde
 * MarketplaceListing olarak görünür; stok ve fiyat StockEvent / PriceEvent
 * akışından projeksiyonla yürür.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $brand
 * @property string|null $sku
 * @property string|null $barcode
 * @property string $cost_price
 * @property string $cost_price_vat_rate
 * @property string $vat_rate
 * @property int $weight_g
 * @property string $desi
 * @property string $packaging_cost
 * @property int $current_stock
 * @property string $current_price
 * @property string $pricing_strategy
 * @property string $stock_buffer_strategy
 * @property int $stock_buffer_value
 * @property int $version
 * @property array<string, mixed>|null $marketplace_specific_attributes
 */
class MasterProduct extends Model
{
    /** @use HasFactory<MasterProductFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'brand',
        'sku',
        'barcode',
        'cost_price',
        'cost_price_vat_rate',
        'vat_rate',
        'weight_g',
        'desi',
        'packaging_cost',
        'current_stock',
        'current_price',
        'pricing_strategy',
        'stock_buffer_strategy',
        'stock_buffer_value',
        'version',
        'marketplace_specific_attributes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'cost_price_vat_rate' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'desi' => 'decimal:2',
            'packaging_cost' => 'decimal:4',
            'current_price' => 'decimal:4',
            'current_stock' => 'integer',
            'weight_g' => 'integer',
            'stock_buffer_value' => 'integer',
            'version' => 'integer',
            'marketplace_specific_attributes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MarketplaceListing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class);
    }

    /**
     * @return HasMany<StockEvent, $this>
     */
    public function stockEvents(): HasMany
    {
        return $this->hasMany(StockEvent::class);
    }

    /**
     * @return HasMany<PriceEvent, $this>
     */
    public function priceEvents(): HasMany
    {
        return $this->hasMany(PriceEvent::class);
    }
}
