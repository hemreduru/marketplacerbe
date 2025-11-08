<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceClaim extends Model
{
    /** @use HasFactory<\Database\Factories\MarketplaceClaimFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'marketplace_id',
        'marketplace_order_id',
        'marketplace_claim_id',
        'marketplace_order_id_value',
        'package_number',
        'claim_type',
        'claim_status',
        'claim_reason',
        'customer_note',
        'seller_note',
        'customer_name',
        'customer_email',
        'customer_phone',
        'claim_amount',
        'approved_amount',
        'currency',
        'return_tracking_number',
        'return_carrier',
        'return_shipped_at',
        'return_received_at',
        'claim_date',
        'approved_at',
        'rejected_at',
        'completed_at',
        'marketplace_raw_data',
        'internal_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claim_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'return_shipped_at' => 'datetime',
            'return_received_at' => 'datetime',
            'claim_date' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
            'marketplace_raw_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the claim.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the marketplace for this claim.
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Get the related order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    /**
     * Get the claim items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceClaimItem::class);
    }
}
