<?php

namespace App\Services\Marketplaces\Contracts;

use App\Support\ServiceResult;

/**
 * Fiyat/stok yazan pazaryeri servisi. SyncDispatcherJob write-dispatch bunu
 * kullanır; her pazaryeri ProductService'i implemente eder.
 */
interface InventoryWriter
{
    /**
     * @param  array<int, array<string, mixed>>  $items  pazaryeri-özel item payload'ları
     * @return ServiceResult<array<string, mixed>>
     */
    public function updatePriceAndInventory(array $items): ServiceResult;
}
