<?php

namespace App\Jobs;

use App\Jobs\Concerns\HasRetryPolicy;
use App\Models\MasterProduct;
use App\Models\SyncDispatchEntry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkStockUpdateJob implements ShouldQueue
{
    use Dispatchable, HasRetryPolicy, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, int>  $masterProductIds
     * @param  array{type: string, value: string}  $rule
     */
    public function __construct(
        private readonly int $userId,
        private readonly array $masterProductIds,
        private readonly array $rule,
        private readonly ?string $marketplaceSlug = null,
    ) {}

    public function handle(): void
    {
        $products = MasterProduct::whereIn('id', $this->masterProductIds)
            ->where('user_id', $this->userId)
            ->get();

        foreach ($products as $product) {
            $newStock = $this->calculateStock($product->current_stock);
            $product->listings()
                ->when($this->marketplaceSlug, fn ($q) => $q->whereHas('credential.marketplace', fn ($q) => $q->where('slug', $this->marketplaceSlug)))
                ->each(function ($listing) use ($newStock) {
                    SyncDispatchEntry::create([
                        'master_product_id' => $listing->master_product_id,
                        'marketplace_listing_id' => $listing->id,
                        'mutation_type' => 'stock',
                        'payload_json' => ['new_stock' => $newStock],
                        'status' => 'pending',
                    ]);
                });
        }
    }

    /**
     * İnteger aritmetikle yüzde/mutlak/formül stok hesaplama.
     * Stok tam sayıdır; float kullanılmaz.
     */
    private function calculateStock(int $currentStock): int
    {
        $value = (int) $this->rule['value'];

        return match ($this->rule['type']) {
            'percentage' => $currentStock + (int) ($currentStock * $value / 100),
            'absolute' => $currentStock + $value,
            'formula' => $value,
            default => $currentStock,
        };
    }

    public function failed(Throwable $e): void
    {
        Log::error('BulkStockUpdateJob failed', [
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
        ]);
    }
}
