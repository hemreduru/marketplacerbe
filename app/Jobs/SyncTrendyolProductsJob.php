<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolProductService;

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

        if (!$credential) {
            return;
        }

        try {
            $service = new TrendyolProductService(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                false
            );

            $service->syncProducts($credential->id);
        } catch (\Exception $e) {
            Log::error('Trendyol Product Sync Failed: ' . $e->getMessage());
        }
    }
}
