<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Order;
use App\Services\Reports\OrderReportService;
use App\Services\Reports\ReportPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PR 4.1 — Sipariş Raporu + toplu işlemler (Spec 10.3).
 */
class OrderReportController extends Controller
{
    public function __construct(private readonly OrderReportService $service) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $period = ReportPeriod::fromRequest(
            $request->string('period', 'this_month')->toString(),
            $request->string('from')->toString(),
            $request->string('to')->toString(),
        );

        $filters = $this->filters($request);
        $orders = $this->service->paginate($user, $period, $filters);
        $netProfitMap = $this->service->netProfitMap($orders->getCollection());

        $marketplaces = Marketplace::whereIn('id', $user->marketplaceCredentials()->pluck('marketplace_id'))->get();
        $statuses = Order::where('user_id', $user->id)->distinct()->pluck('status')->filter()->values();

        return view('reports.order-report', [
            'orders' => $orders,
            'netProfitMap' => $netProfitMap,
            'period' => $period->key,
            'from' => $period->fromDate(),
            'to' => $period->toDate(),
            'marketplaces' => $marketplaces,
            'statuses' => $statuses,
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $period = ReportPeriod::fromRequest(
            $request->string('period', 'this_month')->toString(),
            $request->string('from')->toString(),
            $request->string('to')->toString(),
        );

        $orders = $this->service->query($user, $period, $this->filters($request))->get();
        $netProfitMap = $this->service->netProfitMap($orders);

        $filename = 'order-report-'.$period->fromDate().'_'.$period->toDate().'.csv';

        return response()->streamDownload(function () use ($orders, $netProfitMap) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order #', 'Marketplace', 'Date', 'City', 'Items', 'Amount', 'Status', 'Net Profit']);
            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->marketplace?->name,
                    $order->order_date?->toDateString(),
                    $order->shipping_city,
                    $order->items_count,
                    $order->total_amount,
                    $order->status,
                    $netProfitMap[$order->id] ?? '0',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:status,invoice,cargo',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer',
            'new_status' => 'required_if:action,status|string',
        ]);

        $user = Auth::user();
        $orderIds = array_map('intval', $validated['order_ids']);

        if ($validated['action'] === 'status') {
            $count = $this->service->bulkUpdateLocalStatus($user, $orderIds, $validated['new_status']);

            return back()->with('success', __('reports.bulk_status_done', ['count' => $count]));
        }

        // invoice / cargo: pazaryeri/sağlayıcı yazması gerektirir — iki katmanlı write
        // guard kapalıysa canlı çağrı YAPILMAZ (seeded key gerçek mağaza).
        if (! config('marketplace.write_enabled')) {
            return back()->with('info', __('reports.bulk_write_disabled'));
        }

        return back()->with('info', __('reports.bulk_queued', ['count' => count($orderIds)]));
    }

    /**
     * @return array{marketplace_id: ?string, status: ?string, city: ?string, q: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'marketplace_id' => $request->string('marketplace_id')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'city' => $request->string('city')->toString() ?: null,
            'q' => $request->string('q')->toString() ?: null,
        ];
    }
}
