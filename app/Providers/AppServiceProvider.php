<?php

namespace App\Providers;

use App\Services\Cargo\CargoManager;
use App\Services\EFatura\EInvoiceManager;
use App\Services\MarketplaceServiceFactory;
use App\Services\MarketplaceServiceInterface;
use Illuminate\Pagination\Paginator;
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

        $this->app->singleton(CargoManager::class, function ($app) {
            $manager = new CargoManager;
            $providers = config('cargo.providers', []);

            foreach ($providers as $code => $cfg) {
                if (isset($cfg['enabled']) && $cfg['enabled'] === false) {
                    continue;
                }
                if (isset($cfg['class'])) {
                    $manager->register($code, $cfg['class']);
                }
            }

            return $manager;
        });

        $this->app->singleton(EInvoiceManager::class, function ($app) {
            $manager = new EInvoiceManager;
            $providers = config('efatura.providers', []);

            foreach ($providers as $code => $cfg) {
                if (isset($cfg['enabled']) && $cfg['enabled'] === false) {
                    continue;
                }
                if (isset($cfg['class'])) {
                    $manager->register($code, $cfg['class']);
                }
            }

            return $manager;
        });
    }

    public function boot(): void
    {
        // Metronic/Bootstrap teması için sayfalama görünümü
        Paginator::useBootstrapFive();

        Blade::if('feature', function (string $feature): bool {
            return auth()->check() && auth()->user()->canUseFeature($feature);
        });

        // Türk Lirası para gösterimi: 1.234,56 ₺
        Blade::directive('money', function (string $expression): string {
            return "<?php echo number_format((float) ({$expression}), 2, ',', '.') . ' ₺'; ?>";
        });
    }
}
