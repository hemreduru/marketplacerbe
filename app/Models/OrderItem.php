<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'barcode', 'barcode');
    }

    public function master()
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }
}
