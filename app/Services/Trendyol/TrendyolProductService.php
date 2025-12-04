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
}
