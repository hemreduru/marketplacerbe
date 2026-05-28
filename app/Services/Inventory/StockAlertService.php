<?php

namespace App\Services\Inventory;

use App\Models\MasterProduct;
use Illuminate\Support\Collection;

class StockAlertService
{
    /**
     * Kritik stok altındaki ürünleri döner.
     *
     * @return Collection<int, MasterProduct>
     */
    public function getCriticalStockProducts(int $userId): Collection
    {
        return MasterProduct::where('user_id', $userId)
            ->where('stock_alert_enabled', true)
            ->where('critical_stock_threshold', '>', 0)
            ->whereRaw('current_stock <= critical_stock_threshold')
            ->orderBy('current_stock')
            ->get();
    }

    /**
     * Kritik stok altına düşen ürün sayısı.
     */
    public function criticalStockCount(int $userId): int
    {
        return MasterProduct::where('user_id', $userId)
            ->where('stock_alert_enabled', true)
            ->where('critical_stock_threshold', '>', 0)
            ->whereRaw('current_stock <= critical_stock_threshold')
            ->count();
    }
}
