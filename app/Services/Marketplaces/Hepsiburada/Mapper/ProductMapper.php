<?php

namespace App\Services\Marketplaces\Hepsiburada\Mapper;

/**
 * Hepsiburada ürün/listing verisini Cirotik iç formatına dönüştürür.
 *
 * HB alan adları Trendyol'dan farklıdır (merchantSku/hbSku, availableStock,
 * isSalable) ve bazı alanlar {amount, currency} para objesi olabilir —
 * mapper her iki şekli de savunmacı okur.
 */
class ProductMapper
{
    /**
     * HB API item → legacy Product fillable.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function toProductAttributes(array $item): array
    {
        return [
            'barcode' => $item['barcode'] ?? null,
            'sku' => $this->merchantSku($item),
            'title' => $item['productName'] ?? $item['title'] ?? 'Unknown Product',
            'brand' => is_array($item['brand'] ?? null) ? ($item['brand']['name'] ?? null) : ($item['brand'] ?? null),
            'category_name' => $item['categoryName'] ?? null,
            'category_id' => $item['categoryId'] ?? null,
            'price' => $this->money($item['price'] ?? 0),
            'list_price' => $this->money($item['listPrice'] ?? $item['price'] ?? 0),
            'stock' => $this->stock($item),
            'currency' => 'TRY',
            'status' => $this->isActive($item) ? 'active' : 'inactive',
            'images' => $item['images'] ?? [],
            'attributes' => $item['attributes'] ?? [],
            'description' => $item['description'] ?? null,
            'product_url' => $item['productUrl'] ?? null,
        ];
    }

    /**
     * HB API item → MasterProduct fillable.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function toMasterProductAttributes(array $item): array
    {
        return [
            'title' => $item['productName'] ?? $item['title'] ?? 'Unknown Product',
            'brand' => is_array($item['brand'] ?? null) ? ($item['brand']['name'] ?? null) : ($item['brand'] ?? null),
            'sku' => $this->merchantSku($item),
            'barcode' => $item['barcode'] ?? null,
            'cost_price' => 0,
            'current_price' => $this->money($item['price'] ?? 0),
            'current_stock' => $this->stock($item),
        ];
    }

    /**
     * HB API item → MarketplaceListing fillable.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function toListingAttributes(array $item): array
    {
        return [
            'remote_product_id' => (string) ($item['hbSku'] ?? $item['hepsiburadaSku'] ?? $item['id'] ?? $this->merchantSku($item) ?? ''),
            'remote_sku' => $this->merchantSku($item),
            'remote_barcode' => $item['barcode'] ?? null,
            'listing_status' => $this->isActive($item) ? 'active' : 'inactive',
            'listed_price' => $this->money($item['price'] ?? 0),
            'listed_stock' => $this->stock($item),
            'listing_url' => $item['productUrl'] ?? null,
            'category_path' => $item['categoryName'] ?? null,
            'attributes_json' => $item['attributes'] ?? [],
            'last_synced_at' => now(),
            'sync_status' => 'synced',
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function merchantSku(array $item): ?string
    {
        $sku = $item['merchantSku'] ?? $item['sku'] ?? $item['stockCode'] ?? null;

        return $sku !== null ? (string) $sku : null;
    }

    /**
     * Scalar veya {amount, currency} para objesini scalar'a indirger.
     */
    protected function money(mixed $value): float|int|string
    {
        if (is_array($value)) {
            return $value['amount'] ?? 0;
        }

        return $value ?? 0;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function stock(array $item): int
    {
        return (int) ($item['availableStock'] ?? $item['stock'] ?? $item['quantity'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function isActive(array $item): bool
    {
        if (isset($item['isSalable'])) {
            return (bool) $item['isSalable'];
        }
        if (isset($item['status'])) {
            return in_array(strtolower((string) $item['status']), ['active', 'salable', 'onsale'], true);
        }

        return true;
    }
}
