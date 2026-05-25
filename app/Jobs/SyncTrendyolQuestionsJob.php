<?php

namespace App\Jobs;

use App\Models\MarketplaceSyncLog;
use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolQuestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTrendyolQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $credentialId) {}

    public function handle(): void
    {
        $credential = UserMarketplaceCredential::find($this->credentialId);

        if (! $credential) {
            return;
        }

        $log = MarketplaceSyncLog::start($credential->id, 'question');

        try {
            $service = new TrendyolQuestionService(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                false
            );

            $stats = $service->syncQuestions($credential->id);

            $credential->update(['last_sync_at' => now()]);
            $log->succeed($stats);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            Log::error('Trendyol Question Sync Failed: '.$e->getMessage());
        }
    }
}
