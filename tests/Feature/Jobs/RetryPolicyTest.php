<?php

use App\Jobs\SyncTrendyolClaimsJob;
use App\Jobs\SyncTrendyolFinancialsJob;
use App\Jobs\SyncTrendyolOrdersJob;
use App\Jobs\SyncTrendyolProductsJob;
use App\Jobs\SyncTrendyolQuestionsJob;
use Illuminate\Support\Facades\Log;

test('tüm Trendyol sync job\'larında tries=5 ve backoff politikası tanımlı', function (string $jobClass) {
    $job = new $jobClass(1);

    expect($job->tries)->toBe(5)
        ->and($job->backoff)->toBe([30, 120, 600, 3600, 21600]);
})->with([
    SyncTrendyolProductsJob::class,
    SyncTrendyolOrdersJob::class,
    SyncTrendyolClaimsJob::class,
    SyncTrendyolQuestionsJob::class,
    SyncTrendyolFinancialsJob::class,
]);

test('failed() handler log::error çağırır ve job class\'ını/credentialId\'yi yazar', function () {
    Log::spy();

    $job = new SyncTrendyolProductsJob(42);
    $exception = new RuntimeException('Trendyol 503 down');

    $job->failed($exception);

    Log::shouldHaveReceived('error')
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'Sync Job Failed')
                && $context['credentialId'] === 42
                && $context['error'] === 'Trendyol 503 down'
                && $context['exception'] === RuntimeException::class;
        })
        ->once();
});
