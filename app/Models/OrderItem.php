<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasOne<Product, $this>
     */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'barcode', 'barcode');
    }

    /**
     * @return BelongsTo<MasterProduct, $this>
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }
}
