<?php

namespace App\Console\Commands;

use App\Models\MarketplaceCategory;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-categories {marketplace? : Marketplace slug (trendyol, hepsiburada)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync marketplace categories to local cache';

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

                // Fetch categories from marketplace API
                $categories = $service->getCategories();

                if (empty($categories)) {
                    $this->warn("{$marketplace->name} için kategori bulunamadı.");
                    continue;
                }

                // Sync categories
                $synced = 0;
                $progressBar = $this->output->createProgressBar(count($categories));
                $progressBar->start();

                foreach ($categories as $category) {
                    $this->syncCategory($marketplace->id, $category);
                    $synced++;
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();

                Log::channel('resbe')->info("[SyncMarketplaceCategoriesCommand] {$marketplace->name}: {$synced} kategori senkronize edildi");
                $this->info("✓ {$synced} kategori senkronize edildi");

            } catch (\Exception $e) {
                Log::channel('resbe')->error("[SyncMarketplaceCategoriesCommand] {$marketplace->name} hatası: " . $e->getMessage());
                $this->error("Hata: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->info('Tüm kategoriler başarıyla senkronize edildi.');
        return Command::SUCCESS;
    }

    /**
     * Sync a single category (recursive for hierarchical structure)
     */
    private function syncCategory(int $marketplaceId, array $category, ?int $parentId = null, int $level = 0, string $fullPath = ''): void
    {
        // Build full path
        $currentPath = $fullPath ? $fullPath . ' > ' . ($category['name'] ?? '') : ($category['name'] ?? '');

        // Check if has children
        $hasChildren = !empty($category['subCategories']) || !empty($category['children']);
        $isLeaf = !$hasChildren;

        // Upsert category
        $dbCategory = MarketplaceCategory::updateOrCreate(
            [
                'marketplace_id' => $marketplaceId,
                'marketplace_category_id' => (string)$category['id'],
            ],
            [
                'name' => $category['name'] ?? '',
                'parent_id' => $parentId,
                'full_path' => $currentPath,
                'level' => $level,
                'is_leaf' => $isLeaf,
                'attributes' => $category['attributes'] ?? null,
                'marketplace_raw_data' => $category,
            ]
        );

        // Recursively sync children/subCategories
        $children = $category['subCategories'] ?? $category['children'] ?? [];
        foreach ($children as $child) {
            $this->syncCategory($marketplaceId, $child, $dbCategory->id, $level + 1, $currentPath);
        }
    }
}
