<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialDailySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_marketplace_credential_id',
        'date',
        'gross_sales',
        'commission',
        'shipping_cost',
        'platform_expense',
        'other_expense',
        'net_profit',
        'order_count',
        'item_count',
    ];

    protected $casts = [
        'date' => 'date',
        'gross_sales' => 'decimal:2',
        'commission' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'platform_expense' => 'decimal:2',
        'other_expense' => 'decimal:2',
        'net_profit' => 'decimal:2',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }
}
