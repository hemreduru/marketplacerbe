<?php

namespace App\Services\Marketplaces\N11;

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
        return $this->client->call('ProductService', 'GetProductList', [
            'pagingData' => [
                'currentPage' => ($filters['page'] ?? 0),
                'pageSize' => ($filters['size'] ?? 50),
            ],
        ]);
    }

    public function syncProducts(int $credentialId, ?callable $onProgress = null): void
    {
        $page = 0;

        do {
            $result = $this->getProducts(['page' => $page, 'size' => 50]);

            if (! $result->ok) {
                break;
            }

            $products = $result->data['products']['product'] ?? [];
            if (empty($products)) {
                break;
            }

            $page++;
        } while (true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return ServiceResult<array<string, mixed>>
     */
    public function updatePriceAndInventory(array $items): ServiceResult
    {
        return $this->client->call('ProductStockService', 'UpdateStockByStockIdList', [
            'stockItems' => ['stockItem' => $items],
        ]);
    }
}
