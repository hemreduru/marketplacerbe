<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTrendyolFinancialsJob;
use App\Models\FinancialDailySummary;
use App\Models\FinancialTransaction;
use App\Services\MarketplaceManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialController extends Controller
{
    public function __construct(private MarketplaceManager $marketplace) {}

    /**
     * Display the financial dashboard with summary stats and charts.
     */
    public function index(Request $request)
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return redirect()->route('marketplace.settings')
                ->with('warning', __('common.please_connect_trendyol'));
        }

        $endDate = $request->input('end_date', date('Y-m-d'));
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-29 days')));

        $dailySummaries = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        // Calculate Totals for Summary Cards (Current Period)
        $currentStats = [
            'gross_sales' => $dailySummaries->sum('gross_sales'),
            'net_profit' => $dailySummaries->sum('net_profit'),
            'commission' => $dailySummaries->sum('commission'),
            'total_expense' => $dailySummaries->sum('commission') + $dailySummaries->sum('shipping_cost') + $dailySummaries->sum('platform_expense') + $dailySummaries->sum('other_expense'),
        ];

        // Calculate Previous Period Dates
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
        $prevEndDate = date('Y-m-d', strtotime($startDate.' -1 day'));
        $prevStartDate = date('Y-m-d', strtotime($prevEndDate.' -'.$daysDiff.' days'));

        // Fetch Previous Period Data
        $prevSummaries = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)
            ->whereBetween('date', [$prevStartDate, $prevEndDate])
            ->get();

        $prevStats = [
            'gross_sales' => $prevSummaries->sum('gross_sales'),
            'net_profit' => $prevSummaries->sum('net_profit'),
            'commission' => $prevSummaries->sum('commission'),
            'total_expense' => $prevSummaries->sum('commission') + $prevSummaries->sum('shipping_cost') + $prevSummaries->sum('platform_expense') + $prevSummaries->sum('other_expense'),
        ];

        // Calculate Growth Percentages
        $growth = [];
        foreach ($currentStats as $key => $value) {
            if ($prevStats[$key] > 0) {
                $growth[$key] = (($value - $prevStats[$key]) / $prevStats[$key]) * 100;
            } else {
                $growth[$key] = $value > 0 ? 100 : 0;
            }
        }

        // Calculate Advanced Metrics
        $profitMargin = $currentStats['gross_sales'] > 0 ? ($currentStats['net_profit'] / $currentStats['gross_sales']) * 100 : 0;
        $avgDailySales = $daysDiff >= 0 ? $currentStats['gross_sales'] / ($daysDiff + 1) : 0;

        $summaryStats = array_merge($currentStats, [
            'growth' => $growth,
            'profit_margin' => $profitMargin,
            'avg_daily_sales' => $avgDailySales,
        ]);

        // Calculate Detailed Expense Breakdown from Transactions
        $expenseBreakdown = [
            'commission' => $dailySummaries->sum('commission'),
            'shipping' => 0,
            'platform' => 0,
            'intl_ops' => 0,
            'intl_service' => 0,
            'penalty' => 0,
            'other' => 0,
        ];

        $deductions = FinancialTransaction::where('user_marketplace_credential_id', $credential->id)
            ->where('transaction_type', 'Deduction')
            ->whereBetween('transaction_date', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->get();

        foreach ($deductions as $d) {
            $desc = $d->description ?? '';
            $cat = $this->classifyDeduction($desc);
            $val = abs($d->amount); // Deductions are negative in DB

            // Check for Shipping (Kargo)
            $meta = $d->metadata ?? [];
            $txType = mb_strtolower($meta['transactionType'] ?? '', 'UTF-8');

            if ($txType === 'kargo faturası' || $txType === 'kargo fatura') {
                $expenseBreakdown['shipping'] += $val;
            } elseif ($cat === 'platform') {
                $expenseBreakdown['platform'] += $val;
            } elseif ($cat === 'intl_ops') {
                $expenseBreakdown['intl_ops'] += $val;
            } elseif ($cat === 'intl_service') {
                $expenseBreakdown['intl_service'] += $val;
            } elseif ($cat === 'penalty') {
                $expenseBreakdown['penalty'] += $val;
            } else {
                $expenseBreakdown['other'] += $val;
            }
        }

        // Prepare Chart Data
        $chartData = [
            'dates' => $dailySummaries->pluck('date')->map(fn ($d) => date('d M', strtotime($d)))->toArray(),
            'sales' => $dailySummaries->pluck('gross_sales')->toArray(),
            'net' => $dailySummaries->pluck('net_profit')->toArray(),
            'expenses' => $expenseBreakdown,
        ];

        // Get oldest transaction date for "All Time" filter
        $oldestTx = FinancialTransaction::where('user_marketplace_credential_id', $credential->id)
            ->orderBy('transaction_date', 'asc')
            ->first();
        $minDate = $oldestTx ? date('Y-m-d', strtotime($oldestTx->transaction_date)) : date('Y-m-d', strtotime('-5 years'));

        // Format dates for display (Localized)
        Carbon::setLocale(app()->getLocale());
        $prevPeriodLabel = Carbon::parse($prevStartDate)->translatedFormat('d M').' - '.Carbon::parse($prevEndDate)->translatedFormat('d M');

        return view('financial.dashboard', [
            'summaryStats' => $summaryStats,
            'chartData' => $chartData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'minDate' => $minDate,
            'prevPeriodLabel' => $prevPeriodLabel,
        ]);
    }

    /**
     * Trigger a manual sync of financial data.
     */
    public function sync()
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
        }

        SyncTrendyolFinancialsJob::dispatch($credential->id);

        return response()->json(['success' => true, 'message' => __('common.sync_started')]);
    }

    /**
     * Helper to calculate stats from DB records.
     */
    private function getStatsFromDb($credentialId, $startDate, $endDate)
    {
        $summaries = FinancialDailySummary::where('user_marketplace_credential_id', $credentialId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $stats = [
            'gross' => 0, 'commission' => 0, 'shipping' => 0,
            'platform' => 0, 'intl_ops' => 0, 'intl_service' => 0,
            'penalty_other' => 0, 'net' => 0, 'orders' => 0, 'qty' => 0,
        ];

        foreach ($summaries as $s) {
            $stats['gross'] += $s->gross_sales;
            $stats['commission'] += $s->commission;
            $stats['shipping'] += $s->shipping_cost;
            $stats['platform'] += $s->platform_expense;
            $stats['penalty_other'] += $s->other_expense;
            $stats['net'] += $s->net_profit;
            $stats['orders'] += $s->order_count;
            $stats['qty'] += $s->item_count;
        }

        return $stats;
    }

    /**
     * Return empty stats structure.
     */
    private function emptyStats()
    {
        return [
            'gross' => 0, 'commission' => 0, 'shipping' => 0,
            'platform' => 0, 'intl_ops' => 0, 'intl_service' => 0,
            'penalty_other' => 0, 'net' => 0, 'orders' => 0, 'qty' => 0,
        ];
    }

    /**
     * Classify deduction description into categories.
     */
    private function classifyDeduction(string $description): string
    {
        $d = mb_strtolower(strtr($description, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');

        $platform = ['platform hizmet', 'platform hizmeti', 'hizmet bedeli', 'platform bedeli', 'phb', 'p.h.b'];
        foreach ($platform as $k) {
            if (strpos($d, $k) !== false) {
                return 'platform';
            }
        }

        $intlOps = ['yurtdışı operasyon', 'yurt dışı operasyon', 'yd operasyon'];
        foreach ($intlOps as $k) {
            if (strpos($d, $k) !== false) {
                return 'intl_ops';
            }
        }

        $intlSrv = ['uluslararası hizmet', 'international service'];
        foreach ($intlSrv as $k) {
            if (strpos($d, $k) !== false) {
                return 'intl_service';
            }
        }

        $pen = ['ceza', 'penalty', 'gecikme'];
        foreach ($pen as $k) {
            if (strpos($d, $k) !== false) {
                return 'penalty';
            }
        }

        return 'other';
    }
}
