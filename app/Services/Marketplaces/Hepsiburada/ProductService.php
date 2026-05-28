<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Support\ServiceResult;

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

    public function syncProducts(int $credentialId, ?callable $onProgress = null): void
    {
        $page = 0;
        $size = 50;

        do {
            $result = $this->getProducts(['page' => $page, 'size' => $size]);

            if (! $result->ok) {
                break;
            }

            $content = $result->data['content'] ?? $result->data ?? [];
            if (empty($content)) {
                break;
            }

            $page++;
        } while (! empty($content));
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
