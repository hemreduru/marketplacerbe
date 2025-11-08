<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAdditionalExpense extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'marketplace_id',
        'expense_type',
        'title',
        'description',
        'amount',
        'currency',
        'expense_date',
        'allocation_type',
        'receipt_number',
        'attachments',
        'metadata',
        'is_recurring',
        'recurrence_period',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'attachments' => 'array',
            'metadata' => 'array',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Scope to get expenses for a specific product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId)
            ->where('is_active', true);
    }

    /**
     * Scope to get expenses for a specific marketplace.
     */
    public function scopeForMarketplace($query, int $marketplaceId)
    {
        return $query->where('marketplace_id', $marketplaceId)
            ->where('is_active', true);
    }

    /**
     * Scope to get global expenses (allocated to all products).
     */
    public function scopeGlobal($query)
    {
        return $query->where('allocation_type', 'global')
            ->where('is_active', true);
    }

    /**
     * Get expense types as array.
     */
    public static function getExpenseTypes(): array
    {
        return [
            'packaging' => 'Ambalaj',
            'advertising' => 'Reklam',
            'storage' => 'Depolama',
            'shipping_material' => 'Kargo Malzemesi',
            'extra_service' => 'Ekstra Hizmet',
            'other' => 'Diğer',
        ];
    }
}
