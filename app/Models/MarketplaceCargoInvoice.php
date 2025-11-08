<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceCargoInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'marketplace_id',
        'invoice_serial_number',
        'invoice_date',
        'total_amount',
        'status',
        'marketplace_data',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'total_amount' => 'decimal:2',
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

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceCargoInvoiceItem::class, 'cargo_invoice_id');
    }
}
