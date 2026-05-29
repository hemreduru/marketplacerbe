<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reports\MarketplaceComparisonService;
use App\Services\Reports\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PR 4.4 — Pazaryeri karşılaştırma pivotu (Spec 10.6).
 */
class MarketplaceComparisonController extends Controller
{
    public function __construct(private readonly MarketplaceComparisonService $service) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $period = ReportPeriod::fromRequest(
            $request->string('period', 'this_month')->toString(),
            $request->string('from')->toString(),
            $request->string('to')->toString(),
        );

        $pivot = $this->service->pivot($user, $period);

        return view('reports.marketplace-comparison', [
            'marketplaces' => $pivot['marketplaces'],
            'rows' => $pivot['rows'],
            'period' => $period->key,
            'from' => $period->fromDate(),
            'to' => $period->toDate(),
        ]);
    }
}
