<?php

namespace App\Services\Reports;

use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * PR 4.2 — Stok raporu veri katmanı (Spec 10.4).
 *
 * Satış hızı, tükenme tahmini, ölü stok ve satın alma listesi (PO) hesaplar.
 */
class StockReportService
{
    private const VELOCITY_WINDOW_DAYS = 30;

    private const DEAD_STOCK_DAYS = 365;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(User $user, string $filter = 'all'): Collection
    {
        $masters = MasterProduct::where('user_id', $user->id)->get();
        $masterIds = $masters->pluck('id')->all();

        $sales = $this->salesAggregates($user, $masterIds);
        $listedStock = $this->listedStock($masterIds);

        $now = Carbon::now();

        return $masters->map(function (MasterProduct $master) use ($sales, $listedStock, $now) {
            $sale = $sales->get($master->id);
            $qty30 = (int) ($sale->qty_30 ?? 0);
            $lastSale = $sale?->last_sale ? Carbon::parse($sale->last_sale) : null;

            $velocity = $qty30 > 0 ? round($qty30 / self::VELOCITY_WINDOW_DAYS, 2) : 0.0;
            $daysToDepletion = $velocity > 0 ? (int) floor($master->current_stock / $velocity) : null;

            return [
                'id' => $master->id,
                'sku' => $master->sku,
                'title' => $master->title,
                'current_stock' => (int) $master->current_stock,
                'listed_stock' => (int) ($listedStock[$master->id] ?? 0),
                'critical_threshold' => (int) $master->critical_stock_threshold,
                'velocity' => $velocity,
                'days_to_depletion' => $daysToDepletion,
                'last_sale' => $lastSale,
                'is_critical' => $master->critical_stock_threshold > 0 && $master->current_stock <= $master->critical_stock_threshold,
                'is_zero' => $master->current_stock <= 0,
                'is_dead' => $lastSale === null || $lastSale->lt($now->copy()->subDays(self::DEAD_STOCK_DAYS)),
            ];
        })->filter(fn (array $row) => match ($filter) {
            'critical' => $row['is_critical'],
            'zero' => $row['is_zero'],
            'dead' => $row['is_dead'],
            default => true,
        })->values();
    }

    /**
     * Kritik stok altındaki ürünler için önerilen satın alma adetleri (30 günlük talep).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function purchaseOrder(User $user): Collection
    {
        return $this->rows($user, 'all')
            ->filter(fn (array $row) => $row['is_critical'] || $row['is_zero'])
            ->map(function (array $row) {
                $targetCover = (int) ceil($row['velocity'] * self::VELOCITY_WINDOW_DAYS);
                $suggested = max($targetCover - $row['current_stock'], max($row['critical_threshold'], 1));

                return [
                    'sku' => $row['sku'],
                    'title' => $row['title'],
                    'current_stock' => $row['current_stock'],
                    'velocity' => $row['velocity'],
                    'suggested_qty' => $suggested,
                ];
            })->values();
    }

    /**
     * @param  array<int, int>  $masterIds
     * @return Collection<int, object>
     */
    private function salesAggregates(User $user, array $masterIds): Collection
    {
        if (empty($masterIds)) {
            return collect();
        }

        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->whereIn('order_items.master_product_id', $masterIds)
            ->selectRaw('order_items.master_product_id as master_product_id')
            ->selectRaw('SUM(CASE WHEN orders.order_date >= ? THEN order_items.quantity ELSE 0 END) as qty_30', [Carbon::now()->subDays(self::VELOCITY_WINDOW_DAYS)])
            ->selectRaw('MAX(orders.order_date) as last_sale')
            ->groupBy('order_items.master_product_id')
            ->get()
            ->keyBy('master_product_id');
    }

    /**
     * @param  array<int, int>  $masterIds
     * @return array<int, int>
     */
    private function listedStock(array $masterIds): array
    {
        if (empty($masterIds)) {
            return [];
        }

        return MarketplaceListing::whereIn('master_product_id', $masterIds)
            ->selectRaw('master_product_id, SUM(listed_stock) as total')
            ->groupBy('master_product_id')
            ->pluck('total', 'master_product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
