<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserMarketplaceCredential extends Model
{
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::creating(function (UserMarketplaceCredential $credential) {
            if ($credential->webhook_uuid === null) {
                $credential->webhook_uuid = (string) Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Sırlar loglanmasın: api_key/api_secret/additional_credentials atlanır
            ->logOnly(['marketplace_id', 'is_active', 'last_sync_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('marketplace_credential');
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_marketplace_credentials';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'marketplace_id',
        'webhook_uuid',
        'api_key',
        'api_secret',
        'additional_credentials',
        'is_active',
        'last_sync_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'api_key',
        'api_secret',
        'additional_credentials',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'additional_credentials' => 'array',
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this credential.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the marketplace that this credential belongs to.
     *
     * @return BelongsTo<Marketplace, $this>
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Get the sync logs recorded for this credential.
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(MarketplaceSyncLog::class);
    }

    /**
     * Get the customer questions stored for this credential.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the return claims stored for this credential.
     */
    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }
}
