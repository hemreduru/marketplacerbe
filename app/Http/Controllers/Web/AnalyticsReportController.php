<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reports\AnalyticsReportService;
use App\Services\Reports\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PR 4.7 — Coğrafya + saat ısı haritası + cohort + LTM trend (Spec 10.10–10.14).
 */
class AnalyticsReportController extends Controller
{
    public function __construct(private readonly AnalyticsReportService $service) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $period = ReportPeriod::fromRequest(
            $request->string('period', 'this_month')->toString(),
            $request->string('from')->toString(),
            $request->string('to')->toString(),
        );

        return view('reports.analytics', [
            'topCities' => $this->service->topCities($user, $period),
            'heatmap' => $this->service->hourlyHeatmap($user, $period),
            'cohort' => $this->service->cohort($user),
            'ltm' => $this->service->ltmTrend($user),
            'period' => $period->key,
            'from' => $period->fromDate(),
            'to' => $period->toDate(),
        ]);
    }
}
