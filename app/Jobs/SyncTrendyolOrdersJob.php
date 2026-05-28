<?php

namespace App\Jobs;

use App\Jobs\Concerns\HasRetryPolicy;
use App\Models\MarketplaceSyncLog;
use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncTrendyolOrdersJob implements ShouldQueue
{
    use HasRetryPolicy, Queueable;

    protected $credentialId;

    public function __construct($credentialId = null)
    {
        $this->credentialId = $credentialId;
    }

    public function handle(): void
    {
        $query = UserMarketplaceCredential::whereHas('marketplace', function ($q) {
            $q->where('slug', 'trendyol');
        });

        if ($this->credentialId) {
            $query->where('id', $this->credentialId);
        }

        $credentials = $query->get();

        foreach ($credentials as $credential) {
            $log = MarketplaceSyncLog::start($credential->id, 'order');

            try {
                $service = new TrendyolOrderService(
                    $credential->api_key,
                    $credential->api_secret,
                    $credential->additional_credentials['seller_id'] ?? '',
                    false
                );

                $stats = $service->syncOrders($credential->marketplace_id, $credential->user_id);
                $credential->update(['last_sync_at' => now()]);
                $log->succeed($stats);
                Log::info("Synced orders for credential {$credential->id}: ".json_encode($stats));
            } catch (\Exception $e) {
                $log->fail($e->getMessage());
                Log::error("Failed to sync orders for credential {$credential->id}: ".$e->getMessage());
            }
        }
    }
}
