<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Calculations\NetVatLiability;
use App\Services\Calculations\VatCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * PR 4.5 — KDV & Vergi raporu veri katmanı (Spec 10.7).
 *
 * Aylık KDV mahsuplaşması: satış KDV (borç) vs alış/komisyon/kargo/platform KDV (alacak).
 */
class VatReportService
{
    public function __construct(
        private readonly NetVatLiability $netVat,
        private readonly VatCalculator $vat,
    ) {}

    /**
     * @return array{from: string, to: string, rows: Collection<int, array<string, mixed>>, totals: array<string, string>, order_count: int}
     */
    public function monthly(User $user, int $year, int $month): array
    {
        $from = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $to = $from->endOfMonth();

        $commissionRate = (float) config('marketplaces.trendyol.commission.default_rate', 15.0);
        $commissionVatRate = (float) config('marketplaces.trendyol.vat_rates.commission', 20);
        $saleVatDefault = (float) config('marketplaces.trendyol.vat_rates.sale_default', 20);
        $platformFeeExcl = (float) config('marketplaces.trendyol.platform_service_fee.standard.amount_excl_vat', 8.49);
        $platformFeeVatRate = (float) config('marketplaces.trendyol.platform_service_fee.standard.vat_rate', 20);

        $items = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->whereBetween('orders.order_date', [$from, $to])
            ->with('master')
            ->select('order_items.*')
            ->get();

        $grid = [];
        $totals = ['sale_vat' => '0', 'purchase_vat' => '0', 'commission_vat' => '0', 'shipping_vat' => '0', 'net' => '0'];

        foreach ($items as $item) {
            $master = $item->master;
            $saleIncVat = bcmul((string) $item->price, (string) $item->quantity, 4);
            $saleVatRate = $master?->vat_rate ?? $saleVatDefault;
            $costIncVat = $master ? bcmul((string) $master->cost_price, (string) $item->quantity, 4) : '0';
            $costVatRate = $master?->cost_price_vat_rate ?? $saleVatDefault;

            $commissionAmount = $item->commission_amount > 0
                ? (string) $item->commission_amount
                : bcmul($this->vat->excludeVat($saleIncVat, $saleVatRate), (string) ($commissionRate / 100), 4);

            $shippingIncVat = (string) ($item->shipping_cost ?? 0);

            $vatResult = $this->netVat->calculate(
                saleIncVat: $saleIncVat,
                saleVatRate: $saleVatRate,
                costIncVat: $costIncVat,
                costVatRate: $costVatRate,
                commissionAmount: $commissionAmount,
                commissionVatRate: $commissionVatRate,
                shippingIncVat: $shippingIncVat,
                shippingVatRate: $saleVatDefault,
                platformFeeExclVat: 0,
                platformFeeVatRate: $platformFeeVatRate,
            );

            $key = $master?->id ?? 'item-'.$item->id;
            $grid[$key]['sku'] = $master?->sku ?? $item->merchant_sku;
            $grid[$key]['title'] = $master?->title ?? $item->product_name;
            $grid[$key]['sale_vat'] = bcadd($grid[$key]['sale_vat'] ?? '0', $vatResult['sale_vat'], 4);
            $grid[$key]['purchase_vat'] = bcadd($grid[$key]['purchase_vat'] ?? '0', $vatResult['purchase_vat_refund'], 4);
            $grid[$key]['commission_vat'] = bcadd($grid[$key]['commission_vat'] ?? '0', $vatResult['commission_vat_refund'], 4);
            $grid[$key]['shipping_vat'] = bcadd($grid[$key]['shipping_vat'] ?? '0', $vatResult['shipping_vat_refund'], 4);

            $totals['sale_vat'] = bcadd($totals['sale_vat'], $vatResult['sale_vat'], 4);
            $totals['purchase_vat'] = bcadd($totals['purchase_vat'], $vatResult['purchase_vat_refund'], 4);
            $totals['commission_vat'] = bcadd($totals['commission_vat'], $vatResult['commission_vat_refund'], 4);
            $totals['shipping_vat'] = bcadd($totals['shipping_vat'], $vatResult['shipping_vat_refund'], 4);
        }

        // Platform hizmet bedeli KDV'si: sipariş başına 1 kez (item başına değil)
        $orderCount = Order::where('user_id', $user->id)->whereBetween('order_date', [$from, $to])->count();
        $platformFeeIncVat = $this->vat->includeVat((string) bcmul((string) $platformFeeExcl, (string) $orderCount, 4), $platformFeeVatRate);
        $platformFeeVatRefund = $this->vat->vatAmount($platformFeeIncVat, $platformFeeVatRate);

        $rows = collect($grid)->map(function (array $row) {
            $row['net'] = bcsub(
                $row['sale_vat'],
                bcadd($row['purchase_vat'], bcadd($row['commission_vat'], $row['shipping_vat'], 4), 4),
                4
            );

            return $row;
        })->sortByDesc('sale_vat')->values();

        $totals['platform_fee_vat'] = bcround($platformFeeVatRefund, 4);
        $totalRefunds = bcadd(
            bcadd($totals['purchase_vat'], $totals['commission_vat'], 4),
            bcadd($totals['shipping_vat'], $totals['platform_fee_vat'], 4),
            4
        );
        $totals['net'] = bcsub($totals['sale_vat'], $totalRefunds, 4);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows,
            'totals' => $totals,
            'order_count' => $orderCount,
        ];
    }
}
