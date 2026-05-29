<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reports\StockReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PR 4.2 — Stok raporu + satın alma listesi (Spec 10.4).
 */
class StockReportController extends Controller
{
    public function __construct(private readonly StockReportService $service) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->string('filter', 'all')->toString();
        $filter = in_array($filter, ['all', 'critical', 'zero', 'dead'], true) ? $filter : 'all';

        return view('reports.stock-report', [
            'rows' => $this->service->rows($user, $filter),
            'filter' => $filter,
        ]);
    }

    public function purchaseOrder(): StreamedResponse
    {
        $user = Auth::user();
        $rows = $this->service->purchaseOrder($user);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Product', 'Current Stock', 'Sales/Day', 'Suggested Qty']);
            foreach ($rows as $row) {
                fputcsv($out, [$row['sku'], $row['title'], $row['current_stock'], $row['velocity'], $row['suggested_qty']]);
            }
            fclose($out);
        }, 'purchase-order-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }
}
