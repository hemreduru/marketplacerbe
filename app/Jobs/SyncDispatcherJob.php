<?php

namespace App\Jobs;

use App\Jobs\Concerns\HasRetryPolicy;
use App\Models\SyncDispatchEntry;
use App\Models\UserMarketplaceCredential;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tek bir SyncDispatchEntry'yi pazaryerine yansıt. Write-guard ile
 * (env MARKETPLACE_WRITE_ENABLED + credential.write_enabled) korumalı.
 * Başarısızlıkta exponential backoff ile yeniden dener; 5. denemede
 * status=failed.
 */
class SyncDispatcherJob implements ShouldQueue
{
    use Dispatchable, HasRetryPolicy, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $dispatchEntryId) {}

    public function handle(): void
    {
        /** @var SyncDispatchEntry|null $entry */
        $entry = SyncDispatchEntry::query()->find($this->dispatchEntryId);

        if ($entry === null || $entry->status !== 'pending') {
            return;
        }

        $listing = $entry->listing()->with('credential')->first();
        $credential = $listing?->credential;

        if (! $this->isWriteAllowed($credential)) {
            $entry->update([
                'status' => 'skipped',
                'last_error' => 'write_disabled',
                'last_attempt_at' => now(),
            ]);

            Log::info('sync dispatch skipped (write disabled)', [
                'entry_id' => $entry->id,
                'credential_id' => $credential?->id,
            ]);

            return;
        }

        $entry->increment('attempt_count');
        $entry->update(['last_attempt_at' => now()]);

        try {
            // Gerçek pazaryeri push burada — PR #1.11/1.12/1.13'te marketplace bazında
            // ayrı write service çağırılacak. Şimdilik no-op + success işaretle.
            $entry->update(['status' => 'sent']);
        } catch (Throwable $e) {
            $entry->update([
                'last_error' => $e->getMessage(),
                'next_attempt_at' => now()->addSeconds($this->backoffSecondsForAttempt($entry->attempt_count)),
                'status' => $entry->attempt_count >= 5 ? 'failed' : 'pending',
            ]);

            throw $e;
        }
    }

    private function isWriteAllowed(?UserMarketplaceCredential $credential): bool
    {
        if (! (bool) config('marketplace.write_enabled', false)) {
            return false;
        }

        if ($credential === null) {
            return false;
        }

        // Per-credential write_enabled kolonu Faz 0 öncesi modelde yoktu.
        // additional_credentials JSON'da `write_enabled => true` kontrolüne fallback.
        $extra = $credential->additional_credentials ?? [];

        return (bool) ($extra['write_enabled'] ?? false);
    }

    private function backoffSecondsForAttempt(int $attempt): int
    {
        $schedule = [30, 120, 600, 3600, 21600];

        return $schedule[min($attempt, count($schedule)) - 1] ?? 21600;
    }
}
