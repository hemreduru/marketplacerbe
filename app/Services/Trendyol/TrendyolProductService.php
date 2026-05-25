<?php

namespace App\Services\Trendyol;

use App\Models\Product;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolProductService implements ProductServiceContract
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $apiSecret;

    protected string $sellerId;

    public function __construct(string $apiKey, string $apiSecret, string $sellerId, bool $isStage = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->sellerId = $sellerId;
        $this->baseUrl = $isStage ? 'https://stageapigw.trendyol.com' : 'https://apigw.trendyol.com';
    }

    /**
     * Get products from the seller's inventory.
     */
    public function getProducts(array $filters = []): array
    {
        $url = sprintf('%s/integration/product/sellers/%s/products', $this->baseUrl, $this->sellerId);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->get($url, $filters);

        if ($response->failed()) {
            Log::error('Trendyol Product API Error (getProducts): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Get all brands.
     */
    public function getBrands(int $page = 0, int $size = 100): array
    {
        $url = sprintf('%s/integration/product/brands', $this->baseUrl);

        $response = Http::get($url, [
            'page' => $page,
            'size' => $size,
        ]);

        if ($response->failed()) {
            Log::error('Trendyol Product API Error (getBrands): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Get category tree.
     */
    public function getCategories(): array
    {
        $url = sprintf('%s/integration/product/product-categories', $this->baseUrl);

        $response = Http::get($url);

        if ($response->failed()) {
            Log::error('Trendyol Product API Error (getCategories): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Get attributes for a specific category.
     */
    public function getCategoryAttributes(int $categoryId): array
    {
        $url = sprintf('%s/integration/product/product-categories/%s/attributes', $this->baseUrl, $categoryId);

        $response = Http::get($url);

        if ($response->failed()) {
            Log::error('Trendyol Product API Error (getCategoryAttributes): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Sync products from Trendyol to Database.
     *
     * @param  callable|null  $onProgress  function($current, $total)
     */
    public function syncProducts(int $credentialId, ?callable $onProgress = null): void
    {
        $page = 0;
        $size = 50;
        $totalProcessed = 0;
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        do {
            $response = $this->getProducts(['page' => $page, 'size' => $size]);

            if (isset($response['error'])) {
                throw new \Exception($response['message']);
            }

            $content = $response['content'] ?? [];
            $totalElements = $response['totalElements'] ?? 0;

            if (empty($content)) {
                break;
            }

            foreach ($content as $item) {
                try {
                    $product = Product::updateOrCreate(
                        [
                            'user_marketplace_credential_id' => $credentialId,
                            'remote_id' => $item['productMainId'] ?? $item['id'] ?? null,
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
                    $stats['failed']++;
                }
                $totalProcessed++;
            }

            if ($onProgress) {
                $onProgress($totalProcessed, $totalElements, "Fetched: {$totalProcessed} / {$totalElements}", $stats);
            }

            $page++;
            // Safety break to prevent infinite loops if API reports wrong total
            if ($page * $size > $totalElements + $size) {
                break;
            }

        } while (! empty($content));
    }

    /**
     * Push price and/or stock updates to Trendyol.
     *
     * Each item must contain a barcode and at least one of quantity, salePrice
     * or listPrice. Trendyol processes this asynchronously and returns a
     * batchRequestId that can be polled via getBatchRequestResult().
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function updatePriceAndInventory(array $items): array
    {
        $url = sprintf('%s/integration/inventory/sellers/%s/products/price-and-inventory', $this->baseUrl, $this->sellerId);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->post($url, ['items' => array_values($items)]);

        if ($response->failed()) {
            Log::error('Trendyol Product API Error (updatePriceAndInventory): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json() ?: ['success' => true];
    }

    /**
     * Poll the result of an asynchronous batch request.
     *
     * @return array<string, mixed>
     */
    public function getBatchRequestResult(string $batchRequestId): array
    {
        $url = sprintf('%s/integration/product/sellers/%s/products/batch-requests/%s', $this->baseUrl, $this->sellerId, $batchRequestId);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)->get($url);

        if ($response->failed()) {
            Log::error('Trendyol Product API Error (getBatchRequestResult): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }
}
