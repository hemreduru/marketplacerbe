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

class BulkPriceUpdateJob implements ShouldQueue
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
            $newPrice = $this->calculatePrice($product->current_price);
            $product->listings()
                ->whereHas('credential', fn ($q) => $q->where('user_id', $this->userId))
                ->when($this->marketplaceSlug, fn ($q) => $q->whereHas('credential.marketplace', fn ($q) => $q->where('slug', $this->marketplaceSlug)))
                ->each(function ($listing) use ($newPrice) {
                    SyncDispatchEntry::create([
                        'master_product_id' => $listing->master_product_id,
                        'marketplace_listing_id' => $listing->id,
                        'mutation_type' => 'price',
                        'payload_json' => ['listed_price' => $newPrice],
                        'status' => 'pending',
                    ]);
                });
        }
    }

    /**
     * bcmath ile yüzde/mutlak formül hesaplama. Spec Bölüm 0: asla float.
     */
    private function calculatePrice(string $currentPrice): string
    {
        $value = (string) $this->rule['value'];

        return match ($this->rule['type']) {
            'percentage' => bcadd(
                $currentPrice,
                bcmul($currentPrice, bcdiv($value, '100', 6), 6),
                4
            ),
            'absolute' => bcadd($currentPrice, $value, 4),
            'formula' => $value,
            default => $currentPrice,
        };
    }

    public function failed(Throwable $e): void
    {
        Log::error('BulkPriceUpdateJob failed', [
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
        ]);
    }
}
