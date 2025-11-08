<?php

namespace App\Console\Commands;

use App\Models\MarketplaceBrand;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceBrandsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-brands {marketplace? : Marketplace slug (trendyol, hepsiburada)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync marketplace brands to local cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $marketplaceSlug = $this->argument('marketplace');

        // Get credentials (for now, get first active credential for the marketplace)
        $query = UserMarketplaceCredential::with('marketplace')
            ->where('is_active', true);

        if ($marketplaceSlug) {
            $query->whereHas('marketplace', function ($q) use ($marketplaceSlug) {
                $q->where('slug', $marketplaceSlug);
            });
        }

        $credentials = $query->get();

        if ($credentials->isEmpty()) {
            $this->error('Aktif marketplace kimlik bilgisi bulunamadı.');
            return Command::FAILURE;
        }

        foreach ($credentials as $credential) {
            $marketplace = $credential->marketplace;
            $this->info("Senkronize ediliyor: {$marketplace->name}");

            try {
                // Get service instance
                $service = MarketplaceServiceFactory::make($credential);

                // Fetch brands from marketplace API
                $brands = $service->getBrands();

                if (empty($brands)) {
                    $this->warn("{$marketplace->name} için marka bulunamadı.");
                    continue;
                }

                // Sync brands
                $synced = 0;
                $progressBar = $this->output->createProgressBar(count($brands));
                $progressBar->start();

                foreach ($brands as $brand) {
                    MarketplaceBrand::updateOrCreate(
                        [
                            'marketplace_id' => $marketplace->id,
                            'marketplace_brand_id' => (string)$brand['id'],
                        ],
                        [
                            'name' => $brand['name'] ?? '',
                            'marketplace_raw_data' => $brand,
                        ]
                    );

                    $synced++;
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();

                Log::channel('resbe')->info("[SyncMarketplaceBrandsCommand] {$marketplace->name}: {$synced} marka senkronize edildi");
                $this->info("✓ {$synced} marka senkronize edildi");

            } catch (\Exception $e) {
                Log::channel('resbe')->error("[SyncMarketplaceBrandsCommand] {$marketplace->name} hatası: " . $e->getMessage());
                $this->error("Hata: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->info('Tüm markalar başarıyla senkronize edildi.');
        return Command::SUCCESS;
    }
}
