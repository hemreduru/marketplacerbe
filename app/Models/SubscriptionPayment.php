<?php

namespace App\Models;

use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Abonelik ödeme denetim kaydı (iyzico 3DS).
 *
 * @property int $id
 * @property int $user_id
 * @property int $plan_id
 * @property string $billing_period
 * @property string $amount
 * @property string $currency
 * @property string $status
 * @property string $conversation_id
 * @property string|null $payment_id
 * @property string|null $error_message
 * @property Carbon|null $paid_at
 */
class SubscriptionPayment extends Model
{
    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'billing_period',
        'amount',
        'currency',
        'status',
        'conversation_id',
        'payment_id',
        'error_message',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
