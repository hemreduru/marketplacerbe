<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FinancialDailySummary;
use App\Models\MasterProduct;
use App\Models\OrderItem;
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

        $rows = [];
        foreach ($items as $masterId => $groupedItems) {
            $master = MasterProduct::find($masterId);
            if (! $master) {
                continue;
            }

            $qty = $groupedItems->sum('quantity');
            $revenue = $groupedItems->sum(fn ($i) => (float) $i->price * $i->quantity);
            $vatRate = (float) $master->vat_rate;
            $netRevenue = $revenue > 0 ? round($revenue / (1 + $vatRate / 100), 2) : 0;
            $cost = (float) $master->cost_price * $qty;
            $netProfit = $netRevenue - ($cost / (1 + (float) $master->cost_price_vat_rate / 100));

            $rows[] = [
                'sku' => $master->sku,
                'title' => $master->title,
                'qty' => $qty,
                'revenue' => $revenue,
                'net_revenue' => $netRevenue,
                'cost' => $cost,
                'net_profit' => $netProfit,
                'margin' => $netRevenue > 0 ? round(($netProfit / $netRevenue) * 100, 1) : 0,
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
