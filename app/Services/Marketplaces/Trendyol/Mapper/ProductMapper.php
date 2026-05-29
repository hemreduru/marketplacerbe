<?php

namespace App\Services\Marketplaces\Trendyol\Mapper;

use App\Models\MarketplaceListing;
use App\Models\MasterProduct;

/**
 * Trendyol ürün verisini Cirotik iç formatına dönüştürür.
 */
class ProductMapper
{
    /**
     * Trendyol API item → MasterProduct fillable.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function toMasterProductAttributes(array $item): array
    {
        return [
            'title' => $item['title'] ?? 'Unknown Product',
            'brand' => $item['brand']['name'] ?? null,
            'sku' => $item['stockCode'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'cost_price' => 0,
            'current_price' => $item['salePrice'] ?? 0,
            'current_stock' => $item['quantity'] ?? $item['stockUnitQuantity'] ?? 0,
        ];
    }

    /**
     * Trendyol API item → MarketplaceListing fillable.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function toListingAttributes(array $item): array
    {
        return [
            'remote_product_id' => (string) ($item['productMainId'] ?? $item['id'] ?? ''),
            'remote_sku' => $item['stockCode'] ?? null,
            'remote_barcode' => $item['barcode'] ?? null,
            'listing_status' => ($item['approved'] ?? false) ? 'active' : 'inactive',
            'listed_price' => $item['salePrice'] ?? 0,
            'listed_stock' => $item['quantity'] ?? $item['stockUnitQuantity'] ?? 0,
            'listing_url' => $item['productUrl'] ?? null,
            'category_path' => $item['categoryName'] ?? null,
            'attributes_json' => $item['attributes'] ?? [],
            'last_synced_at' => now(),
            'sync_status' => 'synced',
        ];
    }
}
