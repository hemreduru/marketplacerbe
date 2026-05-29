<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reports\VatReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PR 4.5 — KDV & Vergi raporu + export (Spec 10.7).
 */
class VatReportController extends Controller
{
    public function __construct(private readonly VatReportService $service) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        [$year, $month] = $this->resolveMonth($request);

        $report = $this->service->monthly($user, $year, $month);

        return view('reports.vat-report', array_merge($report, [
            'month' => sprintf('%04d-%02d', $year, $month),
        ]));
    }

    public function export(Request $request): StreamedResponse
    {
        $user = Auth::user();
        [$year, $month] = $this->resolveMonth($request);
        $report = $this->service->monthly($user, $year, $month);

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Product', 'Sale VAT', 'Purchase VAT', 'Commission VAT', 'Shipping VAT', 'Net']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, [$row['sku'], $row['title'], $row['sale_vat'], $row['purchase_vat'], $row['commission_vat'], $row['shipping_vat'], $row['net']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['TOTAL', '', $report['totals']['sale_vat'], $report['totals']['purchase_vat'], $report['totals']['commission_vat'], $report['totals']['shipping_vat'], $report['totals']['net']]);
            fclose($out);
        }, 'vat-report-'.sprintf('%04d-%02d', $year, $month).'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function resolveMonth(Request $request): array
    {
        $monthStr = $request->string('month')->toString();
        $date = $monthStr !== '' ? CarbonImmutable::parse($monthStr.'-01') : CarbonImmutable::now();

        return [$date->year, $date->month];
    }
}
