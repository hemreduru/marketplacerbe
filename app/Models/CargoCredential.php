<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CargoCredential extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'cargo_credentials';

    protected $fillable = [
        'user_id',
        'cargo_provider_id',
        'username',
        'password',
        'customer_code',
        'is_active',
        'ip_whitelisted_at',
        'additional_config',
    ];

    protected $hidden = [
        'username',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'username' => 'encrypted',
            'password' => 'encrypted',
            'is_active' => 'boolean',
            'ip_whitelisted_at' => 'datetime',
            'additional_config' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['cargo_provider_id', 'is_active', 'ip_whitelisted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cargo_credential');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cargoProvider(): BelongsTo
    {
        return $this->belongsTo(CargoProvider::class);
    }

    public function isWhitelisted(): bool
    {
        return $this->ip_whitelisted_at !== null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
