<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSettlement extends Model
{
    protected $fillable = [
        'user_id',
        'marketplace_id',
        'marketplace_order_id',
        'transaction_type',
        'transaction_date',
        'payment_date',
        'order_number',
        'package_id',
        'barcode',
        'credit',
        'debt',
        'commission_amount',
        'seller_revenue',
        'store_id',
        'payment_order_id',
        'marketplace_data',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'payment_date' => 'datetime',
            'credit' => 'decimal:2',
            'debt' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'seller_revenue' => 'decimal:2',
            'marketplace_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function marketplaceOrder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id', 'id');
    }
}
