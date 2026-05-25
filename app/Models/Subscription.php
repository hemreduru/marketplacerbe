<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'billing_period',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function onTrial(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_ends_at !== null
            && now()->lt($this->trial_ends_at);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && now()->lt($this->current_period_end);
    }

    /** 3-day grace period after expiry. */
    public function onGracePeriod(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return now()->gte($this->current_period_end)
            && now()->lt($this->current_period_end->addDays(3));
    }

    public function valid(): bool
    {
        return $this->onTrial() || $this->isActive() || $this->onGracePeriod();
    }

    public function canceled(): bool
    {
        return $this->status === 'canceled';
    }
}
