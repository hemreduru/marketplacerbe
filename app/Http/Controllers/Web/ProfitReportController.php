<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Finance\ProfitAggregator;
use App\Services\Finance\ReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfitReportController extends Controller
{
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
