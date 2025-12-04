<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_marketplace_credential_id',
        'transaction_type',
        'transaction_date',
        'order_number',
        'amount',
        'commission',
        'description',
        'metadata',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'commission' => 'decimal:2',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }
}
