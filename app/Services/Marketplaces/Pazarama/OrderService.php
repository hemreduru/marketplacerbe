<?php

namespace App\Services\Marketplaces\Pazarama;

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
        return $this->client->get('/orders', $filters);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function updateStatus(int $packageId, string $status): ServiceResult
    {
        return ServiceResult::fail('not_implemented', 'Pazarama siparis guncellemesi henuz implemente edilmedi.');
    }

    /**
     * @return array<string, int>
     */
    public function syncOrders(int $marketplaceId, int $userId, ?int $startYear = null, ?callable $onProgress = null): array
    {
        return ['created' => 0, 'updated' => 0, 'failed' => 0];
    }
}
