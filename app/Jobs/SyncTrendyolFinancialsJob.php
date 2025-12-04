<?php

namespace App\Jobs;

use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolFinanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncTrendyolFinancialsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $credentialId;
    protected $startDate;
    protected $endDate;

    /**
     * Create a new job instance.
     */
    public function __construct(int $credentialId, ?string $startDate = null, ?string $endDate = null)
    {
        $this->credentialId = $credentialId;
        $this->startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $this->endDate = $endDate ?? date('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting financial sync for credential ID: {$this->credentialId}");

        try {
            $credential = UserMarketplaceCredential::find($this->credentialId);
            if (!$credential) {
                Log::error("Credential not found: {$this->credentialId}");
                return;
            }

            $service = new TrendyolFinanceService(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                false
            );

            $service->syncSmart($this->credentialId);

            Log::info("Financial sync completed for credential ID: {$this->credentialId}");

        } catch (\Exception $e) {
            Log::error("Financial sync failed: " . $e->getMessage());
            throw $e;
        }
    }
}
