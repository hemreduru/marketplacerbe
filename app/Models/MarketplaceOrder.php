<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceOrder extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketplace_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'marketplace_id',
        'marketplace_order_id',
        'marketplace_order_number',
        'package_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'order_status',
        'shipment_status',
        'total_price',
        'gross_amount',
        'discount_amount',
        'tax_amount',
        'currency',
        'shipping_company',
        'tracking_number',
        'shipping_address',
        'shipping_city',
        'shipping_district',
        'shipping_postal_code',
        'billing_address',
        'billing_city',
        'billing_district',
        'billing_postal_code',
        'invoice_number',
        'invoice_link',
        'invoiced_at',
        'order_date',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'last_sync_at',
        'marketplace_data',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'invoiced_at' => 'datetime',
            'order_date' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'marketplace_data' => 'array',
            'notes' => 'array',
        ];
    }

    /**
     * Get the user that owns this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the marketplace that this order belongs to.
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Get the order items for this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }
}
