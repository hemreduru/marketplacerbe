<?php

namespace App\Services\Reports;

use App\Models\FinancialDailySummary;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * PR 4.7 — Coğrafya + saat ısı haritası + cohort + LTM trend (Spec 10.10–10.14).
 *
 * Saat/gün ve ay bucketing'i PHP'de yapılır (SQLite/MySQL dialect bağımsızlığı).
 */
class AnalyticsReportService
{
    /**
     * En çok satış yapılan şehirler.
     *
     * @return Collection<int, array{city: string, orders: int, revenue: string}>
     */
    public function topCities(User $user, ReportPeriod $period, int $limit = 10): Collection
    {
        return Order::where('user_id', $user->id)
            ->whereBetween('order_date', [$period->from, $period->to])
            ->whereNotNull('shipping_city')
            ->selectRaw('shipping_city as city, COUNT(*) as orders, COALESCE(SUM(total_amount),0) as revenue')
            ->groupBy('shipping_city')
            ->orderByDesc('orders')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['city' => $r->city, 'orders' => (int) $r->orders, 'revenue' => (string) $r->revenue]);
    }

    /**
     * 7 gün × 24 saat sipariş ısı haritası. [weekday(1=Mon..7=Sun)][hour(0..23)] => count.
     *
     * @return array<int, array<int, int>>
     */
    public function hourlyHeatmap(User $user, ReportPeriod $period): array
    {
        $matrix = [];
        for ($d = 1; $d <= 7; $d++) {
            $matrix[$d] = array_fill(0, 24, 0);
        }

        Order::where('user_id', $user->id)
            ->whereBetween('order_date', [$period->from, $period->to])
            ->whereNotNull('order_date')
            ->select('order_date')
            ->chunk(1000, function ($orders) use (&$matrix) {
                foreach ($orders as $order) {
                    $date = $order->order_date instanceof Carbon ? $order->order_date : Carbon::parse($order->order_date);
                    $matrix[$date->isoWeekday()][(int) $date->format('G')]++;
                }
            });

        return $matrix;
    }

    /**
     * Yeni listelenen ürün cohort'u: ürünün ilk listelendiği ay × o üründen satış adedi.
     *
     * @return Collection<int, array{month: string, products: int, sales_qty: int}>
     */
    public function cohort(User $user, int $months = 6): Collection
    {
        $since = CarbonImmutable::now()->subMonthsNoOverflow($months)->startOfMonth();

        $masters = MasterProduct::where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->get(['id', 'created_at']);

        if ($masters->isEmpty()) {
            return collect();
        }

        $salesByMaster = OrderItem::query()
            ->whereIn('master_product_id', $masters->pluck('id'))
            ->selectRaw('master_product_id, SUM(quantity) as qty')
            ->groupBy('master_product_id')
            ->pluck('qty', 'master_product_id');

        return $masters
            ->groupBy(fn (MasterProduct $m) => $m->created_at->format('Y-m'))
            ->map(fn (Collection $group, string $month) => [
                'month' => $month,
                'products' => $group->count(),
                'sales_qty' => (int) $group->sum(fn (MasterProduct $m) => (int) ($salesByMaster[$m->id] ?? 0)),
            ])
            ->sortKeys()
            ->values();
    }

    /**
     * Son 12 ay net kâr / ciro trendi (FinancialDailySummary'den).
     *
     * @return Collection<int, array{month: string, gross: string, net: string}>
     */
    public function ltmTrend(User $user): Collection
    {
        $credentialIds = $user->marketplaceCredentials()->pluck('id')->all();
        $since = CarbonImmutable::now()->subMonthsNoOverflow(11)->startOfMonth();

        $summaries = FinancialDailySummary::whereIn('user_marketplace_credential_id', $credentialIds)
            ->where('date', '>=', $since->toDateString())
            ->get(['date', 'gross_sales', 'net_profit']);

        $buckets = $summaries->groupBy(fn ($s) => Carbon::parse($s->date)->format('Y-m'));

        $rows = collect();
        for ($i = 11; $i >= 0; $i--) {
            $month = CarbonImmutable::now()->subMonthsNoOverflow($i)->format('Y-m');
            $group = $buckets->get($month, collect());
            $rows->push([
                'month' => $month,
                'gross' => (string) $group->sum(fn ($s) => (float) $s->gross_sales),
                'net' => (string) $group->sum(fn ($s) => (float) $s->net_profit),
            ]);
        }

        return $rows;
    }
}
