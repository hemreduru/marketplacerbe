<?php

use App\Jobs\SendDigestMail;
use App\Jobs\SyncCargoTrackingJob;
use App\Jobs\SyncHepsiburadaClaimsJob;
use App\Jobs\SyncHepsiburadaFinancialsJob;
use App\Jobs\SyncHepsiburadaOrdersJob;
use App\Jobs\SyncHepsiburadaProductsJob;
use App\Jobs\SyncHepsiburadaQuestionsJob;
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

/*
|--------------------------------------------------------------------------
| Hepsiburada Sync Schedule
|--------------------------------------------------------------------------
|
| Trendyol ile aynı kadans: ürünler 6 saatte bir, siparişler 30 dakikada
| bir, sorular saatlik, iadeler 6 saatte bir, finansallar gecelik.
| HB webhook'ları anlık akışı sağlar; polling güvenlik ağıdır.
|
*/

$activeHepsiburadaCredentials = static fn () => UserMarketplaceCredential::query()
    ->where('is_active', true)
    ->whereHas('marketplace', fn ($q) => $q->where('slug', 'hepsiburada'))
    ->pluck('id');

Schedule::call(function () use ($activeHepsiburadaCredentials) {
    foreach ($activeHepsiburadaCredentials() as $credentialId) {
        SyncHepsiburadaProductsJob::dispatch($credentialId)->onQueue('sync');
    }
})->everySixHours()->name('sync-hepsiburada-products')->withoutOverlapping();

Schedule::call(function () use ($activeHepsiburadaCredentials) {
    foreach ($activeHepsiburadaCredentials() as $credentialId) {
        SyncHepsiburadaOrdersJob::dispatch($credentialId)->onQueue('sync');
    }
})->everyThirtyMinutes()->name('sync-hepsiburada-orders')->withoutOverlapping();

Schedule::call(function () use ($activeHepsiburadaCredentials) {
    foreach ($activeHepsiburadaCredentials() as $credentialId) {
        SyncHepsiburadaQuestionsJob::dispatch($credentialId)->onQueue('sync');
    }
})->hourly()->name('sync-hepsiburada-questions')->withoutOverlapping();

Schedule::call(function () use ($activeHepsiburadaCredentials) {
    foreach ($activeHepsiburadaCredentials() as $credentialId) {
        SyncHepsiburadaClaimsJob::dispatch($credentialId)->onQueue('sync');
    }
})->everySixHours()->name('sync-hepsiburada-claims')->withoutOverlapping();

Schedule::call(function () use ($activeHepsiburadaCredentials) {
    foreach ($activeHepsiburadaCredentials() as $credentialId) {
        SyncHepsiburadaFinancialsJob::dispatch($credentialId)->onQueue('sync');
    }
})->dailyAt('03:15')->name('sync-hepsiburada-financials')->withoutOverlapping();

Schedule::command('subscriptions:process-expired')
    ->dailyAt('00:05')
    ->name('process-expired-subscriptions')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Digest Mail Schedule
|--------------------------------------------------------------------------
|
| Daily digest at 09:00, weekly every Monday, monthly on the 1st day.
| Each job iterates over active users and sends per their preferences.
|
*/

Schedule::job(new SendDigestMail('daily_digest'))
    ->dailyAt('09:00')
    ->name('send-daily-digest')
    ->withoutOverlapping();

Schedule::job(new SendDigestMail('weekly_digest'))
    ->weeklyOn(1, '09:00')
    ->name('send-weekly-digest')
    ->withoutOverlapping();

Schedule::job(new SendDigestMail('monthly_digest'))
    ->monthlyOn(1, '09:00')
    ->name('send-monthly-digest')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Cargo Tracking Sync Schedule
|--------------------------------------------------------------------------
|
| Saatlik kargo takip durumu sorgulama. Webhook desteklemeyen
| kargo firmaları için polling mekanizması.
|
*/
Schedule::job(SyncCargoTrackingJob::class)
    ->hourly()
    ->name('sync-cargo-tracking')
    ->withoutOverlapping();
