<?php

namespace App\Jobs;

use App\Models\MarketplaceProduct;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 60;

    /**
     * The marketplace product ID to sync.
     *
     * @var int
     */
    protected int $marketplaceProductId;

    /**
     * The sync type: 'stock', 'price', or 'both'.
     *
     * @var string
     */
    protected string $syncType;

    /**
     * Create a new job instance.
     *
     * @param int $marketplaceProductId
     * @param string $syncType
     */
    public function __construct(int $marketplaceProductId, string $syncType = 'both')
    {
        $this->marketplaceProductId = $marketplaceProductId;
        $this->syncType = $syncType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $marketplaceProduct = MarketplaceProduct::with(['product', 'marketplace', 'credential'])
                ->find($this->marketplaceProductId);

            if (!$marketplaceProduct) {
                Log::error("Job - Pazaryeri Urun ID:{$this->marketplaceProductId} bulunamadi");
                return;
            }

            $userId = $marketplaceProduct->user_id;

            // Get credential
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $marketplaceProduct->marketplace_id)
                ->first();

            if (!$credential) {
                Log::error("Job - Pazaryeri Urun ID:{$this->marketplaceProductId} - Credential bulunamadi");
                return;
            }

            // Initialize service
            $service = MarketplaceServiceFactory::make($credential);

            // Sync stock
            if (in_array($this->syncType, ['stock', 'both'])) {
                try {
                    $barcode = $marketplaceProduct->marketplace_barcode ?? $marketplaceProduct->product->barcode;
                    if (!$barcode) {
                        throw new \Exception("Barcode not found for product");
                    }

                    $service->updateStock(
                        $barcode,
                        $marketplaceProduct->product->stock_quantity
                    );

                    Log::info("Job - Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} stok senkronize edildi - Miktar: {$marketplaceProduct->product->stock_quantity}");
                } catch (\Exception $e) {
                    Log::error("Job - Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} stok senkronize edilemedi - " . $e->getMessage());
                    throw $e; // Rethrow for retry mechanism
                }
            }

            // Sync price
            if (in_array($this->syncType, ['price', 'both'])) {
                try {
                    $barcode = $marketplaceProduct->marketplace_barcode ?? $marketplaceProduct->product->barcode;
                    if (!$barcode) {
                        throw new \Exception("Barcode not found for product");
                    }

                    $service->updatePrice(
                        $barcode,
                        $marketplaceProduct->product->sale_price
                    );

                    Log::info("Job - Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} fiyat senkronize edildi - Fiyat: {$marketplaceProduct->product->sale_price}");
                } catch (\Exception $e) {
                    Log::error("Job - Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} fiyat senkronize edilemedi - " . $e->getMessage());
                    throw $e; // Rethrow for retry mechanism
                }
            }

            // Update last sync time
            $marketplaceProduct->update([
                'last_sync_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Job - Pazaryeri Urun ID:{$this->marketplaceProductId} sync basarisiz - Deneme: {$this->attempts()}/{$this->tries} - " . $e->getMessage());

            // If max attempts reached, log final failure
            if ($this->attempts() >= $this->tries) {
                Log::error("Job - Pazaryeri Urun ID:{$this->marketplaceProductId} sync kalici olarak basarisiz - Tum denemeler tuketildi");
            }

            throw $e; // Rethrow to trigger retry
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job FAILED - Pazaryeri Urun ID:{$this->marketplaceProductId} - {$this->tries} deneme sonrasi basarisiz - " . $exception->getMessage());
    }
}
