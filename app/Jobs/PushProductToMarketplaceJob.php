<?php

namespace App\Jobs;

use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushProductToMarketplaceJob implements ShouldQueue
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
     * The product ID to push.
     *
     * @var int
     */
    protected int $productId;

    /**
     * The marketplace credential ID.
     *
     * @var int
     */
    protected int $credentialId;

    /**
     * The user ID.
     *
     * @var int
     */
    protected int $userId;

    /**
     * Create a new job instance.
     *
     * @param int $productId
     * @param int $credentialId
     * @param int $userId
     */
    public function __construct(int $productId, int $credentialId, int $userId)
    {
        $this->productId = $productId;
        $this->credentialId = $credentialId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $product = Product::find($this->productId);
            if (!$product) {
                Log::error("Job - Kullanici ID:{$this->userId} - Urun ID:{$this->productId} bulunamadi");
                return;
            }

            $credential = UserMarketplaceCredential::with('marketplace')
                ->find($this->credentialId);
            if (!$credential) {
                Log::error("Job - Kullanici ID:{$this->userId} - Credential ID:{$this->credentialId} bulunamadi");
                return;
            }

            // Check if already synced
            $existingSync = MarketplaceProduct::where('product_id', $product->id)
                ->where('marketplace_id', $credential->marketplace_id)
                ->where('user_id', $this->userId)
                ->first();

            if ($existingSync) {
                Log::warning("Job - Kullanici ID:{$this->userId} - Urun ID:{$product->id} - SKU:{$product->sku} - Pazaryeri ID:{$credential->marketplace_id} zaten senkronize");
                return;
            }

            // Initialize service
            $service = MarketplaceServiceFactory::make($credential);

            // Push to marketplace
            $response = $service->createProduct($product);

            // Store marketplace product relationship
            MarketplaceProduct::create([
                'user_id' => $this->userId,
                'product_id' => $product->id,
                'marketplace_id' => $credential->marketplace_id,
                'marketplace_product_id' => $response['id'] ?? null,
                'marketplace_sku' => $response['sku'] ?? $product->sku,
                'marketplace_barcode' => $response['barcode'] ?? $product->barcode,
                'marketplace_status' => $response['status'] ?? 'pending',
                'approved' => $response['approved'] ?? false,
                'last_sync_at' => now(),
                'metadata' => $response,
            ]);

            Log::info("Job - Kullanici ID:{$this->userId} - Urun ID:{$product->id} - SKU:{$product->sku} - {$credential->marketplace->name} pazaryerine gonderildi");
        } catch (\Exception $e) {
            Log::error("Job - Kullanici ID:{$this->userId} - Urun ID:{$this->productId} push basarisiz - Deneme: {$this->attempts()}/{$this->tries} - " . $e->getMessage());

            // If max attempts reached, log final failure
            if ($this->attempts() >= $this->tries) {
                Log::error("Job - Kullanici ID:{$this->userId} - Urun ID:{$this->productId} push kalici olarak basarisiz - Tum denemeler tuketildi");
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
        Log::error("Job FAILED - Kullanici ID:{$this->userId} - Urun ID:{$this->productId} - {$this->tries} deneme sonrasi basarisiz - " . $exception->getMessage());
    }
}
