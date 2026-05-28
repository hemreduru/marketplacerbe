<?php

namespace App\Jobs;

use App\Jobs\Concerns\HasRetryPolicy;
use App\Models\MarketplaceSyncLog;
use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolFinanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTrendyolFinancialsJob implements ShouldQueue
{
    use Dispatchable, HasRetryPolicy, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * The finance service performs a smart incremental sync that determines
     * its own date window from the latest stored transaction, so no explicit
     * date range is needed here.
     */
    public function __construct(protected int $credentialId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting financial sync for credential ID: {$this->credentialId}");

        try {
            $credential = UserMarketplaceCredential::find($this->credentialId);
            if (! $credential) {
                Log::error("Credential not found: {$this->credentialId}");

                return;
            }

            $log = MarketplaceSyncLog::start($credential->id, 'finance');

            $service = new TrendyolFinanceService(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                false
            );

            try {
                $service->syncSmart($this->credentialId);

                $credential->update(['last_sync_at' => now()]);
                $log->succeed();

                Log::info("Financial sync completed for credential ID: {$this->credentialId}");
            } catch (\Exception $e) {
                $log->fail($e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Financial sync failed: '.$e->getMessage());
            throw $e;
        }
    }
}
