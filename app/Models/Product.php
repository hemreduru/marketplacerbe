<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_marketplace_credential_id',
        'remote_id',
        'barcode',
        'sku',
        'title',
        'brand',
        'category_name',
        'category_id',
        'price',
        'list_price',
        'stock',
        'currency',
        'status',
        'images',
        'attributes',
        'description',
        'product_url',
    ];

    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'price' => 'decimal:2',
        'list_price' => 'decimal:2',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }
}
