<?php

namespace App\Models;

use Database\Factories\MarketplaceListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Bir master ürünün bir pazaryerindeki listelenme kaydı.
 *
 * @property int $id
 * @property int|null $master_product_id
 * @property int $user_marketplace_credential_id
 * @property string|null $remote_product_id
 * @property string|null $remote_sku
 * @property string|null $remote_barcode
 * @property string $listing_status
 * @property string $listed_price
 * @property int $listed_stock
 * @property string|null $listing_url
 * @property string|null $category_path
 * @property array<string, mixed>|null $attributes_json
 * @property Carbon|null $last_synced_at
 * @property string $sync_status
 * @property string|null $last_sync_error
 */
class MarketplaceListing extends Model
{
    /** @use HasFactory<MarketplaceListingFactory> */
    use HasFactory;

    protected $fillable = [
        'master_product_id',
        'user_marketplace_credential_id',
        'remote_product_id',
        'remote_sku',
        'remote_barcode',
        'listing_status',
        'listed_price',
        'listed_stock',
        'listing_url',
        'category_path',
        'attributes_json',
        'last_synced_at',
        'sync_status',
        'last_sync_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'listed_price' => 'decimal:4',
            'listed_stock' => 'integer',
            'attributes_json' => 'array',
            'last_synced_at' => 'datetime',
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
     * @return BelongsTo<UserMarketplaceCredential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }
}
