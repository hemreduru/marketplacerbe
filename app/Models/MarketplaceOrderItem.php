<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketplace_order_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'marketplace_order_id',
        'product_id',
        'marketplace_product_id',
        'marketplace_item_id',
        'marketplace_sku',
        'barcode',
        'product_name',
        'product_description',
        'product_color',
        'product_size',
        'quantity',
        'unit_price',
        'total_price',
        'discount',
        'vat_amount',
        'vat_rate',
        'currency',
        'commission_amount',
        'commission_rate',
        'item_status',
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
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'vat_rate' => 'integer',
            'commission_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'marketplace_data' => 'array',
        ];
    }

    /**
     * Get the order that this item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    /**
     * Get the product associated with this order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the marketplace product associated with this order item.
     */
    public function marketplaceProduct(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class);
    }
}
