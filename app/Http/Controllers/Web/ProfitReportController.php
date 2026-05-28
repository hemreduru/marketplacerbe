<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FinancialDailySummary;
use App\Models\MasterProduct;
use App\Models\OrderItem;
use App\Services\Calculations\AdAllocator;
use App\Services\Calculations\CommissionCalculator;
use App\Services\Calculations\PackagingCostCalculator;
use App\Services\Calculations\ProfitCalculator;
use App\Services\Calculations\ReturnCostEstimator;
use App\Services\Calculations\ServiceFeeCalculator;
use App\Services\Calculations\ShippingCostCalculator;
use App\Services\Calculations\VatCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfitReportController extends Controller
{
    public function skuProfit(Request $request)
    {
        $user = Auth::user();

        $period = $request->get('period', 'this_month');
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $masterIds = MasterProduct::where('user_id', $user->id)->pluck('id');

        $items = OrderItem::whereIn('master_product_id', $masterIds)
            ->whereHas('order', fn ($q) => $q->whereBetween('order_date', [$from, $to]))
            ->with('master', 'order')
            ->get()
            ->groupBy('master_product_id');

        $vat = new VatCalculator;
        $calculator = new ProfitCalculator(
            vat: $vat,
            commission: new CommissionCalculator($vat),
            serviceFee: new ServiceFeeCalculator($vat),
            shipping: new ShippingCostCalculator($vat),
            returnCost: new ReturnCostEstimator,
            packaging: new PackagingCostCalculator($vat),
            ads: new AdAllocator,
        );

        $commissionRate = (float) config('marketplaces.trendyol.commission.default_rate', 15.0);
        $commissionBaseType = config('marketplaces.trendyol.commission.base_type', 'vat_excluded');
        $shippingTariff = config('marketplaces.trendyol.shipping.default_tariff', []);

        $rows = [];
        foreach ($items as $masterId => $groupedItems) {
            $firstItem = $groupedItems->first();
            $master = $firstItem?->master;
            if (! $master) {
                continue;
            }

            $qty = 0;
            $revenue = '0.0000';
            $netRevenue = '0.0000';
            $netProfit = '0.0000';
            $costOfGoodsTotal = '0.0000';

            foreach ($groupedItems as $item) {
                $qty += $item->quantity;
                $revenue = bcadd($revenue, bcmul((string) $item->price, (string) $item->quantity, 4), 4);

                $breakdown = $calculator->forOrderItem(
                    $item,
                    $master,
                    commissionRate: $commissionRate,
                    commissionBaseType: $commissionBaseType,
                    shippingTariff: $shippingTariff,
                );

                $netRevenue = bcadd($netRevenue, $breakdown->netRevenue, 6);
                $netProfit = bcadd($netProfit, $breakdown->netProfit, 6);
                $costOfGoodsTotal = bcadd($costOfGoodsTotal, $breakdown->deductions['cost_of_goods'], 6);
            }

            $margin = bccomp($netRevenue, '0.0000', 6) === 0
                ? '0.00'
                : bcround(bcmul(bcdiv($netProfit, $netRevenue, 6), '100', 6), 2);

            $rows[] = [
                'sku' => $master->sku,
                'title' => $master->title,
                'qty' => $qty,
                'revenue' => $revenue,
                'net_revenue' => bcround($netRevenue, 4),
                'cost' => bcround($costOfGoodsTotal, 4),
                'net_profit' => bcround($netProfit, 4),
                'margin' => $margin,
            ];
        }

        return view('reports.sku-profit', [
            'rows' => $rows,
            'period' => $period,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function reconciliation(Request $request)
    {
        $user = Auth::user();
        $credentialIds = $user->marketplaceCredentials()->pluck('id');

        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $estimated = FinancialDailySummary::whereIn('user_marketplace_credential_id', $credentialIds)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(net_profit), 0) as net, COALESCE(SUM(gross_sales), 0) as gross, COALESCE(SUM(commission), 0) as commission')
            ->first();

        return view('reports.reconciliation', [
            'estimated' => [
                'net' => (float) ($estimated->net ?? 0),
                'gross' => (float) ($estimated->gross ?? 0),
                'commission' => (float) ($estimated->commission ?? 0),
            ],
            'from' => $from,
            'to' => $to,
        ]);
    }
}
