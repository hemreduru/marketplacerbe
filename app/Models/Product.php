<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'sku',
        'name',
        'description',
        'brand',
        'barcode',
        'stock_quantity',
        'base_price',
        'sale_price',
        'vat_rate',
        'currency',
        'weight',
        'dimensional_weight',
        'images',
        'attributes',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
            'base_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'weight' => 'decimal:2',
            'dimensional_weight' => 'decimal:2',
            'images' => 'array',
            'attributes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns this product.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the marketplace products for this product.
     */
    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(MarketplaceProduct::class);
    }
}
