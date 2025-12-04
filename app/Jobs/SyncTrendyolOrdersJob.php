<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncTrendyolOrdersJob implements ShouldQueue
{
    use Queueable;

    protected $credentialId;

    public function __construct($credentialId = null)
    {
        $this->credentialId = $credentialId;
    }

    public function handle(): void
    {
        $query = \App\Models\UserMarketplaceCredential::whereHas('marketplace', function ($q) {
            $q->where('slug', 'trendyol');
        });

        if ($this->credentialId) {
            $query->where('id', $this->credentialId);
        }

        $credentials = $query->get();

        foreach ($credentials as $credential) {
            try {
                $service = new \App\Services\Trendyol\TrendyolOrderService(
                    $credential->api_key,
                    $credential->api_secret,
                    $credential->additional_credentials['seller_id'] ?? '',
                    false
                );

                $stats = $service->syncOrders($credential->marketplace_id, $credential->user_id);
                \Illuminate\Support\Facades\Log::info("Synced orders for credential {$credential->id}: " . json_encode($stats));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to sync orders for credential {$credential->id}: " . $e->getMessage());
            }
        }
    }
}
