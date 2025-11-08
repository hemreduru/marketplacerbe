<?php

namespace App\Providers;

use App\Services\MarketplaceServiceFactory;
use App\Services\MarketplaceServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register MarketplaceServiceFactory as singleton
        $this->app->singleton(MarketplaceServiceFactory::class, function ($app) {
            return new MarketplaceServiceFactory();
        });

        // Bind MarketplaceServiceInterface to factory
        $this->app->bind(MarketplaceServiceInterface::class, function ($app) {
            // This will be resolved dynamically based on the current user's marketplace credential
            // Usage: app(MarketplaceServiceInterface::class, ['credential' => $credential])
            return $app->make(MarketplaceServiceFactory::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
