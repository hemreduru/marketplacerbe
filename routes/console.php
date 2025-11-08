<?php

use App\Jobs\SyncMarketplaceProductJob;
use App\Models\MarketplaceProduct;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Tasks for Marketplace Sync
Schedule::call(function () {
    // Sync stock and price for all marketplace products that haven't been synced in the last 6 hours
    $marketplaceProducts = MarketplaceProduct::where('last_sync_at', '<', now()->subHours(6))
        ->orWhereNull('last_sync_at')
        ->get();

    foreach ($marketplaceProducts as $marketplaceProduct) {
        SyncMarketplaceProductJob::dispatch($marketplaceProduct->id, 'both')
            ->onQueue('sync');
    }
})->everySixHours()->name('sync-marketplace-stock-price')->withoutOverlapping();

// Sync recently updated products (last 1 hour) every 30 minutes
Schedule::call(function () {
    $recentlyUpdatedProducts = MarketplaceProduct::whereHas('product', function ($query) {
        $query->where('updated_at', '>', now()->subHour());
    })->get();

    foreach ($recentlyUpdatedProducts as $marketplaceProduct) {
        SyncMarketplaceProductJob::dispatch($marketplaceProduct->id, 'both')
            ->onQueue('sync');
    }
})->everyThirtyMinutes()->name('sync-recent-updates')->withoutOverlapping();
