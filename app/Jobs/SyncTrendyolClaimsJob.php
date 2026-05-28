<?php

namespace App\Jobs;

use App\Jobs\Concerns\HasRetryPolicy;
use App\Models\MarketplaceSyncLog;
use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolClaimService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTrendyolClaimsJob implements ShouldQueue
{
    use Dispatchable, HasRetryPolicy, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $credentialId) {}

    public function handle(): void
    {
        $credential = UserMarketplaceCredential::find($this->credentialId);

        if (! $credential) {
            return;
        }

        $log = MarketplaceSyncLog::start($credential->id, 'claim');

        try {
            $service = new TrendyolClaimService(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                false
            );

            $stats = $service->syncClaims($credential->id);

            $credential->update(['last_sync_at' => now()]);
            $log->succeed($stats);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            Log::error('Trendyol Claim Sync Failed: '.$e->getMessage());
        }
    }
}
