<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Exceptions\SubscriptionLimitException;
use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Hepsiburada\Mapper\ProductMapper;
use App\Support\ServiceResult;

/**
 * Hepsiburada ürün senkronizasyonu — getProducts, updatePriceAndInventory.
 *
 * Trendyol ProductService ile aynı persistence paterni: legacy Product +
 * cross-marketplace köprüsü (MasterProduct + MarketplaceListing).
 */
class ProductService
{
    public function __construct(protected Client $client) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return ServiceResult<array<string, mixed>>
     */
    public function getProducts(array $filters = []): ServiceResult
    {
        return $this->client->get('/product/api/products', $filters);
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncProducts(int $credentialId, ?callable $onProgress = null): array
    {
        $page = 0;
        $size = 50;
        $totalProcessed = 0;
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        $credential = UserMarketplaceCredential::with('user')->find($credentialId);
        $user = $credential?->user;
        $limit = $user?->getSubscriptionLimit('products') ?? 500;

        do {
            $result = $this->getProducts(['page' => $page, 'size' => $size]);

            if (! $result->ok) {
                throw new \RuntimeException($result->errorMessage ?? 'Hepsiburada ürün API hatası');
            }

            $content = $result->data['content'] ?? $result->data['data'] ?? $result->data['listings'] ?? [];

            if (empty($content)) {
                break;
            }

            $mapper = new ProductMapper;

            foreach ($content as $item) {
                try {
                    $attributes = $mapper->toProductAttributes($item);
                    $remoteId = (string) ($item['hbSku'] ?? $item['hepsiburadaSku'] ?? $item['id'] ?? $attributes['sku'] ?? '');

                    $exists = Product::where('user_marketplace_credential_id', $credentialId)
                        ->where('remote_id', $remoteId)
                        ->exists();

                    if (! $exists && $user) {
                        $currentCount = Product::whereHas('credential', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        })->count();

                        if ($limit !== -1 && $currentCount >= $limit) {
                            throw new SubscriptionLimitException(
                                __('subscription.product_limit_reached', ['limit' => $limit])
                            );
                        }
                    }

                    $product = Product::updateOrCreate(
                        [
                            'user_marketplace_credential_id' => $credentialId,
                            'remote_id' => $remoteId,
                        ],
                        $attributes,
                    );

                    $product->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;

                    if ($credential !== null) {
                        $this->syncListing($credential, $item);
                    }
                } catch (\Exception $e) {
                    if ($e instanceof SubscriptionLimitException) {
                        throw $e;
                    }
                    $stats['failed']++;
                }
                $totalProcessed++;
            }

            if ($onProgress) {
                $onProgress($totalProcessed, null, "Fetched: {$totalProcessed}", $stats);
            }

            $page++;
        } while (count($content) === $size);

        return $stats;
    }

    /**
     * Tek bir HB ürününü marketplace_listings'e upsert eder ve uygun
     * master_product'a bağlar (barcode → sku eşleşme önceliğiyle).
     *
     * @param  array<string, mixed>  $item
     */
    protected function syncListing(UserMarketplaceCredential $credential, array $item): void
    {
        $mapper = new ProductMapper;
        $listingAttributes = $mapper->toListingAttributes($item);

        $existingListing = MarketplaceListing::where('user_marketplace_credential_id', $credential->id)
            ->where('remote_product_id', $listingAttributes['remote_product_id'])
            ->first();

        $masterId = $existingListing->master_product_id
            ?? $this->resolveMasterProduct($credential->user_id, $item)->id;

        MarketplaceListing::updateOrCreate(
            [
                'user_marketplace_credential_id' => $credential->id,
                'remote_product_id' => $listingAttributes['remote_product_id'],
            ],
            array_merge($listingAttributes, ['master_product_id' => $masterId]),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolveMasterProduct(int $userId, array $item): MasterProduct
    {
        $mapper = new ProductMapper;
        $attributes = $mapper->toMasterProductAttributes($item);
        $barcode = $attributes['barcode'];
        $sku = $attributes['sku'];

        $existing = MasterProduct::where('user_id', $userId)
            ->when($barcode, fn ($q) => $q->where('barcode', $barcode))
            ->when(! $barcode && $sku, fn ($q) => $q->where('sku', $sku))
            ->when(! $barcode && ! $sku, fn ($q) => $q->whereRaw('1 = 0'))
            ->first();

        return $existing ?? MasterProduct::create(array_merge(
            $attributes,
            ['user_id' => $userId],
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return ServiceResult<array<string, mixed>>
     */
    public function updatePriceAndInventory(array $items): ServiceResult
    {
        return $this->client->post('/product/api/products/price-and-stock', ['items' => $items]);
    }
}
