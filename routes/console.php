<?php

use App\Jobs\SyncTrendyolClaimsJob;
use App\Jobs\SyncTrendyolFinancialsJob;
use App\Jobs\SyncTrendyolOrdersJob;
use App\Jobs\SyncTrendyolProductsJob;
use App\Jobs\SyncTrendyolQuestionsJob;
use App\Models\UserMarketplaceCredential;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Marketplace Sync Schedule
|--------------------------------------------------------------------------
|
| Dispatches the per-credential Trendyol sync jobs onto the "sync" queue.
| Orders are dispatched as a single job that iterates every active Trendyol
| credential itself; products and financials are dispatched per credential.
|
*/

$activeTrendyolCredentials = static fn () => UserMarketplaceCredential::query()
    ->where('is_active', true)
    ->whereHas('marketplace', fn ($q) => $q->where('slug', 'trendyol'))
    ->pluck('id');

Schedule::call(function () use ($activeTrendyolCredentials) {
    foreach ($activeTrendyolCredentials() as $credentialId) {
        SyncTrendyolProductsJob::dispatch($credentialId)->onQueue('sync');
    }
})->everySixHours()->name('sync-trendyol-products')->withoutOverlapping();

Schedule::call(function () {
    SyncTrendyolOrdersJob::dispatch()->onQueue('sync');
})->everyThirtyMinutes()->name('sync-trendyol-orders')->withoutOverlapping();

Schedule::call(function () use ($activeTrendyolCredentials) {
    foreach ($activeTrendyolCredentials() as $credentialId) {
        SyncTrendyolQuestionsJob::dispatch($credentialId)->onQueue('sync');
    }
})->hourly()->name('sync-trendyol-questions')->withoutOverlapping();

Schedule::call(function () use ($activeTrendyolCredentials) {
    foreach ($activeTrendyolCredentials() as $credentialId) {
        SyncTrendyolClaimsJob::dispatch($credentialId)->onQueue('sync');
    }
})->everySixHours()->name('sync-trendyol-claims')->withoutOverlapping();

Schedule::call(function () use ($activeTrendyolCredentials) {
    foreach ($activeTrendyolCredentials() as $credentialId) {
        SyncTrendyolFinancialsJob::dispatch($credentialId)->onQueue('sync');
    }
})->dailyAt('03:00')->name('sync-trendyol-financials')->withoutOverlapping();
