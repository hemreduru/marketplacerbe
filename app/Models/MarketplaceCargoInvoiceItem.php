<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceCargoInvoiceItem extends Model
{
    protected $fillable = [
        'cargo_invoice_id',
        'order_number',
        'amount',
        'description',
        'marketplace_data',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'marketplace_data' => 'array',
        ];
    }

    public function cargoInvoice(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCargoInvoice::class, 'cargo_invoice_id');
    }

    public function marketplaceOrder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'order_number', 'order_number');
    }
}
