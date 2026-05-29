<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\User;
use App\Services\Calculations\ProfitCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Sipariş raporu veri katmanı: filtreli sorgu + sipariş başına net kâr.
 *
 * @phpstan-type OrderFilters array{marketplace_id?: int|string|null, status?: string|null, city?: string|null, q?: string|null}
 */
class OrderReportService
{
    public function __construct(private readonly ProfitCalculator $profit) {}

    /**
     * @param  OrderFilters  $filters
     * @return Builder<Order>
     */
    public function query(User $user, ReportPeriod $period, array $filters = []): Builder
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->whereBetween('order_date', [$period->from, $period->to])
            ->when(! empty($filters['marketplace_id']), fn (Builder $q) => $q->where('marketplace_id', $filters['marketplace_id']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['city']), fn (Builder $q) => $q->where('shipping_city', 'like', '%'.$filters['city'].'%'))
            ->when(! empty($filters['q']), function (Builder $q) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('order_number', 'like', $term)
                        ->orWhere('customer_first_name', 'like', $term)
                        ->orWhere('customer_last_name', 'like', $term);
                });
            })
            ->with(['marketplace'])
            ->withCount('items')
            ->orderByDesc('order_date');
    }

    /**
     * @param  OrderFilters  $filters
     * @return LengthAwarePaginator<Order>
     */
    public function paginate(User $user, ReportPeriod $period, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->query($user, $period, $filters)->paginate($perPage)->withQueryString();
    }

    /**
     * Verilen siparişler için sipariş başına net kâr haritası (id => decimal string).
     *
     * @param  Collection<int, Order>  $orders
     * @return array<int, string>
     */
    public function netProfitMap(Collection $orders): array
    {
        $map = [];
        $orders->loadMissing('items');

        foreach ($orders as $order) {
            $map[$order->id] = $this->profit->forOrder($order)->netProfit;
        }

        return $map;
    }

    /**
     * Toplu yerel statü güncelleme (kullanıcıya scope'lu).
     *
     * Not: Pazaryerine yazma (OrderService::updateStatus) iki katmanlı write
     * guard gerektirir; bu metot yalnızca Cirotik içi statüyü günceller.
     *
     * @param  array<int, int>  $orderIds
     */
    public function bulkUpdateLocalStatus(User $user, array $orderIds, string $status): int
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $orderIds)
            ->update(['status' => $status]);
    }
}
