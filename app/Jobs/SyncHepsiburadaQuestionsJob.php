<?php

namespace App\Jobs;

use App\Jobs\Concerns\HasRetryPolicy;
use App\Models\MarketplaceSyncLog;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Hepsiburada\Client;
use App\Services\Marketplaces\Hepsiburada\QuestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncHepsiburadaQuestionsJob implements ShouldQueue
{
    use Dispatchable, HasRetryPolicy, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $credentialId) {}

    public function handle(): void
    {
        $credential = UserMarketplaceCredential::find($this->credentialId);

        if (! $credential) {
            Log::error("HB question sync: credential bulunamadı {$this->credentialId}");

            return;
        }

        $log = MarketplaceSyncLog::start($credential->id, 'questions');

        $service = new QuestionService(new Client(
            $credential->api_key,
            $credential->api_secret,
            $credential->additional_credentials['seller_id'] ?? '',
        ));

        try {
            $service->syncQuestions($this->credentialId);

            $credential->update(['last_sync_at' => now()]);
            $log->succeed();
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            throw $e;
        }
    }
}
