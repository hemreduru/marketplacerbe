<?php

namespace App\Services\Marketplaces\Amazon;

use App\Support\ServiceResult;

class OrderService
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Orders API v2026-01-01 — get recent orders (polling).
     */
    public function getOrders(?string $createdAfter = null): ServiceResult
    {
        $query = [
            'MarketplaceIds' => 'A33AVAJ2PDY3EV',
            'CreatedAfter' => $createdAfter ?? now()->subHours(6)->toIso8601String(),
        ];

        return $this->client->request('GET', '/orders/v0/orders', [], $query);
    }
}
