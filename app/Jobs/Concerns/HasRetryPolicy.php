<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync job'lar için ortak retry politikası.
 *
 * - 5 deneme; backoff: 30s, 2dk, 10dk, 1sa, 6sa
 * - Son deneme de başarısız olursa failed() çağrılır; log + (Faz 2'de) NotificationService::syncFailure
 */
trait HasRetryPolicy
{
    /** @var int */
    public $tries = 5;

    /** @var array<int, int> */
    public $backoff = [30, 120, 600, 3600, 21600];

    public function failed(Throwable $e): void
    {
        Log::error('[Sync Job Failed] '.static::class, [
            'credentialId' => $this->credentialId ?? null,
            'error' => $e->getMessage(),
            'exception' => get_class($e),
        ]);

        // PR #2.7 — NotificationService::syncFailure($this->credentialId, $e)
    }
}
