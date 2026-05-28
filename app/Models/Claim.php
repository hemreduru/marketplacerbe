<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_marketplace_credential_id',
        'remote_id',
        'order_number',
        'status',
        'customer_name',
        'item_count',
        'claim_date',
        'raw_data',
        'return_reason',
        'return_tracking_number',
        'return_carrier',
        'refund_amount',
        'approved_at',
        'restock',
        'restocked_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'datetime',
            'approved_at' => 'datetime',
            'restocked_at' => 'datetime',
            'item_count' => 'integer',
            'refund_amount' => 'decimal:4',
            'restock' => 'boolean',
            'raw_data' => 'array',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }
}
