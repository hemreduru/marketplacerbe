<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FinancialDailySummary;
use App\Models\Order;
use App\Models\Product;
use App\Services\MarketplaceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private MarketplaceManager $marketplace) {}

    /**
     * Display the dashboard with key performance indicators.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $credential = $this->marketplace->credentialFor($user);

        if (! $credential) {
            return view('dashboard', ['user' => $user, 'hasCredential' => false]);
        }

        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        $monthSales = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)
            ->whereBetween('date', [$monthStart, $today]);
        $monthGross = (float) (clone $monthSales)->sum('gross_sales');
        $monthNet = (float) (clone $monthSales)->sum('net_profit');

        $todaySummary = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)
            ->whereDate('date', $today)
            ->first();

        $ordersQuery = Order::where('user_id', $user->id);
        $statusCounts = (clone $ordersQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $productsScope = fn () => Product::whereHas('credential', fn ($q) => $q->where('id', $credential->id));

        $kpis = [
            'month_gross' => $monthGross,
            'month_net' => $monthNet,
            'today_gross' => (float) ($todaySummary->gross_sales ?? 0),
            'today_net' => (float) ($todaySummary->net_profit ?? 0),
            'total_orders' => (clone $ordersQuery)->count(),
            'waiting_orders' => (clone $ordersQuery)->where('status', 'Created')->count(),
            'total_products' => $productsScope()->count(),
            'out_of_stock' => $productsScope()->where('stock', '<=', 0)->count(),
            'low_stock' => $productsScope()->whereBetween('stock', [1, 5])->count(),
            'waiting_questions' => $credential->questions()->where('status', 'WAITING_FOR_ANSWER')->count(),
        ];

        $trend = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->orderBy('date')
            ->get();

        $chartData = [
            'dates' => $trend->map(fn ($s) => $s->date->translatedFormat('d M'))->toArray(),
            'sales' => $trend->map(fn ($s) => (float) $s->gross_sales)->toArray(),
            'net' => $trend->map(fn ($s) => (float) $s->net_profit)->toArray(),
        ];

        $recentOrders = (clone $ordersQuery)
            ->with('items')
            ->orderByDesc('order_date')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'hasCredential' => true,
            'kpis' => $kpis,
            'statusCounts' => $statusCounts,
            'chartData' => $chartData,
            'recentOrders' => $recentOrders,
            'lastSyncAt' => $credential->last_sync_at,
        ]);
    }
}
