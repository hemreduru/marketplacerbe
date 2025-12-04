<?php

namespace App\Services\Trendyol;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolProductService
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
     *
     * @param array $filters
     * @return array
     */
    public function getProducts(array $filters = []): array
    {
        $url = sprintf("%s/integration/product/sellers/%s/products", $this->baseUrl, $this->sellerId);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->get($url, $filters);

        if ($response->failed()) {
            Log::error("Trendyol Product API Error (getProducts): " . $response->body());
            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Get all brands.
     *
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getBrands(int $page = 0, int $size = 100): array
    {
        $url = sprintf("%s/integration/product/brands", $this->baseUrl);

        $response = Http::get($url, [
            'page' => $page,
            'size' => $size
        ]);

        if ($response->failed()) {
            Log::error("Trendyol Product API Error (getBrands): " . $response->body());
            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Get category tree.
     *
     * @return array
     */
    public function getCategories(): array
    {
        $url = sprintf("%s/integration/product/product-categories", $this->baseUrl);

        $response = Http::get($url);

        if ($response->failed()) {
            Log::error("Trendyol Product API Error (getCategories): " . $response->body());
            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Get attributes for a specific category.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryAttributes(int $categoryId): array
    {
        $url = sprintf("%s/integration/product/product-categories/%s/attributes", $this->baseUrl, $categoryId);

        $response = Http::get($url);

        if ($response->failed()) {
            Log::error("Trendyol Product API Error (getCategoryAttributes): " . $response->body());
            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }
    /**
     * Sync products from Trendyol to Database.
     *
     * @param int $credentialId
     * @param callable|null $onProgress function($current, $total)
     * @return void
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
                    $product = \App\Models\Product::updateOrCreate(
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

                    if ($product->wasRecentlyCreated) $stats['created']++;
                    else $stats['updated']++;

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
            if ($page * $size > $totalElements + $size) break;

        } while (!empty($content));
    }
}
