<?php

namespace App\Jobs;

use App\Models\MarketplaceSyncLog;
use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTrendyolProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $credentialId;

    /**
     * Create a new job instance.
     */
    public function __construct($credentialId)
    {
        $this->credentialId = $credentialId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $credential = UserMarketplaceCredential::find($this->credentialId);

        if (! $credential) {
            return;
        }

        $log = MarketplaceSyncLog::start($credential->id, 'product');
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        try {
            $service = new TrendyolProductService(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                false
            );

            $service->syncProducts($credential->id, function ($processed, $total, $message, $progressStats) use (&$stats) {
                $stats = $progressStats;
            });

            $credential->update(['last_sync_at' => now()]);
            $log->succeed($stats);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            Log::error('Trendyol Product Sync Failed: '.$e->getMessage());
        }
    }
}
