<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EInvoice extends Model
{
    use HasFactory;

    protected $table = 'e_invoices';

    protected $fillable = [
        'user_id',
        'order_id',
        'provider',
        'invoice_uuid',
        'e_invoice_number',
        'e_archive_number',
        'status',
        'subtotal',
        'total_vat',
        'total_amount',
        'pdf_url',
        'raw_response',
        'issued_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'total_vat' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'raw_response' => 'array',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
