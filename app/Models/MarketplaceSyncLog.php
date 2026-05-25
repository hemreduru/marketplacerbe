<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_marketplace_credential_id',
        'entity_type',
        'status',
        'created_count',
        'updated_count',
        'failed_count',
        'message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'failed_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }

    /**
     * Open a new running sync log for a credential and entity type.
     */
    public static function start(int $credentialId, string $entityType): self
    {
        return static::create([
            'user_marketplace_credential_id' => $credentialId,
            'entity_type' => $entityType,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark this sync run as successful, recording any available counts.
     *
     * @param  array{created?: int, updated?: int, failed?: int}  $stats
     */
    public function succeed(array $stats = []): void
    {
        $this->update([
            'status' => 'success',
            'created_count' => $stats['created'] ?? 0,
            'updated_count' => $stats['updated'] ?? 0,
            'failed_count' => $stats['failed'] ?? 0,
            'finished_at' => now(),
        ]);
    }

    /**
     * Mark this sync run as failed, storing a truncated error message.
     */
    public function fail(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'message' => mb_substr($message, 0, 1000),
            'finished_at' => now(),
        ]);
    }
}
