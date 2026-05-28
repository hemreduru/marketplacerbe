<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Trendyol\Client;
use App\Services\Marketplaces\Trendyol\FinanceService;
use App\Services\Marketplaces\Trendyol\OrderService;
use App\Services\Marketplaces\Trendyol\ProductService;
use Illuminate\Console\Command;

class SyncUserMarketplaceDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-user {user_id} {--start-year= : Start year for sync (e.g. 2020)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all marketplace data for a specific user (Orders, Products, Financials)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $startYear = $this->option('start-year');

        $user = User::find($userId);

        if (! $user) {
            $this->error('User not found!');

            return 1;
        }

        $this->info("Starting sync for user: {$user->name} (ID: {$user->id})");
        if ($startYear) {
            $this->warn("CUSTOM START YEAR: Fetching data starting from {$startYear}-01-01...");
        }

        $credentials = UserMarketplaceCredential::where('user_id', $user->id)->get();

        foreach ($credentials as $credential) {
            $this->info("Processing Credential ID: {$credential->id} (Seller ID: ".($credential->additional_credentials['seller_id'] ?? 'N/A').')');

            // 1. Orders
            $this->info('Syncing Orders...');
            try {
                $orderService = new OrderService(
                    new Client(
                        $credential->api_key,
                        $credential->api_secret,
                        $credential->additional_credentials['seller_id'] ?? '',
                        false
                    )
                );
                $orderService->syncOrders($credential->marketplace_id, $credential->user_id, $startYear ? (int) $startYear : null, function ($msg, $stats) {
                    $this->line($msg);
                    $this->line(" > Order : Fail: {$stats['failed']} Created: {$stats['created']} Updated: {$stats['updated']}");
                });
                $this->info('Orders Synced.');
            } catch (\Exception $e) {
                $this->error('Orders Sync Failed: '.$e->getMessage());
            }

            // 2. Products
            $this->info('Syncing Products...');
            try {
                $productService = new ProductService(
                    new Client(
                        $credential->api_key,
                        $credential->api_secret,
                        $credential->additional_credentials['seller_id'] ?? '',
                        false
                    )
                );
                $productService->syncProducts($credential->id, function ($current, $total, $msg = null, $stats = []) {
                    if ($msg) {
                        $this->line(' > '.$msg);
                    }
                    if (! empty($stats)) {
                        $this->line(" > Product : Fail: {$stats['failed']} Created: {$stats['created']} Updated: {$stats['updated']}");
                    }
                });
                $this->info('Products Synced.');
            } catch (\Exception $e) {
                $this->error('Products Sync Failed: '.$e->getMessage());
            }

            // 3. Financials
            $this->info('Syncing Financials...');
            try {
                $financeService = new FinanceService(
                    new Client(
                        $credential->api_key,
                        $credential->api_secret,
                        $credential->additional_credentials['seller_id'] ?? '',
                        false
                    )
                );
                $financeService->syncSmart($credential->id, $startYear ? (int) $startYear : null, function ($current, $total, $msg = null, $stats = []) {
                    if ($msg) {
                        $this->line($msg);
                    }
                    if (! empty($stats)) {
                        $this->line(" > Financial : Fail: {$stats['failed']} Created: {$stats['created']} Updated: {$stats['updated']}");
                    }
                });
                $this->info("\nFinancials Synced.");
            } catch (\Exception $e) {
                $this->error('Financials Sync Failed: '.$e->getMessage());
            }
        }

        $this->info('All sync operations completed.');

        return 0;
    }
}
