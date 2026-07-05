<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\MarketplaceSyncLog;
use App\Models\User;
use App\Services\MarketplaceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function __construct(private MarketplaceManager $marketplace) {}

    public function index()
    {
        return view('claims.index');
    }

    /**
     * İade detayı — zenginleştirilmiş alanlar (neden, kargo, iade tutarı,
     * stoğa iade). Kullanıcının kendi credential'ına scope'lu.
     */
    public function show(int $id): View
    {
        /** @var User $user */
        $user = Auth::user();

        $claim = Claim::whereHas('credential', fn ($q) => $q->where('user_id', $user->id))
            ->findOrFail($id);

        return view('claims.detail', ['claim' => $claim]);
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
                '<a href="'.route('claims.show', $claim->getKey()).'" class="fw-bold text-gray-800 text-hover-primary">'.e($claim->order_number ?? '-').'</a>',
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

            if (! $result->ok) {
                return response()->json(['success' => false, 'message' => $result->errorMessage], 500);
            }

            return response()->json(['success' => true, 'message' => __('common.status_updated')]);
        } catch (\Exception $e) {
            Log::error('Claim approve exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('common.error_occurred')], 500);
        }
    }
}
