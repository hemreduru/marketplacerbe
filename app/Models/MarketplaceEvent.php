<?php

namespace App\Models;

use Database\Factories\MarketplaceEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pazaryerinden gelen webhook olayı. event_uuid UNIQUE ile idempotent.
 *
 * @property int $id
 * @property string $event_uuid
 * @property int|null $user_marketplace_credential_id
 * @property string $marketplace_code
 * @property string $event_type
 * @property string|null $source_reference
 * @property array<string, mixed> $raw_payload
 * @property string $status
 * @property Carbon|null $processed_at
 * @property string|null $processing_error
 */
class MarketplaceEvent extends Model
{
    /** @use HasFactory<MarketplaceEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_uuid',
        'user_marketplace_credential_id',
        'marketplace_code',
        'event_type',
        'source_reference',
        'raw_payload',
        'status',
        'processed_at',
        'processing_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UserMarketplaceCredential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }
}
