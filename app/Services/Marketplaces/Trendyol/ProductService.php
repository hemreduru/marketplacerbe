<?php

namespace App\Services\Marketplaces\Trendyol;

use App\Exceptions\SubscriptionLimitException;
use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Contracts\InventoryWriter;
use App\Services\Marketplaces\Trendyol\Mapper\ProductMapper;
use App\Support\ServiceResult;

/**
 * Trendyol ürün senkronizasyonu — getProducts, updatePriceAndInventory, batch poll.
 *
 * Tüm HTTP çağrıları {@see Client} üzerinden yapılır, ServiceResult döner.
 */
class ProductService implements InventoryWriter
{
    public function __construct(protected Client $client) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return ServiceResult<array<string, mixed>>
     */
    public function getProducts(array $filters = []): ServiceResult
    {
        $path = '/integration/product/sellers/'.$this->client->getSellerId().'/products';

        return $this->client->get($path, $filters);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function getBrands(int $page = 0, int $size = 100): ServiceResult
    {
        return $this->client->get('/integration/product/brands', [
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function getCategories(): ServiceResult
    {
        return $this->client->get('/integration/product/product-categories');
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function getCategoryAttributes(int $categoryId): ServiceResult
    {
        $path = '/integration/product/product-categories/'.$categoryId.'/attributes';

        return $this->client->get($path);
    }

    /**
     * @param  callable|null  $onProgress  function($current, $total)
     */
    public function syncProducts(int $credentialId, ?callable $onProgress = null): void
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
                throw new \RuntimeException($result->errorMessage ?? 'Trendyol ürün API hatası');
            }

            $response = $result->data;
            $content = $response['content'] ?? [];
            $totalElements = $response['totalElements'] ?? 0;

            if (empty($content)) {
                break;
            }

            foreach ($content as $item) {
                try {
                    $remoteId = $item['productMainId'] ?? $item['id'] ?? null;
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
                        [
                            'barcode' => $item['barcode'] ?? null,
                            'sku' => $item['stockCode'] ?? null,
                            'title' => $item['title'] ?? 'Unknown Product',
                            'brand' => $item['brand']['name'] ?? null,
                            'category_name' => $item['categoryName'] ?? null,
                            'category_id' => $item['pimCategoryId'] ?? null,
                            'price' => $item['salePrice'] ?? 0,
                            'list_price' => $item['listPrice'] ?? 0,
                            'stock' => $item['quantity'] ?? $item['stockUnitQuantity'] ?? 0,
                            'currency' => $item['currencyType'] ?? 'TRY',
                            'status' => ($item['approved'] ?? false) ? 'active' : 'inactive',
                            'images' => $item['images'] ?? [],
                            'attributes' => $item['attributes'] ?? [],
                            'description' => $item['description'] ?? null,
                            'product_url' => $item['productUrl'] ?? null,
                        ]
                    );

                    if ($product->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }

                    // Cirotik master_products + marketplace_listings köprüsü:
                    // legacy Product'a ek olarak yeni cross-marketplace şemasını doldur.
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
                $onProgress($totalProcessed, $totalElements, "Fetched: {$totalProcessed} / {$totalElements}", $stats);
            }

            $page++;

            if ($page * $size > $totalElements + $size) {
                break;
            }

        } while (! empty($content));
    }

    /**
     * Tek bir Trendyol ürününü marketplace_listings'e upsert eder ve
     * uygun master_product'a bağlar. Aynı (credential, remote_product_id)
     * tekrar sync edilince listing güncellenir, master yeniden oluşturulmaz.
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

        $masterId = $existingListing?->master_product_id
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
     * Bir kullanıcının ürünleri için master_product bulur ya da oluşturur.
     * Eşleşme önceliği: barcode → sku. İkisi de yoksa solo master üretilir.
     *
     * @param  array<string, mixed>  $item
     */
    protected function resolveMasterProduct(int $userId, array $item): MasterProduct
    {
        $mapper = new ProductMapper;
        $barcode = $item['barcode'] ?? null;
        $sku = $item['stockCode'] ?? null;

        $existing = MasterProduct::where('user_id', $userId)
            ->when($barcode, fn ($q) => $q->where('barcode', $barcode))
            ->when(! $barcode && $sku, fn ($q) => $q->where('sku', $sku))
            ->when(! $barcode && ! $sku, fn ($q) => $q->whereRaw('1 = 0'))
            ->first();

        return $existing ?? MasterProduct::create(array_merge(
            $mapper->toMasterProductAttributes($item),
            ['user_id' => $userId],
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return ServiceResult<array<string, mixed>>
     */
    public function updatePriceAndInventory(array $items): ServiceResult
    {
        $path = '/integration/inventory/sellers/'.$this->client->getSellerId()
            .'/products/price-and-inventory';

        return $this->client->post($path, ['items' => array_values($items)]);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function getBatchRequestResult(string $batchRequestId): ServiceResult
    {
        $path = '/integration/product/sellers/'.$this->client->getSellerId()
            .'/products/batch-requests/'.$batchRequestId;

        return $this->client->get($path);
    }
}
