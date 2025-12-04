<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Marketplace;
use App\Models\UserMarketplaceCredential;
use App\Http\Requests\UpdateMarketplaceSettingsRequest;

class MarketplaceSettingsController extends Controller
{
    /**
     * Show marketplace settings page
     */
    public function index()
    {
        $user = Auth::user();
        $marketplaces = Marketplace::where('is_active', true)->get();
        $credentials = UserMarketplaceCredential::where('user_id', $user->id)->get()->keyBy('marketplace_id');

        return view('marketplace.settings', [
            'user' => $user,
            'marketplaces' => $marketplaces,
            'credentials' => $credentials
        ]);
    }

    /**
     * Update marketplace credentials (AJAX)
     */
    public function update(UpdateMarketplaceSettingsRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $validated = $request->validated();

            $credential = UserMarketplaceCredential::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'marketplace_id' => $validated['marketplace_id'],
                ],
                [
                    'api_key' => $validated['api_key'],
                    'api_secret' => $validated['api_secret'],
                    'additional_credentials' => $validated['additional_credentials'] ?? [],
                    'is_active' => true,
                ]
            );

            DB::commit();

            $marketplace = \App\Models\Marketplace::find($validated['marketplace_id']);
            if ($marketplace && $marketplace->slug === 'trendyol') {
                \App\Jobs\SyncTrendyolFinancialsJob::dispatch($credential->id);
            }

            return response()->json([
                'success' => true,
                'message' => __('settings.credentials_updated'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update marketplace credentials: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
