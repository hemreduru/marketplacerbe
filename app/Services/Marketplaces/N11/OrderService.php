<?php

namespace App\Services\Marketplaces\N11;

use App\Support\ServiceResult;

class OrderService
{
    public function __construct(protected Client $client) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return ServiceResult<array<string, mixed>>
     */
    public function getOrders(array $filters = []): ServiceResult
    {
        return $this->client->call('OrderService', 'OrderList', [
            'searchData' => [
                'period' => [
                    'startDate' => $filters['startDate'] ?? null,
                    'endDate' => $filters['endDate'] ?? null,
                ],
            ],
            'pagingData' => [
                'currentPage' => ($filters['page'] ?? 0),
                'pageSize' => ($filters['size'] ?? 50),
            ],
        ]);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function updateStatus(int $packageId, string $status): ServiceResult
    {
        return ServiceResult::fail('not_implemented', 'N11 sipariş durum güncellemesi henüz implemente edilmedi.');
    }

    /**
     * @return array<string, int>
     */
    public function syncOrders(int $marketplaceId, int $userId, ?int $startYear = null, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        return $stats;
    }
}
