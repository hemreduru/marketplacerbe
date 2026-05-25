<?php

namespace App\Services\Contracts;

interface OrderServiceContract
{
    /**
     * Fetch a page of orders from the marketplace.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getOrders(array $filters = []): array;

    /**
     * Push an order/package status change to the marketplace.
     *
     * @return array<string, mixed>
     */
    public function updateStatus(int $packageId, string $status): array;

    /**
     * Pull orders for the given marketplace/user into local storage.
     *
     * @return array<string, mixed>
     */
    public function syncOrders(int $marketplaceId, int $userId, ?int $startYear = null, ?callable $onProgress = null): array;
}
