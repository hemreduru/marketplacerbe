<?php

namespace App\Services\Contracts;

interface ProductServiceContract
{
    /**
     * Fetch a page of products from the marketplace.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getProducts(array $filters = []): array;

    /**
     * Pull every product for the given credential into local storage.
     */
    public function syncProducts(int $credentialId, ?callable $onProgress = null): void;

    /**
     * Push price and/or stock updates to the marketplace (a live write operation).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function updatePriceAndInventory(array $items): array;
}
