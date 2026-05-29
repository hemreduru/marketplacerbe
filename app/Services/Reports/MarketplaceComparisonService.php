<?php

namespace App\Services\Reports;

use App\Models\Marketplace;
use App\Models\MasterProduct;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Calculations\ProfitCalculator;
use Illuminate\Support\Collection;

/**
 * PR 4.4 — Pazaryeri karşılaştırma pivotu (Spec 10.6).
 *
 * Satırlar: SKU (master). Sütunlar: pazaryeri × (satış adedi, net kâr).
 */
class MarketplaceComparisonService
{
    public function __construct(private readonly ProfitCalculator $profit) {}

    /**
     * @return array{marketplaces: Collection<int, Marketplace>, rows: Collection<int, array<string, mixed>>}
     */
    public function pivot(User $user, ReportPeriod $period): array
    {
        $items = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->whereBetween('orders.order_date', [$period->from, $period->to])
            ->whereNotNull('order_items.master_product_id')
            ->with('master')
            ->select('order_items.*', 'orders.marketplace_id as mp_id')
            ->get();

        $marketplaceIds = $items->pluck('mp_id')->unique()->filter()->values();
        $marketplaces = Marketplace::whereIn('id', $marketplaceIds)->get();

        $commissionRate = (float) config('marketplaces.trendyol.commission.default_rate', 15.0);
        $commissionBaseType = config('marketplaces.trendyol.commission.base_type', 'vat_excluded');
        $shippingTariff = config('marketplaces.trendyol.shipping.default_tariff', []);

        // [masterId][mpId] => ['qty' => int, 'profit' => string]
        $grid = [];
        $masters = [];

        foreach ($items as $item) {
            $masterId = $item->master_product_id;
            $mpId = $item->mp_id;
            $masters[$masterId] = $item->master;

            $breakdown = $this->profit->forOrderItem(
                $item,
                $item->master,
                commissionRate: $commissionRate,
                commissionBaseType: $commissionBaseType,
                shippingTariff: $shippingTariff,
            );

            $grid[$masterId][$mpId]['qty'] = ($grid[$masterId][$mpId]['qty'] ?? 0) + $item->quantity;
            $grid[$masterId][$mpId]['profit'] = bcadd($grid[$masterId][$mpId]['profit'] ?? '0', $breakdown->netProfit, 4);
        }

        $rows = collect($grid)->map(function (array $cells, $masterId) use ($masters, $marketplaces) {
            /** @var MasterProduct|null $master */
            $master = $masters[$masterId] ?? null;
            $perMarketplace = [];
            foreach ($marketplaces as $mp) {
                $perMarketplace[$mp->id] = [
                    'qty' => (int) ($cells[$mp->id]['qty'] ?? 0),
                    'profit' => (string) ($cells[$mp->id]['profit'] ?? '0'),
                ];
            }

            return [
                'sku' => $master?->sku,
                'title' => $master?->title,
                'cells' => $perMarketplace,
            ];
        })->values();

        return ['marketplaces' => $marketplaces, 'rows' => $rows];
    }
}
