<?php

namespace App\Services\Reports;

use App\Models\Claim;
use App\Models\MasterProduct;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * PR 4.3 — İade analiz raporu veri katmanı (Spec 10.5).
 */
class ReturnReportService
{
    /**
     * @return array{sales_qty: int, return_qty: int, return_rate: float, return_cost: string}
     */
    public function summary(User $user, ReportPeriod $period): array
    {
        $credentialIds = $user->marketplaceCredentials()->pluck('id')->all();

        $salesQty = (int) OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->whereBetween('orders.order_date', [$period->from, $period->to])
            ->sum('order_items.quantity');

        $claims = Claim::whereIn('user_marketplace_credential_id', $credentialIds)
            ->whereBetween('claim_date', [$period->from, $period->to]);

        $returnQty = (int) (clone $claims)->sum('item_count');
        $returnCost = (string) ((clone $claims)->sum('refund_amount') ?: '0');

        $returnRate = $salesQty > 0 ? round(($returnQty / $salesQty) * 100, 2) : 0.0;

        return [
            'sales_qty' => $salesQty,
            'return_qty' => $returnQty,
            'return_rate' => $returnRate,
            'return_cost' => $returnCost,
        ];
    }

    /**
     * İade nedenine göre kırılım.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byReason(User $user, ReportPeriod $period): Collection
    {
        $credentialIds = $user->marketplaceCredentials()->pluck('id')->all();

        return Claim::whereIn('user_marketplace_credential_id', $credentialIds)
            ->whereBetween('claim_date', [$period->from, $period->to])
            ->selectRaw('COALESCE(return_reason, ?) as reason, COUNT(*) as claim_count, COALESCE(SUM(item_count),0) as qty, COALESCE(SUM(refund_amount),0) as refund', ['other'])
            ->groupBy('reason')
            ->orderByDesc('claim_count')
            ->get()
            ->map(fn ($r) => [
                'reason' => $r->reason,
                'claim_count' => (int) $r->claim_count,
                'qty' => (int) $r->qty,
                'refund' => (string) $r->refund,
            ]);
    }

    /**
     * SKU bazlı satış vs iade adedi ve iade oranı.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function bySku(User $user, ReportPeriod $period): Collection
    {
        $salesBySku = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->whereBetween('orders.order_date', [$period->from, $period->to])
            ->whereNotNull('order_items.master_product_id')
            ->selectRaw('order_items.master_product_id, SUM(order_items.quantity) as sales_qty')
            ->groupBy('order_items.master_product_id')
            ->pluck('sales_qty', 'master_product_id');

        // İade adedi: claim.item_count, siparişteki SKU'lara atfedilir. Claim'ler SKU
        // bazlı detay tutmadığı için, bir siparişte birden çok master varsa item_count
        // her birine atfedilir (tek-SKU siparişlerde — TR'de baskın durum — birebir doğru).
        $returnsBySku = $this->returnedQtyBySku($user, $period);

        $masterIds = $salesBySku->keys()->merge($returnsBySku->keys())->unique();
        if ($masterIds->isEmpty()) {
            return collect();
        }

        $masters = MasterProduct::whereIn('id', $masterIds)->get()->keyBy('id');

        return $masterIds->map(function ($masterId) use ($salesBySku, $returnsBySku, $masters) {
            $master = $masters->get($masterId);
            $sales = (int) ($salesBySku[$masterId] ?? 0);
            $returns = (int) ($returnsBySku[$masterId] ?? 0);

            return [
                'sku' => $master?->sku,
                'title' => $master?->title,
                'sales_qty' => $sales,
                'return_qty' => $returns,
                'return_rate' => $sales > 0 ? round(($returns / $sales) * 100, 2) : 0.0,
            ];
        })->sortByDesc('return_rate')->values();
    }

    /**
     * Claim.item_count'u siparişteki master ürünlere atfederek SKU bazlı iade adedi.
     *
     * @return Collection<int, int>
     */
    private function returnedQtyBySku(User $user, ReportPeriod $period): Collection
    {
        $credentialIds = $user->marketplaceCredentials()->pluck('id')->all();

        $claims = Claim::whereIn('user_marketplace_credential_id', $credentialIds)
            ->whereBetween('claim_date', [$period->from, $period->to])
            ->get(['order_number', 'item_count']);

        if ($claims->isEmpty()) {
            return collect();
        }

        $orderMasters = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->whereIn('orders.order_number', $claims->pluck('order_number')->unique())
            ->whereNotNull('order_items.master_product_id')
            ->get(['orders.order_number as order_number', 'order_items.master_product_id as master_product_id'])
            ->groupBy('order_number');

        $totals = [];
        foreach ($claims as $claim) {
            $masters = ($orderMasters[$claim->order_number] ?? collect())
                ->pluck('master_product_id')->unique();
            foreach ($masters as $masterId) {
                $totals[$masterId] = ($totals[$masterId] ?? 0) + (int) $claim->item_count;
            }
        }

        return collect($totals);
    }
}
