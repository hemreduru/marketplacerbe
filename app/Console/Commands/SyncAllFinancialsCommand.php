<?php

namespace App\Console\Commands;

use App\Models\Marketplace;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Trendyol\Client as TrendyolClient;
use App\Services\Marketplaces\Trendyol\FinanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllFinancialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:sync-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs 15-year financial data for all users with active Trendyol credentials.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting financial data sync for all users...');

        $trendyol = Marketplace::where('slug', 'trendyol')->first();

        if (! $trendyol) {
            $this->error('Trendyol marketplace not found!');

            return 1;
        }

        $credentials = UserMarketplaceCredential::where('marketplace_id', $trendyol->id)
            ->where('is_active', true)
            ->get();

        if ($credentials->isEmpty()) {
            $this->warn('No active Trendyol credentials found.');

            return 0;
        }

        $this->info("Found {$credentials->count()} active credentials.");
        $this->newLine();

        $results = [];

        foreach ($credentials as $index => $credential) {
            $userNum = $index + 1;
            $totalUsers = $credentials->count();

            $this->info("Processing User {$userNum}/{$totalUsers} (ID: {$credential->user_id})...");

            // Create a progress bar for this user's chunks
            // We don't know exact total chunks until service calculates it,
            // but we can initialize it and update max later, or just let the service drive it.
            // Actually, we can just start it with 0 and let the callback set the max on first call if we wanted,
            // but standard ProgressBar needs max.
            // Let's estimate or just use a generic bar that advances.
            // Better: The service callback gives $total.

            $bar = $this->output->createProgressBar();
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% %memory:6s%');

            try {
                $service = new FinanceService(new TrendyolClient(
                    $credential->api_key,
                    $credential->api_secret,
                    $credential->additional_credentials['seller_id'] ?? '',
                    false
                ));

                $service->syncSmart($credential->id, null, function ($current, $total, $msg = null, $stats = []) use ($bar) {
                    if ($bar->getMaxSteps() != $total) {
                        $bar->setMaxSteps($total);
                        $bar->start();
                    }
                    $bar->setProgress($current);
                });

                $bar->finish();
                $this->newLine();
                $this->info("User {$credential->user_id} completed.");
                $this->newLine();

                $results[] = [
                    'User ID' => $credential->user_id,
                    'Status' => 'Success',
                    'Message' => 'Synced 15 years of data',
                ];
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Sync failed for credential {$credential->id}: ".$e->getMessage());
                Log::error("Sync failed for credential {$credential->id}: ".$e->getMessage());
                $results[] = [
                    'User ID' => $credential->user_id,
                    'Status' => 'Failed',
                    'Message' => substr($e->getMessage(), 0, 50).'...',
                ];
            }
        }

        $this->newLine();
        $this->table(
            ['User ID', 'Status', 'Message'],
            $results
        );

        $this->info('Financial data sync completed.');

        return 0;
    }
}
