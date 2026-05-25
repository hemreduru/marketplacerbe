<?php

namespace App\Providers;

use App\Services\MarketplaceServiceFactory;
use App\Services\MarketplaceServiceInterface;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MarketplaceServiceFactory::class, function ($app) {
            return new MarketplaceServiceFactory;
        });

        $this->app->bind(MarketplaceServiceInterface::class, function ($app) {
            return $app->make(MarketplaceServiceFactory::class);
        });
    }

    public function boot(): void
    {
        Blade::if('feature', function (string $feature): bool {
            return auth()->check() && auth()->user()->canUseFeature($feature);
        });
    }
}
