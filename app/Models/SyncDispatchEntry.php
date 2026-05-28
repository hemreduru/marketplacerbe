<?php

namespace App\Models;

use Database\Factories\SyncDispatchEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pazaryerine giden mutation kuyruğu.
 *
 * @property int $id
 * @property int $master_product_id
 * @property int $marketplace_listing_id
 * @property string $mutation_type
 * @property array<string, mixed> $payload_json
 * @property string $status
 * @property int $attempt_count
 * @property Carbon|null $last_attempt_at
 * @property string|null $last_error
 * @property Carbon|null $next_attempt_at
 */
class SyncDispatchEntry extends Model
{
    /** @use HasFactory<SyncDispatchEntryFactory> */
    use HasFactory;

    protected $table = 'sync_dispatch_queue';

    protected $fillable = [
        'master_product_id',
        'marketplace_listing_id',
        'mutation_type',
        'payload_json',
        'status',
        'attempt_count',
        'last_attempt_at',
        'last_error',
        'next_attempt_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'attempt_count' => 'integer',
            'last_attempt_at' => 'datetime',
            'next_attempt_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MasterProduct, $this>
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    /**
     * @return BelongsTo<MarketplaceListing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'marketplace_listing_id');
    }
}
