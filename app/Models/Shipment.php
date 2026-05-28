<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'cargo_provider_id',
        'cargo_credential_id',
        'tracking_number',
        'label_url',
        'label_format',
        'status',
        'package_count',
        'total_weight_kg',
        'total_desi',
        'sender_address',
        'receiver_address',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'package_count' => 'integer',
            'total_weight_kg' => 'decimal:3',
            'total_desi' => 'decimal:3',
            'sender_address' => 'array',
            'receiver_address' => 'array',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cargoProvider(): BelongsTo
    {
        return $this->belongsTo(CargoProvider::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['delivered', 'cancelled', 'returned', 'failed']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
