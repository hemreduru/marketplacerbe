<?php

namespace App\Services\Marketplaces\Hepsiburada;

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
        return $this->client->get('/order/api/orders', $filters);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function updateStatus(int $packageId, string $status): ServiceResult
    {
        return $this->client->put('/order/api/orders/'.$packageId, ['status' => $status]);
    }

    /**
     * @return array<string, int>
     */
    public function syncOrders(int $marketplaceId, int $userId, ?int $startYear = null, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $page = 0;

        do {
            $result = $this->getOrders(['page' => $page, 'size' => 50]);

            if (! $result->ok) {
                break;
            }

            $content = $result->data['content'] ?? $result->data ?? [];
            if (empty($content)) {
                break;
            }

            $page++;
        } while (! empty($content));

        return $stats;
    }
}
