<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReturnReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PR 4.3 — İade analiz raporu (Spec 10.5).
 */
class ReturnReportController extends Controller
{
    public function __construct(private readonly ReturnReportService $service) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $period = ReportPeriod::fromRequest(
            $request->string('period', 'this_month')->toString(),
            $request->string('from')->toString(),
            $request->string('to')->toString(),
        );

        return view('reports.return-report', [
            'summary' => $this->service->summary($user, $period),
            'byReason' => $this->service->byReason($user, $period),
            'bySku' => $this->service->bySku($user, $period),
            'period' => $period->key,
            'from' => $period->fromDate(),
            'to' => $period->toDate(),
        ]);
    }
}
