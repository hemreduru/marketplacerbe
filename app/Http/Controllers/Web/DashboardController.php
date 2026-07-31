<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FinancialDailySummary;
use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItemFinancial;
use App\Services\MarketplaceManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private MarketplaceManager $marketplace) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $credential = $this->marketplace->credentialFor($user);

        if (! $credential) {
            return view('dashboard', ['user' => $user, 'hasCredential' => false]);
        }

        $period = $request->get('period', 'month');
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->resolvePeriod($period);

        $currentSummary = $this->aggregateSummary($credential->id, $currentStart, $currentEnd);
        $previousSummary = $this->aggregateSummary($credential->id, $previousStart, $previousEnd);

        $currentOrders = Order::where('user_id', $user->id)
            ->whereBetween('order_date', [$currentStart, $currentEnd]);
        $previousOrders = Order::where('user_id', $user->id)
            ->whereBetween('order_date', [$previousStart, $previousEnd]);

        $criticalStock = MasterProduct::where('user_id', $user->id)
            ->where('current_stock', '<=', 5)
            ->count();

        $zeroCostCount = MasterProduct::where('user_id', $user->id)
            ->where('cost_price', 0)
            ->count();

        $kpis = [
            'revenue' => $currentSummary['gross_sales'],
            'revenue_change' => $this->percentChange($currentSummary['gross_sales'], $previousSummary['gross_sales']),
            'net_profit' => $currentSummary['net_profit'],
            'profit_change' => $this->percentChange($currentSummary['net_profit'], $previousSummary['net_profit']),
            'order_count' => $currentOrders->count(),
            'order_change' => $this->percentChange($currentOrders->count(), $previousOrders->count()),
            'margin' => $currentSummary['gross_sales'] > 0
                ? round(($currentSummary['net_profit'] / $currentSummary['gross_sales']) * 100, 1)
                : 0,
            'return_rate' => 0,
            // TACoS = reklam harcaması / ciro. net_profit zaten reklam-dahil
            // (true_net_profit); bu kart reklamın ciroya oranını görünür kılar.
            'ad_spend' => $currentSummary['ad_spend'],
            'tacos' => $currentSummary['gross_sales'] > 0
                ? round(($currentSummary['ad_spend'] / $currentSummary['gross_sales']) * 100, 1)
                : 0,
            'critical_stock' => $criticalStock,
            'waiting_questions' => $credential->questions()->where('status', 'WAITING_FOR_ANSWER')->count(),
            'low_stock' => MasterProduct::where('user_id', $user->id)->whereBetween('current_stock', [1, 5])->count(),
            'out_of_stock' => MasterProduct::where('user_id', $user->id)->where('current_stock', '<=', 0)->count(),
            'waiting_orders' => Order::where('user_id', $user->id)->where('status', 'Created')->count(),
        ];

        $trend = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->orderBy('date')
            ->get();

        $chartData = [
            'dates' => $trend->map(fn ($s) => Carbon::parse($s->date)->translatedFormat('d M'))->toArray(),
            'sales' => $trend->map(fn ($s) => (float) $s->gross_sales)->toArray(),
            'net' => $trend->map(fn ($s) => (float) $s->true_net_profit ?: (float) $s->net_profit)->toArray(),
        ];

        $listingsSummary = MarketplaceListing::whereHas('credential', fn ($q) => $q->where('user_id', $user->id))
            ->selectRaw('user_marketplace_credential_id, COUNT(*) as total, SUM(listed_stock) as stock')
            ->groupBy('user_marketplace_credential_id')
            ->with('credential.marketplace')
            ->get();

        $recentOrders = $currentOrders
            ->with('items')
            ->orderByDesc('order_date')
            ->limit(5)
            ->get();

        $profitSource = $this->resolveProfitSource($credential->id, $currentStart, $currentEnd);

        return view('dashboard', [
            'user' => $user,
            'hasCredential' => true,
            'period' => $period,
            'kpis' => $kpis,
            'chartData' => $chartData,
            'recentOrders' => $recentOrders,
            'listingsSummary' => $listingsSummary,
            'lastSyncAt' => $credential->last_sync_at,
            'zeroCostCount' => $zeroCostCount,
            'profitSource' => $profitSource,
        ]);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'today' => [
                now()->toDateString(), now()->toDateString(),
                now()->subDay()->toDateString(), now()->subDay()->toDateString(),
            ],
            'week' => [
                now()->startOfWeek()->toDateString(), now()->toDateString(),
                now()->subWeek()->startOfWeek()->toDateString(), now()->subWeek()->endOfWeek()->toDateString(),
            ],
            'year' => [
                now()->startOfYear()->toDateString(), now()->toDateString(),
                now()->subYear()->startOfYear()->toDateString(), now()->subYear()->endOfYear()->toDateString(),
            ],
            default => [
                now()->startOfMonth()->toDateString(), now()->toDateString(),
                now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString(),
            ],
        };
    }

    /**
     * @return array{gross_sales: float, net_profit: float, ad_spend: float}
     */
    private function aggregateSummary(int $credentialId, string $from, string $to): array
    {
        $rows = FinancialDailySummary::where('user_marketplace_credential_id', $credentialId)
            ->whereBetween('date', [$from.' 00:00:00', $to.' 23:59:59'])
            ->selectRaw('COALESCE(SUM(gross_sales), 0) as gross, COALESCE(SUM(COALESCE(NULLIF(true_net_profit, 0), net_profit)), 0) as net, COALESCE(SUM(ad_cost), 0) as ad_spend')
            ->first();

        return [
            'gross_sales' => (float) ($rows->gross ?? 0),
            'net_profit' => (float) ($rows->net ?? 0),
            'ad_spend' => (float) ($rows->ad_spend ?? 0),
        ];
    }

    /**
     * Dönem kâr rakamının kaynağı: kalemlerin reconciliation_status dağılımı.
     * Hepsi settled → 'settled', hepsi estimated → 'estimate', karışık → 'mixed',
     * veri yoksa null (rozet gösterilmez).
     */
    private function resolveProfitSource(int $credentialId, string $from, string $to): ?string
    {
        $counts = OrderItemFinancial::query()
            ->where('user_marketplace_credential_id', $credentialId)
            ->whereBetween('order_date', [$from.' 00:00:00', $to.' 23:59:59'])
            ->toBase()
            ->selectRaw('reconciliation_status, COUNT(*) as c')
            ->groupBy('reconciliation_status')
            ->pluck('c', 'reconciliation_status');

        $total = (int) $counts->sum();
        if ($total === 0) {
            return null;
        }

        if ((int) ($counts['settled'] ?? 0) === $total) {
            return 'settled';
        }

        if ((int) ($counts['estimated'] ?? 0) === $total) {
            return 'estimate';
        }

        return 'mixed';
    }

    private function percentChange(float $current, float $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? '+100' : '0';
        }

        $pct = round((($current - $previous) / $previous) * 100, 1);

        return $pct >= 0 ? "+{$pct}" : (string) $pct;
    }
}
