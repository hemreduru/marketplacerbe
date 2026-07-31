<?php

namespace App\Http\Controllers\Web;

use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\OrderItem;
use App\Services\Calculations\ProfitCalculator;
use App\Services\Finance\ProfitAggregator;
use App\Services\Finance\ProfitContextFactory;
use App\Services\Finance\ReconciliationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfitReportController extends Controller
{
    /**
     * Kampanya kâr simülatörü: seçilen ürün + hipotetik satış fiyatıyla
     * ProfitCalculator'ı istek anında çalıştırır (what-if). Yeni motor yok;
     * mevcut forOrderItem'ı geçici (persist edilmemiş) bir OrderItem ile kullanır.
     */
    public function simulator(Request $request, ProfitCalculator $calculator, ProfitContextFactory $contexts): View
    {
        $user = Auth::user();
        $products = MasterProduct::where('user_id', $user->id)
            ->orderBy('title')
            ->get(['id', 'title', 'sku', 'cost_price', 'current_price']);

        $result = null;
        $selected = null;

        if ($request->filled('master_product_id') && $request->filled('price')) {
            $validated = $request->validate([
                'master_product_id' => ['required', 'integer'],
                'price' => ['required', 'numeric', 'min:0'],
            ]);

            $selected = MasterProduct::where('user_id', $user->id)->find($validated['master_product_id']);

            if ($selected) {
                $item = new OrderItem;
                $item->price = $validated['price'];
                $item->quantity = 1;
                $item->commission_rate = 0;
                $item->merchant_sku = $selected->sku;

                $result = $calculator->forOrderItem($item, $selected, $contexts->forMarketplace('trendyol'));
            }
        }

        return view('reports.simulator', [
            'products' => $products,
            'result' => $result,
            'selected' => $selected,
        ]);
    }

    /**
     * SKU bazlı kâr/zarar raporu — kalem bazlı kâr defterinden (order_item_financials)
     * okunur, istek anında yeniden hesap YOK.
     */
    public function skuProfit(Request $request, ProfitAggregator $aggregator)
    {
        $user = Auth::user();
        $period = $request->get('period', 'this_month');
        [$from, $to] = $this->resolvePeriod($period);

        $rows = $aggregator->skuTable($user, $from, $to);

        return view('reports.sku-profit', [
            'rows' => $rows,
            'period' => $period,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * SKU kâr raporunu Excel (xlsx) veya PDF olarak indirir.
     */
    public function skuProfitExport(Request $request, string $format, ProfitAggregator $aggregator): BinaryFileResponse|Response
    {
        $user = Auth::user();
        $period = $request->get('period', 'this_month');
        [$from, $to] = $this->resolvePeriod($period);

        $headings = [
            'SKU', __('reports.product'), __('reports.quantity'),
            __('reports.net_revenue'), __('reports.cost'),
            __('reports.net_profit'), __('reports.margin'),
        ];

        $rows = $aggregator->skuTable($user, $from, $to)
            ->map(fn (array $row): array => [
                $row['sku'],
                $row['title'],
                $row['items'],
                $row['net_revenue'],
                $row['cogs'],
                $row['net_profit'],
                $row['margin'].'%',
            ])
            ->values()
            ->all();

        $filename = 'sku-profit-'.$from.'_'.$to;

        if ($format === 'pdf') {
            return Pdf::loadView('exports.table', [
                'title' => __('reports.sku_profit'),
                'period' => $from.' — '.$to,
                'headings' => $headings,
                'rows' => $rows,
            ])->download($filename.'.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), $filename.'.xlsx');
    }

    /**
     * Mutabakat raporu — tahmini ↔ settlement gerçeği sapması (portföy + SKU).
     */
    public function reconciliation(Request $request, ReconciliationService $reconciliation)
    {
        $user = Auth::user();
        $period = $request->get('period', 'this_month');
        [$from, $to] = $this->resolvePeriod($period);

        return view('reports.reconciliation', [
            'portfolio' => $reconciliation->portfolioDeviation($user, $from, $to),
            'bySku' => $reconciliation->bySku($user, $from, $to),
            'period' => $period,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * "Para iade" ekranı — geri alınabilir fee anomalileri (fazla komisyon,
     * yanlış desi kargo, ceza satırları) kanıt referanslı listelenir.
     */
    public function refundRecovery(Request $request, ReconciliationService $reconciliation): View
    {
        $user = Auth::user();
        $period = $request->get('period', 'this_month');
        [$from, $to] = $this->resolvePeriod($period);

        return view('reports.refund-recovery', [
            'anomalies' => $reconciliation->anomalies($user, $from, $to),
            'period' => $period,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Dönem etiketini [from, to] tarih aralığına çözer.
     *
     * @return array{0: string, 1: string}
     */
    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'this_week' => [now()->startOfWeek()->toDateString(), now()->toDateString()],
            'this_year' => [now()->startOfYear()->toDateString(), now()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        };
    }
}
