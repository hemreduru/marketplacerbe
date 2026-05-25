<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceSyncLog;
use App\Services\MarketplaceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClaimController extends Controller
{
    public function __construct(private MarketplaceManager $marketplace) {}

    public function index()
    {
        return view('claims.index');
    }

    public function getData(Request $request)
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $query = $credential->claims();
        $totalRecords = (clone $query)->count();

        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $claims = $query->orderByDesc('claim_date')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $claims->map(function ($claim) {
            $statusBadge = '<span class="badge badge-light-info">'.e($claim->status ?? '-').'</span>';

            return [
                '<span class="fw-bold text-gray-800">'.e($claim->order_number ?? '-').'</span>',
                e($claim->customer_name ?? '-'),
                '<span class="fw-bold">'.$claim->item_count.'</span>',
                $statusBadge,
                $claim->claim_date ? $claim->claim_date->format('d.m.Y H:i') : '-',
            ];
        })->all();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function sync()
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
        }

        $log = MarketplaceSyncLog::start($credential->id, 'claim');

        try {
            $stats = $this->marketplace->claimService($credential)->syncClaims($credential->id);

            $credential->update(['last_sync_at' => now()]);
            $log->succeed($stats);

            return response()->json(['success' => true, 'message' => __('common.sync_completed')]);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            Log::error('Claim sync exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('common.error_occurred')], 500);
        }
    }

    public function approve(Request $request)
    {
        $validated = $request->validate([
            'claim_id' => 'required|string',
            'claim_item_ids' => 'required|array|min:1',
        ]);

        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
        }

        // Gate live writes: never approve on the live store unless explicitly enabled.
        if (! config('marketplace.write_enabled')) {
            return response()->json(['success' => true, 'message' => __('common.action_simulated')]);
        }

        try {
            $result = $this->marketplace->claimService($credential)
                ->approveClaimItems($validated['claim_id'], $validated['claim_item_ids']);

            if (isset($result['error'])) {
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }

            return response()->json(['success' => true, 'message' => __('common.status_updated')]);
        } catch (\Exception $e) {
            Log::error('Claim approve exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('common.error_occurred')], 500);
        }
    }
}
