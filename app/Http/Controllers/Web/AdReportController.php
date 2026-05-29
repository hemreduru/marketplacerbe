<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Ads\AdReportService;
use App\Services\Ads\AdSyncService;
use App\Services\Reports\ReportPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PR 4.6 — Reklam performans raporu (Spec 10.8).
 */
class AdReportController extends Controller
{
    public function __construct(
        private readonly AdReportService $service,
        private readonly AdSyncService $syncService,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $period = ReportPeriod::fromRequest(
            $request->string('period', 'this_month')->toString(),
            $request->string('from')->toString(),
            $request->string('to')->toString(),
        );

        $report = $this->service->report($user, $period);

        return view('reports.ad-report', [
            'campaigns' => $report['campaigns'],
            'totals' => $report['totals'],
            'period' => $period->key,
            'from' => $period->fromDate(),
            'to' => $period->toDate(),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $user = Auth::user();
        $synced = 0;

        foreach ($user->marketplaceCredentials()->with('marketplace')->get() as $credential) {
            $result = $this->syncService->sync($credential);
            if ($result->ok) {
                $synced += $result->data['campaigns'] ?? 0;
            }
        }

        return back()->with('info', __('reports.ads_synced', ['count' => $synced]));
    }
}
