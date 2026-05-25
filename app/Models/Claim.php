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
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'datetime',
            'item_count' => 'integer',
            'raw_data' => 'array',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }
}
