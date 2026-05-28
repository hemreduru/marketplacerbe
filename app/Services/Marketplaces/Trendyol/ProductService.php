<?php

namespace App\Services\Marketplaces\Trendyol;

use App\Exceptions\SubscriptionLimitException;
use App\Models\Product;
use App\Models\UserMarketplaceCredential;
use App\Support\ServiceResult;

/**
 * Trendyol ürün senkronizasyonu — getProducts, updatePriceAndInventory, batch poll.
 *
 * Tüm HTTP çağrıları {@see Client} üzerinden yapılır, ServiceResult döner.
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
