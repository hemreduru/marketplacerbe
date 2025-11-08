<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOtherFinancial extends Model
{
    protected $fillable = [
        'user_id',
        'marketplace_id',
        'transaction_type',
        'transaction_date',
        'receipt_date',
        'order_number',
        'description',
        'credit',
        'debt',
        'invoice_serial_number',
        'marketplace_data',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'receipt_date' => 'datetime',
            'credit' => 'decimal:2',
            'debt' => 'decimal:2',
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

    /**
     * Check if this is a cargo invoice record
     */
    public function isCargoInvoice(): bool
    {
        return str_contains(strtolower($this->description ?? ''), 'kargo fatura');
    }

    /**
     * Check if this is a platform service fee
     */
    public function isPlatformFee(): bool
    {
        $desc = strtolower($this->description ?? '');
        return str_contains($desc, 'platform hizmet') || str_contains($desc, 'p.h.b');
    }
}
