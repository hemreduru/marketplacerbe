<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceClaimItem extends Model
{
    /** @use HasFactory<\Database\Factories\MarketplaceClaimItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'marketplace_claim_id',
        'product_id',
        'marketplace_product_id',
        'marketplace_order_item_id',
        'marketplace_item_id',
        'barcode',
        'product_name',
        'product_sku',
        'variant_info',
        'quantity_claimed',
        'quantity_approved',
        'unit_price',
        'total_amount',
        'refund_amount',
        'currency',
        'item_condition',
        'item_condition_note',
        'claim_reason',
        'customer_complaint',
        'resolution',
        'resolution_note',
        'marketplace_raw_data',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_claimed' => 'integer',
            'quantity_approved' => 'integer',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'marketplace_raw_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the claim that owns this item.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(MarketplaceClaim::class, 'marketplace_claim_id');
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the marketplace product.
     */
    public function marketplaceProduct(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class);
    }

    /**
     * Get the related order item.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrderItem::class, 'marketplace_order_item_id');
    }
}
