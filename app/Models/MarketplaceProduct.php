<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProduct extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketplace_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'marketplace_id',
        'user_id',
        'marketplace_product_id',
        'marketplace_sku',
        'stock_code',
        'product_code',
        'product_main_id',
        'platform_listing_id',
        'stock_id',
        'batch_request_id',
        'approved',
        'marketplace_status',
        'marketplace_url',
        'gender',
        'color',
        'stock_unit_type',
        'location_based_delivery',
        'lot_number',
        'delivery_option',
        'variant_attributes',
        'last_sync_at',
        'sync_errors',
        'marketplace_data',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
            'location_based_delivery' => 'boolean',
            'delivery_option' => 'array',
            'variant_attributes' => 'array',
            'last_sync_at' => 'datetime',
            'sync_errors' => 'array',
            'marketplace_data' => 'array',
        ];
    }

    /**
     * Get the product that this marketplace product belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the marketplace that this product is listed on.
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Get the user that owns this marketplace product.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the marketplace credential used for this product.
     * Note: This assumes the user has only one credential per marketplace.
     * For multiple credentials per marketplace, you'd need to add a credential_id column.
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'marketplace_id', 'marketplace_id')
            ->where('user_id', $this->user_id);
    }
}
